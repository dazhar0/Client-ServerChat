import socket
import threading
import select
import bcrypt  # For password hashing
import ssl
import time
import logging
import re
import hmac
from datetime import datetime, timedelta
from collections import defaultdict

SERVER_HOST = '127.0.0.1'
SERVER_PORT = 12345
BUFFER_SIZE = 1024

clients = {}  # Track clients and their usernames
usernames = {}  # Track usernames and their passwords
chat_sessions = {}  # Track active chat sessions (Key: username, Value: username of chat partner)

# Add logging configuration
logging.basicConfig(level=logging.DEBUG)

# Add security constants
MAX_LOGIN_ATTEMPTS = 3
LOGIN_TIMEOUT = 300  # 5 minutes
SESSION_TIMEOUT = 3600  # 1 hour
MAX_MESSAGE_LENGTH = 1024
ALLOWED_CHARS = re.compile(r'^[a-zA-Z0-9_.-]+$')

# Add security tracking
login_attempts = defaultdict(int)
login_timestamps = {}
user_sessions = {}

# Function to hash the password
def hash_password(password):
    hashed_bytes = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt())
    return hashed_bytes.decode('utf-8')

# Function to verify the password
def verify_password(password, hashed_password):
    return bcrypt.checkpw(password.encode('utf-8'), hashed_password.encode('utf-8'))

def sanitize_input(text, max_length=MAX_MESSAGE_LENGTH):
    if not text or len(text) > max_length:
        return None
    return text.strip()

def validate_username(username):
    return bool(username and ALLOWED_CHARS.match(username))

def is_rate_limited(ip_address):
    if login_attempts[ip_address] >= MAX_LOGIN_ATTEMPTS:
        last_attempt = login_timestamps.get(ip_address)
        if last_attempt and datetime.now() - last_attempt < timedelta(seconds=LOGIN_TIMEOUT):
            return True
        login_attempts[ip_address] = 0
    return False

def notify_all_clients(message, exclude_socket=None):
    for client in clients:
        if client != exclude_socket:
            try:
                client.send(message.encode('utf-8'))
            except socket.error as e:
                logging.error(f"Error notifying client: {e}")

# Function to handle each client
def handle_client(client_socket, client_address):
    logging.info(f"New connection from {client_address}")

    # Receive login or registration request
    while True:
        try:
            if is_rate_limited(client_address[0]):
                client_socket.send("Rate limited. Try again later.".encode('utf-8'))
                break

            message = client_socket.recv(BUFFER_SIZE).decode('utf-8')
            if not message:
                break  # Client disconnected

            # Validate message format
            message_parts = message.split(',')
            if len(message_parts) < 2:
                continue

            command = message_parts[0]

            if command == 'login':
                if len(message_parts) != 3:
                    continue

                username = sanitize_input(message_parts[1])
                password = message_parts[2]

                if not validate_username(username):
                    response = "Invalid username format"
                    client_socket.send(response.encode('utf-8'))
                    continue

                if username in usernames:
                    stored_hash = usernames[username]
                    # Hash the received plain password before comparing
                    if verify_password(password, stored_hash):
                        response = "authentication successful"
                        logging.info(f"Authentication successful for {username}")
                        login_attempts[client_address[0]] = 0
                        user_sessions[username] = datetime.now()
                    else:
                        response = "authentication failed"
                        logging.info(f"Authentication failed for {username}")
                        login_attempts[client_address[0]] += 1
                        login_timestamps[client_address[0]] = datetime.now()
                else:
                    response = "user not found"
                    logging.info(f"User not found: {username}")

                try:
                    client_socket.send(response.encode('utf-8'))
                except socket.error as e:
                    logging.error(f"Error sending authentication response: {e}")
                if response == "authentication successful":
                    clients[client_socket] = username
                    # Send the list of connected users to the newly logged-in client
                    connected_users = [user for client, user in clients.items() if user != username]
                    if connected_users:
                        user_list_response = f"Connected users: {', '.join(connected_users)}"
                    else:
                        user_list_response = "Connected users: No other users connected"
                    try:
                        client_socket.send(user_list_response.encode('utf-8'))
                        # Notify other clients about the new user
                        notification = f"User {username} has joined the chat"
                        for other_socket in clients:
                            if other_socket != client_socket:
                                try:
                                    other_socket.send(notification.encode('utf-8'))
                                except socket.error as e:
                                    logging.error(f"Error notifying other client: {e}")
                        notify_all_clients("list_users")
                    except socket.error as e:
                        logging.error(f"Error sending user list: {e}")
                    break

            elif command == 'register':
                if len(message_parts) != 3:
                    continue

                username = sanitize_input(message_parts[1])
                password = message_parts[2]

                if not validate_username(username):
                    response = "Invalid username format"
                    client_socket.send(response.encode('utf-8'))
                    continue

                logging.info(f"Registering user {username}")

                if username not in usernames:
                    # Hash the received plain password before storing
                    hashed_password = hash_password(password)
                    usernames[username] = hashed_password
                    response = f"User {username} registered successfully"
                    logging.info(f"Successfully registered user {username}")
                else:
                    response = f"User {username} already exists"
                    logging.info(f"Registration failed - user exists: {username}")
                try:
                    client_socket.send(response.encode('utf-8'))
                except socket.error as e:
                    logging.error(f"Error sending registration response: {e}")
                if response == f"User {username} registered successfully":
                    clients[client_socket] = username
                    # Notify all clients about the new user
                    notify_all_clients(f"User {username} has joined the chat")
                    # Send the updated user list to all clients
                    notify_all_clients("list_users")
        except Exception as e:
            logging.error(f"Error during login/registration: {e}")
            break



    # Once logged in, listen for chat or file transfer requests
    while True:
        try:
            message = client_socket.recv(BUFFER_SIZE).decode('utf-8')
            if not message:
                break  # Client disconnected

            # Validate message format
            message_parts = message.split(',')
            if len(message_parts) < 2:
                continue

            command = message_parts[0]

            if command == 'chat':
                # Validate session timeout
                if clients[client_socket] in user_sessions:
                    last_activity = user_sessions[clients[client_socket]]
                    if datetime.now() - last_activity > timedelta(seconds=SESSION_TIMEOUT):
                        client_socket.send("Session expired. Please login again.".encode('utf-8'))
                        break
                    user_sessions[clients[client_socket]] = datetime.now()

                # Sanitize message
                if len(message_parts) < 3:
                    continue
                recipient = sanitize_input(message_parts[1])
                msg = sanitize_input(message_parts[2], MAX_MESSAGE_LENGTH)

                if not msg or not recipient:
                    continue

                current_user = clients[client_socket]

                # Check if both users are in a valid chat session
                if recipient in chat_sessions and chat_sessions[recipient] == current_user:
                    # Find the recipient's socket
                    recipient_socket = None
                    for client, user in clients.items():
                        if user == recipient:
                            recipient_socket = client
                            break

                    if recipient_socket:
                        try:
                            recipient_socket.send(f"{current_user}: {msg}".encode('utf-8'))
                            logging.info(f"Message from {current_user} to {recipient}: {msg}")
                        except socket.error as e:
                            logging.error(f"Error sending chat message: {e}")
                    else:
                        try:
                            client_socket.send(f"Failed to send message. Recipient {recipient} not found.".encode('utf-8'))
                        except socket.error as e:
                            logging.error(f"Error sending failure message: {e}")
                else:
                    try:
                        client_socket.send(f"Failed to send message. Ensure you are in a chat session with {recipient}.".encode('utf-8'))
                    except socket.error as e:
                        logging.error(f"Error sending failure message: {e}")

            elif command == 'list_users':
                # Retrieve the username of the requesting client
                requesting_user = clients[client_socket]

                # Exclude the requesting user from the list
                connected_users = [
                    user for client, user in clients.items() if user != requesting_user
                ]
                
                # Format the response properly
                if connected_users:
                    response = f"Connected users: {', '.join(connected_users)}"
                else:
                    response = "Connected users: No other users connected"

                try:
                    client_socket.send(response.encode('utf-8'))
                    logging.info(f"Sent user list to {requesting_user}")
                except socket.error as e:
                    logging.error(f"Error sending user list: {e}")

            elif command == 'switch_user':
                if len(message_parts) != 2:
                    continue

                selected_user = sanitize_input(message_parts[1])
                current_user = clients[client_socket]

                if selected_user in clients.values() and selected_user != current_user:
                    # Update chat sessions for both users
                    chat_sessions[current_user] = selected_user
                    chat_sessions[selected_user] = current_user

                    try:
                        client_socket.send(f"Switched to chat with {selected_user}".encode('utf-8'))
                    except socket.error as e:
                        logging.error(f"Error sending switch confirmation: {e}")
                    logging.info(f"{current_user} switched to chat with {selected_user}")

                    # Notify the other user
                    for client, user in clients.items():
                        if user == selected_user:
                            try:
                                client.send(f"{current_user} has started a chat with you.".encode('utf-8'))
                            except socket.error as e:
                                logging.error(f"Error notifying user {selected_user}: {e}")
                            break
                else:
                    try:
                        client_socket.send(f"User {selected_user} not found or invalid.".encode('utf-8'))
                    except socket.error as e:
                        logging.error(f"Error sending user not found message: {e}")

            elif command == 'file':
                if len(message_parts) != 3:
                    continue

                recipient = sanitize_input(message_parts[1])
                file_name = sanitize_input(message_parts[2])
                file_data = client_socket.recv(BUFFER_SIZE)
                with open(f"received_{file_name}", "wb") as f:
                    f.write(file_data)
                logging.info(f"File {file_name} received from {clients[client_socket]}")

        except Exception as e:
            logging.error(f"Error during client communication: {e}")
            break

    # Clean up connections
    logging.info(f"Connection with {client_address} closed.")
    username = clients.pop(client_socket, None)
    if username:
        if username in chat_sessions:
            # Notify chat partner about disconnection
            partner = chat_sessions[username]
            if partner in chat_sessions:
                del chat_sessions[partner]
            del chat_sessions[username]
            
        # Notify all clients about disconnection
        notify_all_clients(f"User {username} has disconnected")
        
    client_socket.close()

def start_server():
    server_socket = None
    try:
        server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

        # Modified SSL context setup
        ssl_context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        ssl_context.load_cert_chain(certfile="cert.pem", keyfile="key.pem")
        ssl_context.check_hostname = False
        ssl_context.verify_mode = ssl.CERT_NONE

        server_socket.bind((SERVER_HOST, SERVER_PORT))
        server_socket.listen(5)
        server_socket = ssl_context.wrap_socket(server_socket, server_side=True)
        logging.info(f"Secure server is listening on {SERVER_HOST}:{SERVER_PORT}")

        # Modified handle_client function to use try-except for SSL handshake
        def handle_client_wrapper(client_socket, client_address):
            try:
                client_socket.do_handshake()
                handle_client(client_socket, client_address)
            except ssl.SSLError as e:
                logging.error(f"SSL Handshake failed: {e}")
            except Exception as e:
                logging.error(f"Error handling client: {e}")
            finally:
                try:
                    client_socket.close()
                except:
                    pass

        inputs = [server_socket]
        while inputs:
            try:
                readable, _, _ = select.select(inputs, [], [])
                for sock in readable:
                    if sock is server_socket:
                        try:
                            client_socket, client_address = server_socket.accept()
                            logging.info(f"New connection accepted from {client_address}")
                            thread = threading.Thread(target=handle_client_wrapper, 
                                                   args=(client_socket, client_address))
                            thread.start()
                        except ssl.SSLError as e:
                            logging.error(f"SSL error during accept: {e}")
                        except Exception as e:
                            logging.error(f"Error accepting connection: {e}")
                    else:
                        inputs.remove(sock)
            except Exception as e:
                logging.error(f"Error in server main loop: {e}")
                continue

    except ssl.SSLError as ssl_error:
        logging.error(f"SSL error: {ssl_error}")
    except Exception as e:
        logging.error(f"Failed to start server: {e}")
    finally:
        if server_socket:
            server_socket.close()

if __name__ == "__main__":
    start_server()