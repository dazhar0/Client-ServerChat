import asyncio
import websockets
import time

# Simple credentials check (for demonstration)
valid_credentials = {
    "Danny": "dannyboy",
    "Adrian": "mypassword"
}

# Active clients {username: websocket}
active_clients = {}

# Message rate tracking {username: [timestamps]}
message_timestamps = {}

# Rate Limiting Feature Setting
MAX_MESSAGES = 5  # Maximum messages allowed
TIME_FRAME = 10  # Time window in seconds

async def ws_server(websocket, path=None):
    username = None  
    try:
        # Requesting username
        await websocket.send("Enter your username:")
        username = await websocket.recv()

        # Requesting password
        await websocket.send("Enter your password:")
        password = await websocket.recv()

        # Validating credentials
        if username in valid_credentials and valid_credentials[username] == password:
            await websocket.send(f"Welcome {username}, authentication successful!")
        else:
            await websocket.send("Invalid username or password. Disconnecting...")
            return  

        # Register user
        active_clients[username] = websocket
        message_timestamps[username] = []  # Initialize message log
        print(f"Client {username} connected.")

        # Notify others
        await broadcast(f"{username} has joined the chat.", websocket)

        await websocket.send("You can now start chatting!")

        # Ping-Pong Heartbeat Task
        asyncio.create_task(heartbeat(websocket, username))

        # Chat loop
        while True:
            message = await websocket.recv()

            # Rate limit check
            current_time = time.time()
            message_timestamps[username] = [
                t for t in message_timestamps[username] if current_time - t < TIME_FRAME
            ]

            if len(message_timestamps[username]) >= MAX_MESSAGES:
                await websocket.send("Rate limit exceeded. Please wait.")
                continue  # Skip sending

            message_timestamps[username].append(current_time)
            print(f"{username}: {message}")

            # Broadcast message
            await broadcast(f"{username}: {message}", websocket)

    except websockets.ConnectionClosed:
        print(f"Client {username} disconnected.")
    finally:
        if username in active_clients:
            del active_clients[username]
            del message_timestamps[username]
            await broadcast(f"{username} has left the chat.", None)

async def broadcast(message, sender_websocket):
    """Send a message to all connected clients except the sender."""
    await asyncio.gather(*[
        client.send(message)
        for user, client in active_clients.items()
        if client != sender_websocket
    ], return_exceptions=True)

async def heartbeat(websocket, username):
    """Sends periodic pings to check if the client is still connected."""
    try:
        while username in active_clients:
            await websocket.ping()
            await asyncio.sleep(10)  # Ping every 10 seconds
    except websockets.ConnectionClosed:
        pass

async def main():
    server = await websockets.serve(ws_server, "localhost", 8080)
    print("WebSocket server started on ws://localhost:8080")
    await server.wait_closed()

if __name__ == "__main__":
    asyncio.run(main())
