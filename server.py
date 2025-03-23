import socket
import threading
import os

SERVER_HOST = '127.0.0.1'
SERVER_PORT = 12345
BUFFER_SIZE = 1024

clients = {}  # Track clients and their usernames
usernames = {}  # Track usernames and their passwords
chat_sessions = {}  # Track active chat sessions

# Function to handle each client
def handle_client(client_socket, client_address):
    print(f"New connection from {client_address}")

    # Receive login or registration request
    while True:
        message = client_socket.recv(BUFFER_SIZE).decode('utf-8')
        if message.startswith('login'):
            username, password = message.split(',')[1], message.split(',')[2]
            print(f"Login attempt by {username}")

            if username in usernames:
                if usernames[username] == password:
                    response = "authentication successful"
                else:
                    response = "authentication failed"
            else:
                response = "user not found"
            client_socket.send(response.encode('utf-8'))
            if response == "authentication successful":
                clients[client_socket] = username
                break

        elif message.startswith('register'):
            username, password = message.split(',')[1], message.split(',')[2]
            print(f"Registering user {username}")

            if username not in usernames:
                usernames[username] = password
                response = f"User {username} registered successfully"
            else:
                response = f"User {username} already exists"
            client_socket.send(response.encode('utf-8'))

    # Once logged in, listen for chat or file transfer requests
    while True:
        message = client_socket.recv(BUFFER_SIZE).decode('utf-8')

        if message.startswith('chat'):
            _, recipient, msg = message.split(',', 2)
            if recipient in clients.values() and recipient in chat_sessions:
                # Ensure both users are in chat session
                if clients[client_socket] == chat_sessions[recipient]:
                    client_socket.send(f"Message sent to {recipient}: {msg}".encode('utf-8'))
                else:
                    chat_sessions[recipient].send(f"{clients[client_socket]}: {msg}".encode('utf-8'))
                    print(f"Message sent to {recipient}: {msg}")
            else:
                print(f"User {recipient} is not in the session")

        elif message.startswith('list_users'):
            connected_users = ", ".join([clients[client] for client in clients])
            client_socket.send(f"Connected users: {connected_users}".encode('utf-8'))

        elif message.startswith('switch_user'):
            selected_user = message.split(',')[1]
            if selected_user in clients.values():
                chat_sessions[clients[client_socket]] = client_socket
                client_socket.send(f"Switched to chat with {selected_user}".encode('utf-8'))
                print(f"Switched to chat with {selected_user}")
            else:
                client_socket.send(f"User {selected_user} not found".encode('utf-8'))

        elif message.startswith('file'):
            _, recipient, file_name = message.split(',', 2)
            file_data = client_socket.recv(BUFFER_SIZE)
            with open(f"received_{file_name}", "wb") as f:
                f.write(file_data)
            print(f"File {file_name} received from {clients[client_socket]}")

    # Clean up connections
    client_socket.close()

# Set up the server
def start_server():
    server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_socket.bind((SERVER_HOST, SERVER_PORT))
    server_socket.listen(5)
    print(f"Server is listening on {SERVER_HOST}:{SERVER_PORT}")

    while True:
        client_socket, client_address = server_socket.accept()
        thread = threading.Thread(target=handle_client, args=(client_socket, client_address))
        thread.start()

start_server()
