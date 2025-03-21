import asyncio
import websockets
import ssl
import time
import bcrypt

# In-memory user database with bcrypt hashed passwords
valid_credentials = {
    "Danny": bcrypt.hashpw("dannyboy".encode(), bcrypt.gensalt()),
    "Adrian": bcrypt.hashpw("mypassword".encode(), bcrypt.gensalt()),
    "test1": bcrypt.hashpw("testing".encode(), bcrypt.gensalt())
}

# Active clients and rate-limiting data
active_clients = {}
message_timestamps = {}
MAX_MESSAGES = 3  # Number of messages allowed
TIME_FRAME = 5    # Time frame (in seconds) for rate limiting

async def ws_server(websocket, path=None):
    username = None  # Track the username for the connected client
    try:
        # Log connection establishment
        print(f"New connection attempt from: {websocket.remote_address}")

        # Handle login or account creation
        credentials = await websocket.recv()
        print(f"Received credentials: {credentials}")  # Debug log

        action, username, password = credentials.split(',')
        print(f"Action: {action}, Username: {username}, Password: {password}")  # Debug log

        if action == "create":
            if username in valid_credentials:
                await websocket.send("Username already exists. Disconnecting...")
                print(f"Username already exists: {username}")  # Debug log
                return
            hashed_password = bcrypt.hashpw(password.encode(), bcrypt.gensalt())
            valid_credentials[username] = hashed_password
            await websocket.send(f"Account created successfully for {username}!")
            print(f"Account created for {username}")  # Debug log
        elif action == "login":
            if username in valid_credentials and bcrypt.checkpw(password.encode(), valid_credentials[username]):
                await websocket.send(f"Welcome {username}, authentication successful!")
                print(f"User authenticated: {username}")  # Debug log
            else:
                await websocket.send("Invalid username or password. Disconnecting...")
                print(f"Authentication failed for {username}")  # Debug log
                return
        else:
            await websocket.send("Invalid action. Disconnecting...")
            print("Invalid action received.")  # Debug log
            return

        # Register the client
        active_clients[username] = websocket
        message_timestamps[username] = []
        print(f"Client {username} connected.")  # Debug log

        await broadcast(f"{username} has joined the chat.", websocket)
        await websocket.send("You can now start chatting!")

        asyncio.create_task(heartbeat(websocket, username))

        # Main message loop
        while True:
            message = await websocket.recv()
            print(f"Message received from {username}: {message}")  # Debug log
            if message.lower() == "exit":
                await websocket.send("You have been disconnected.")
                print(f"User {username} exited the chat.")  # Debug log
                break

            # Rate limiting logic
            current_time = time.time()
            message_timestamps[username] = [
                t for t in message_timestamps[username] if current_time - t < TIME_FRAME
            ]

            if len(message_timestamps[username]) >= MAX_MESSAGES:
                await websocket.send("Rate limit exceeded. Please wait a few seconds before sending more messages.")
                print(f"Rate limit exceeded for {username}.")  # Debug log
                continue

            message_timestamps[username].append(current_time)
            print(f"Broadcasting message from {username}: {message}")  # Debug log

            await broadcast(f"{username}: {message}", websocket)

    except websockets.ConnectionClosed:
        print(f"Client {username} disconnected.")  # Debug log
    except Exception as e:
        print(f"Error occurred with client {username}: {e}")  # Debug log
    finally:
        # Clean up on disconnect
        if username in active_clients:
            del active_clients[username]
            del message_timestamps[username]
            await broadcast(f"{username} has left the chat.", None)
            print(f"Cleaned up data for {username}.")  # Debug log

# Broadcast messages to all active clients except the sender
async def broadcast(message, sender_websocket):
    print(f"Broadcasting message: {message}")  # Debug log
    await asyncio.gather(*[
        client.send(message)
        for user, client in active_clients.items()
        if client != sender_websocket
    ], return_exceptions=True)

# Heartbeat function to keep connections alive
async def heartbeat(websocket, username):
    try:
        while username in active_clients:
            print(f"Sending heartbeat to {username}.")  # Debug log
            await websocket.ping()
            await asyncio.sleep(10)
    except websockets.ConnectionClosed:
        print(f"Heartbeat failed for {username}. Connection closed.")  # Debug log
        pass

# Start the WebSocket server
async def main():
    ssl_context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    ssl_context.load_cert_chain(certfile="cert.pem", keyfile="key.pem")  # Use valid certificate and key files
    server = await websockets.serve(ws_server, "172.29.2.42", 8080, ssl=ssl_context)
    print("Secure WebSocket server started on wss://localhost:8080")  # Debug log
    await server.wait_closed()

if __name__ == "__main__":
    asyncio.run(main())
