import socket
import threading

# Server configuration
HOST = '127.0.0.1'
PORT = 12345

# Dictionary to keep track of connected clients
clients = {}

def broadcast(message, sender_socket):
    for client_socket in clients.values():
        if client_socket != sender_socket:
            try:
                client_socket.send(message)
            except:
                client_socket.close()
                remove_client(client_socket)

def handle_client(client_socket, client_address):
    while True:
        try:
            message = client_socket.recv(1024)
            if message:
                if message.startswith(b"PRIVATE:"):
                    _, recipient, private_message = message.decode().split(":", 2)
                    if recipient in clients:
                        clients[recipient].send(f"PRIVATE:{recipient}:{private_message}".encode())
                elif message.startswith(b"FILE:"):
                    _, recipient, file_name = message.decode().split(":", 2)
                    file_data = client_socket.recv(1024)
                    if recipient in clients:
                        clients[recipient].send(f"FILE:{recipient}:{file_name}".encode())
                        clients[recipient].send(file_data)
                elif message.startswith(b"ONLINE_USERS"):
                    online_users = ",".join(clients.keys())
                    client_socket.send(f"ONLINE_USERS:{online_users}".encode())
                else:
                    broadcast(message, client_socket)
            else:
                remove_client(client_socket)
                break
        except:
            remove_client(client_socket)
            break

def remove_client(client_socket):
    for username, client in clients.items():
        if client == client_socket:
            del clients[username]
            break

def start_server():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.bind((HOST, PORT))
    server.listen()
    print(f"Server running on {HOST}:{PORT}")

    while True:
        client_socket, client_address = server.accept()
        print(f"New connection from {client_address}")
        client_socket.send("USERNAME".encode())
        username = client_socket.recv(1024).decode()
        clients[username] = client_socket
        broadcast(f"ONLINE_USERS:{','.join(clients.keys())}".encode(), None)
        thread = threading.Thread(target=handle_client, args=(client_socket, client_address))
        thread.start()

if __name__ == "__main__":
    start_server()