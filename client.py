import socket
import tkinter as tk
from tkinter import messagebox
import threading
import select

SERVER_HOST = '127.0.0.1'
SERVER_PORT = 12345

client_socket = None
current_user = None
current_chat = None
receive_thread = None

# Function to handle server connection
def connect_to_server():
    global client_socket
    try:
        client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        client_socket.connect((SERVER_HOST, SERVER_PORT))
        client_socket.setblocking(False)  # Set socket to non-blocking mode
    except Exception as e:
        messagebox.showerror("Error", f"Unable to connect to the server: {e}")

# Function to handle login attempt
def attempt_login():
    global client_socket, current_user
    username = entry_username.get()
    password = entry_password.get()

    if not username or not password:
        messagebox.showwarning("Input Error", "Please enter both username and password.")
        return

    connect_to_server()  # Ensure connection is established

    try:
        # Use select to wait for the socket to be ready to send data
        rlist, wlist, xlist = select.select([], [client_socket], [], 1)
        if client_socket in wlist:
            # Send login request
            client_socket.send(f"login,{username},{password}".encode('utf-8'))

            # Wait for server response
            rlist, _, _ = select.select([client_socket], [], [], 1)
            if client_socket in rlist:
                response = client_socket.recv(1024).decode('utf-8')
                messagebox.showinfo("Login Response", response)

                if "authentication successful" in response:
                    current_user = username

                    # Hide the login frame
                    login_frame.pack_forget()

                    # Show the chat frame
                    chat_frame.pack(fill="both", expand=True)

                    # List connected users (only if login is successful)
                    client_socket.send("list_users".encode('utf-8'))
                    rlist, _, _ = select.select([client_socket], [], [], 1)
                    if client_socket in rlist:
                        users_response = client_socket.recv(1024).decode('utf-8')
                        if "Connected users:" in users_response:
                            users_list = users_response.split(":")[1].split(",")
                            user_listbox.delete(0, tk.END)  # Clear previous users
                            for user in users_list:
                                user_listbox.insert(tk.END, user.strip())

                    # Start listening for incoming messages in a separate thread
                    global receive_thread
                    receive_thread = threading.Thread(target=receive_messages, daemon=True)
                    receive_thread.start()

    except Exception as e:
        messagebox.showerror("Error", f"Unable to connect to the server: {e}")

# Function to send chat message
def send_message():
    global current_chat  # Ensure we are sending to the current chat user

    message = entry_message.get()
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
            response = client_socket.recv(1024).decode('utf-8')
            messagebox.showinfo("Switch User Response", f"Now chatting with {selected_user}")
        
        # Display the switch action message to confirm chat switch
        display_message(f"Switched to chat with {selected_user}")
        
        # Stop the previous receiving thread if it exists and start a new one
        if receive_thread.is_alive():
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
    global client_socket
    while True:
        try:
            # Use select to check if the socket is ready to receive data
            rlist, _, _ = select.select([client_socket], [], [], 1)
            if client_socket in rlist:
                message = client_socket.recv(1024).decode('utf-8')
                if message:
                    root.after(0, display_message, message)  # Update UI from the main thread
        except BlockingIOError:
            # This is expected when the socket is non-blocking, we can just continue
            continue
        except Exception as e:
            print(f"Error receiving message: {e}")
            break

# Function to handle user registration
def attempt_register():
    global client_socket
    username = entry_username.get()
    password = entry_password.get()

    if not username or not password:
        messagebox.showwarning("Input Error", "Please enter both username and password.")
        return

    connect_to_server()  # Ensure connection is established

    try:
        # Use select to wait for the socket to be ready to send data
        rlist, wlist, xlist = select.select([], [client_socket], [], 1)
        if client_socket in wlist:
            # Send register request
            client_socket.send(f"register,{username},{password}".encode('utf-8'))

            # Wait for server response
            rlist, _, _ = select.select([client_socket], [], [], 1)
            if client_socket in rlist:
                response = client_socket.recv(1024).decode('utf-8')
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
