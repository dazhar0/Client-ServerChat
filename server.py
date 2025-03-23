import socket
import threading
import bcrypt

SERVER_HOST = '127.0.0.1'
SERVER_PORT = 12345

user_sessions = {}
registered_users = {}

# Function to send message to all users (notify them about a new connection)
def notify_users(message):
    for user_socket in user_sessions.values():
        try:
            user_socket.send(message.encode('utf-8'))
        except Exception as e:
            print(f"Error notifying users: {e}")

def handle_client(client_socket, client_address):
    print(f"Connection established with {client_address}")
    
    current_user = None
    
    while True:
        try:
            message = client_socket.recv(1024).decode('utf-8')
            if not message:
                break

            message_parts = message.split(',', 1)
            command = message_parts[0]

            if command == "login":
                username, password = message_parts[1].split(",", 1)
                if username not in registered_users:
                    client_socket.send(f"User {username} is not registered.".encode('utf-8'))
                else:
                    # Compare hashed password
                    stored_hash = registered_users[username]
                    if bcrypt.checkpw(password.encode('utf-8'), stored_hash):
                        if username in user_sessions:
                            client_socket.send(f"Username {username} is already logged in.".encode('utf-8'))
                        else:
                            user_sessions[username] = client_socket
                            current_user = username
                            client_socket.send(f"Welcome {username}, authentication successful!".encode('utf-8'))
                            notify_users(f"{username} has connected.")
                            print(f"{username} connected.")
                            # Notify the new user of all connected users
                            connected_users = ", ".join(user_sessions.keys())
                            client_socket.send(f"Connected users: {connected_users}".encode('utf-8'))
                    else:
                        client_socket.send(f"Incorrect password for {username}.".encode('utf-8'))

            elif command == "register":
                username, password = message_parts[1].split(",", 1)
                if username in registered_users:
                    client_socket.send(f"Username {username} is already registered.".encode('utf-8'))
                else:
                    # Hash the password
                    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt())
                    registered_users[username] = hashed_password
                    client_socket.send(f"User {username} registered successfully.".encode('utf-8'))

            elif command == "chat":
                if current_user:
                    recipient, message_content = message_parts[1].split(",", 1)
                    if recipient in user_sessions:
                        user_sessions[recipient].send(f"{current_user}: {message_content}".encode('utf-8'))  # Send message to recipient
                        client_socket.send(f"Message sent to {recipient}: {message_content}".encode('utf-8'))
                    else:
                        client_socket.send(f"User {recipient} not found.".encode('utf-8'))

            elif command == "switch_user":
                target_user = message_parts[1]
                if target_user in user_sessions:
                    current_user = target_user
                    client_socket.send(f"Switched to chat with {target_user}".encode('utf-8'))
                else:
                    client_socket.send(f"User {target_user} not found.".encode('utf-8'))

            elif command == "list_users":
                connected_users = ", ".join(user_sessions.keys())
                client_socket.send(f"Connected users: {connected_users}".encode('utf-8'))

            else:
                client_socket.send("Invalid command.".encode('utf-8'))

        except Exception as e:
            print(f"Error handling client: {e}")
            break

    if current_user:
        del user_sessions[current_user]
        notify_users(f"{current_user} has disconnected.")
    client_socket.close()
    print(f"Connection closed for {client_address}")

def start_server():
    server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_socket.bind((SERVER_HOST, SERVER_PORT))
    server_socket.listen(5)
    print(f"Server started on {SERVER_HOST}:{SERVER_PORT}")

    while True:
        client_socket, client_address = server_socket.accept()
        client_thread = threading.Thread(target=handle_client, args=(client_socket, client_address))
        client_thread.start()

if __name__ == "__main__":
    start_server()
