import socket
import tkinter as tk
from tkinter import messagebox
import threading
import select
import bcrypt  # For password hashing
import ssl
import time
import logging
import re
from datetime import datetime, timedelta

# Add logging configuration
logging.basicConfig(level=logging.DEBUG)

SERVER_HOST = '127.0.0.1'
SERVER_PORT = 12345

client_socket = None
current_user = None
current_chat = None
receive_thread = None
last_activity = None

# Security constants
MAX_MESSAGE_LENGTH = 1024
ALLOWED_CHARS = re.compile(r'^[a-zA-Z0-9_.-]+$')
SESSION_TIMEOUT = 3600  # 1 hour

# Function to hash the password
def hash_password(password):
    hashed_bytes = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt())
    return hashed_bytes.decode('utf-8')

# Function to verify the password
def verify_password(password, hashed_password):
    return bcrypt.checkpw(password.encode('utf-8'), hashed_password.encode('utf-8'))

def validate_input(text, max_length=MAX_MESSAGE_LENGTH):
    if not text or len(text) > max_length:
        return False
    return True

def validate_username(username):
    return bool(username and ALLOWED_CHARS.match(username))

def check_session_timeout():
    global last_activity
    if last_activity and datetime.now() - last_activity > timedelta(seconds=SESSION_TIMEOUT):
        messagebox.showerror("Session Expired", "Your session has expired. Please login again.")
        return True
    last_activity = datetime.now()
    return False

# Function to handle server connection
def connect_to_server(max_retries=3, retry_delay=2):
    global client_socket
    for attempt in range(max_retries):
        try:
            client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            client_socket.settimeout(10)
            
            # Modified SSL context setup
            ssl_context = ssl.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
            ssl_context.check_hostname = False
            ssl_context.verify_mode = ssl.CERT_NONE
            
            logging.info("Attempting to connect to server...")
            client_socket.connect((SERVER_HOST, SERVER_PORT))
            logging.info("TCP connection established, starting SSL handshake...")
            
            client_socket = ssl_context.wrap_socket(client_socket, 
                                                  server_hostname=SERVER_HOST,
                                                  do_handshake_on_connect=True)
            logging.info("SSL handshake completed successfully")
            
            # Keep socket blocking for initial setup
            client_socket.setblocking(True)
            return True

        except (socket.error, ssl.SSLError) as e:
            logging.error(f"Connection attempt {attempt + 1} failed: {str(e)}")
            if attempt < max_retries - 1:
                messagebox.showinfo("Connection Retry", 
                                  f"Unable to connect. Retrying in {retry_delay} seconds...")
                time.sleep(retry_delay)
            else:
                messagebox.showerror("Error", 
                                   f"Unable to connect to the server after {max_retries} attempts: {e}")
                if client_socket:
                    try:
                        client_socket.close()
                    except:
                        pass
                client_socket = None
                return False
        except Exception as e:
            logging.error(f"Unexpected error during connection: {str(e)}")
            if client_socket:
                try:
                    client_socket.close()
                except:
                    pass
            client_socket = None
            return False
            
    return False

def update_user_list():
    try:
        client_socket.send("list_users".encode('utf-8'))
        rlist, _, _ = select.select([client_socket], [], [], 1)
        if client_socket in rlist:
            users_response = client_socket.recv(1024).decode('utf-8')
            if "Connected users:" in users_response:
                users_list = users_response.split(":")[1].split(",")
                user_listbox.delete(0, tk.END)  # Clear previous users
                for user in users_list:
                    if user.strip():  # Only add non-empty user names
                        user_listbox.insert(tk.END, user.strip())
    except socket.error as e:
        messagebox.showerror("Error", f"Error updating user list: {e}")

# Function to handle login attempt
def attempt_login():
    global client_socket, current_user, last_activity
    username = entry_username.get()
    password = entry_password.get()

    if not username or not password:
        messagebox.showwarning("Input Error", "Please enter both username and password.")
        return

    if not validate_username(username):
        messagebox.showwarning("Input Error", "Invalid username format. Use only letters, numbers, dots, and underscores.")
        return

    if not connect_to_server():  # Only proceed if connection is successful
        return

    try:
        # Use select to wait for the socket to be ready to send data
        rlist, wlist, xlist = select.select([], [client_socket], [], 1)
        if client_socket in wlist:
            # Send login request with plain password
            try:
                client_socket.send(f"login,{username},{password}".encode('utf-8'))
            except socket.error as e:
                messagebox.showerror("Error", f"Error sending login request: {e}")
                return

            # Wait for server response
            rlist, _, _ = select.select([client_socket], [], [], 1)
            if client_socket in rlist:
                try:
                    response = client_socket.recv(1024).decode('utf-8')
                except socket.error as e:
                    messagebox.showerror("Error", f"Error receiving login response: {e}")
                    return
                messagebox.showinfo("Login Response", response)

                if "authentication successful" in response:
                    current_user = username
                    last_activity = datetime.now()

                    # Hide the login frame
                    login_frame.pack_forget()

                    # Show the chat frame
                    chat_frame.pack(fill="both", expand=True)

                    # Update the user list after successful login
                    update_user_list()  # Ensure user list updates immediately

                    # Start listening for incoming messages in a separate thread
                    global receive_thread
                    receive_thread = threading.Thread(target=receive_messages, daemon=True)
                    receive_thread.start()

                    start_user_list_refresh()  # Start periodic refresh

    except Exception as e:
        messagebox.showerror("Error", f"Unable to connect to the server: {e}")

# Function to send chat message
def send_message():
    if check_session_timeout():
        return

    global current_chat  # Ensure we are sending to the current chat user

    message = entry_message.get()
    if not validate_input(message):
        messagebox.showwarning("Input Error", "Invalid message format or length.")
        return

    if message and current_chat:  # Ensure current_chat is not None
        # Replace text emojis with their Unicode equivalents
        emojis = {
            ":)": "😊",
            ";)": "😉",
            ":D": "😄",
            ":P": "😜",
            ":(": "☹️"
        }
        for emoji, unicode in emojis.items():
            message = message.replace(emoji, unicode)

        # Send the message to the correct user
        threading.Thread(target=send_message_thread, args=(current_chat, message), daemon=True).start()
        display_message(f"{current_user}: {message}")  # Display the message in the chat window
        entry_message.delete(0, tk.END)

# Send message in a separate thread to avoid blocking the UI
def send_message_thread(to_user, message):
    try:
        client_socket.send(f"chat,{to_user},{message}".encode('utf-8'))
    except Exception as e:
        print(f"Error sending message: {e}")

# Function to switch users (chat with a different user)
def switch_user():
    global current_chat, receive_thread

    selected_user = user_listbox.get(tk.ACTIVE)
    if selected_user:
        current_chat = selected_user  # Update current chat to the selected user
        threading.Thread(target=switch_user_thread, args=(selected_user,), daemon=True).start()

# Switch user in a separate thread to avoid blocking the UI
def switch_user_thread(selected_user):
    try:
        client_socket.send(f"switch_user,{selected_user}".encode('utf-8'))
        
        # Wait for server response before continuing
        rlist, _, _ = select.select([client_socket], [], [], 1)
        if client_socket in rlist:
            try:
                response = client_socket.recv(1024).decode('utf-8')
            except socket.error as e:
                messagebox.showerror("Error", f"Error receiving switch user response: {e}")
                return
            messagebox.showinfo("Switch User Response", f"Now chatting with {selected_user}")
        
        # Display the switch action message to confirm chat switch
        display_message(f"Switched to chat with {selected_user}")
        
        # Stop the previous receiving thread if it exists and start a new one
        if receive_thread and receive_thread.is_alive():
            receive_thread.join()  # Ensure previous thread stops before restarting
        receive_thread = threading.Thread(target=receive_messages, daemon=True)
        receive_thread.start()
        
    except Exception as e:
        print(f"Error switching user: {e}")

# Function to display a message in the chat window
def display_message(message):
    chat_box.config(state=tk.NORMAL)
    chat_box.insert(tk.END, message + "\n")
    chat_box.config(state=tk.DISABLED)
    chat_box.yview(tk.END)

# Function to handle incoming messages
def receive_messages():
    global client_socket, last_activity, current_chat
    while True:
        try:
            if check_session_timeout():
                break

            rlist, _, _ = select.select([client_socket], [], [], 1)
            if client_socket in rlist:
                try:
                    message = client_socket.recv(1024).decode('utf-8')
                except socket.error as e:
                    print(f"Error receiving message: {e}")
                    break
                    
                if message:
                    # Check for user connection/disconnection messages
                    if "has joined the chat" in message or "has disconnected" in message:
                        root.after(100, update_user_list)  # Update user list after brief delay
                        # Reset current_chat if chat partner disconnected
                        if current_chat and f"User {current_chat} has disconnected" in message:
                            current_chat = None
                            root.after(0, display_message, "Your chat partner has disconnected")
                    root.after(0, display_message, message)
        except Exception as e:
            print(f"Error receiving message: {e}")
            break

# Add periodic user list refresh
def start_user_list_refresh():
    update_user_list()
    root.after(5000, start_user_list_refresh)  # Refresh every 5 seconds

# Function to handle user registration
def attempt_register():
    global client_socket
    username = entry_username.get()
    password = entry_password.get()

    if not username or not password:
        messagebox.showwarning("Input Error", "Please enter both username and password.")
        return

    if not validate_username(username):
        messagebox.showwarning("Input Error", "Invalid username format. Use only letters, numbers, dots, and underscores.")
        return

    if not connect_to_server():  # Only proceed if connection is successful
        return

    try:
        rlist, wlist, xlist = select.select([], [client_socket], [], 1)
        if client_socket in wlist:
            try:
                client_socket.send(f"register,{username},{password}".encode('utf-8'))
            except socket.error as e:
                messagebox.showerror("Error", f"Error sending register request: {e}")
                return

            # Wait for server response
            rlist, _, _ = select.select([client_socket], [], [], 1)
            if client_socket in rlist:
                try:
                    response = client_socket.recv(1024).decode('utf-8')
                except socket.error as e:
                    messagebox.showerror("Error", f"Error receiving register response: {e}")
                    return
                messagebox.showinfo("Register Response", response)

    except Exception as e:
        messagebox.showerror("Error", f"Unable to connect to the server: {e}")

# Set up the GUI
root = tk.Tk()
root.title("SecureChat")

# Login frame
login_frame = tk.Frame(root)
login_frame.pack(pady=20)

tk.Label(login_frame, text="Username").grid(row=0, column=0)
entry_username = tk.Entry(login_frame)
entry_username.grid(row=0, column=1)

tk.Label(login_frame, text="Password").grid(row=1, column=0)
entry_password = tk.Entry(login_frame, show="*")
entry_password.grid(row=1, column=1)

btn_login = tk.Button(login_frame, text="Login", command=attempt_login)
btn_login.grid(row=2, column=0, columnspan=2)

btn_register = tk.Button(login_frame, text="Register", command=attempt_register)
btn_register.grid(row=3, column=0, columnspan=2)

# Chat frame
chat_frame = tk.Frame(root)

# Display the list of users
user_listbox = tk.Listbox(chat_frame)
user_listbox.pack(side=tk.LEFT, fill=tk.Y, padx=10)

# Display the chat messages
chat_box = tk.Text(chat_frame, state=tk.DISABLED, height=20, width=50)
chat_box.pack(side=tk.LEFT, padx=10)

# Message entry
entry_message = tk.Entry(chat_frame)
entry_message.pack(side=tk.BOTTOM, fill=tk.X, padx=10, pady=10)

btn_send = tk.Button(chat_frame, text="Send", command=send_message)
btn_send.pack(side=tk.BOTTOM)

# Switch user button
btn_switch_user = tk.Button(chat_frame, text="Switch User", command=switch_user)
btn_switch_user.pack(side=tk.BOTTOM)

root.mainloop()