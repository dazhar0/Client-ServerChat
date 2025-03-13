import asyncio
import websockets
import ssl
import time
import bcrypt

valid_credentials = {
    "Danny": bcrypt.hashpw("dannyboy".encode(), bcrypt.gensalt()).decode(),
    "Adrian": bcrypt.hashpw("mypassword".encode(), bcrypt.gensalt()).decode(),
    "test1": bcrypt.hashpw("testing".encode(), bcrypt.gensalt()).decode()
}

active_clients = {}
message_timestamps = {}
MAX_MESSAGES = 3  
TIME_FRAME = 5  

async def ws_server(websocket, path=None):
    username = None  
    try:
        # Remove the prompt sending from the server
        credentials = await websocket.recv()
        action, username, password = credentials.split(',')

        if action == "create":
            if username in valid_credentials:
                await websocket.send("Username already exists. Disconnecting...")
                return
            hashed_password = bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()
            valid_credentials[username] = hashed_password
            await websocket.send(f"Account created successfully for {username}!")
        elif action == "login":
            if username in valid_credentials and bcrypt.checkpw(password.encode(), valid_credentials[username].encode()):
                await websocket.send(f"Welcome {username}, authentication successful!")
            else:
                await websocket.send("Invalid username or password. Disconnecting...")
                return
        else:
            await websocket.send("Invalid action. Disconnecting...")
            return

        active_clients[username] = websocket
        message_timestamps[username] = []
        print(f"Client {username} connected.")

        await broadcast(f"{username} has joined the chat.", websocket)
        await websocket.send("You can now start chatting!")

        asyncio.create_task(heartbeat(websocket, username))

        while True:
            message = await websocket.recv()
            if message.lower() == "exit":
                await websocket.send("You have been disconnected.")
                break

            current_time = time.time()
            message_timestamps[username] = [
                t for t in message_timestamps[username] if current_time - t < TIME_FRAME
            ]

            if len(message_timestamps[username]) >= MAX_MESSAGES:
                await websocket.send("Rate limit exceeded. Please wait.")
                continue  

            message_timestamps[username].append(current_time)
            print(f"{username}: {message}")

            await broadcast(f"{username}: {message}", websocket)

    except websockets.ConnectionClosed:
        print(f"Client {username} disconnected.")
    finally:
        if username in active_clients:
            del active_clients[username]
            del message_timestamps[username]
            await broadcast(f"{username} has left the chat.", None)

async def broadcast(message, sender_websocket):
    await asyncio.gather(*[
        client.send(message)
        for user, client in active_clients.items()
        if client != sender_websocket
    ], return_exceptions=True)

async def heartbeat(websocket, username):
    try:
        while username in active_clients:
            await websocket.ping()
            await asyncio.sleep(10)
    except websockets.ConnectionClosed:
        pass

async def main():
    ssl_context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    ssl_context.load_cert_chain(certfile="cert.pem", keyfile="key.pem")  
    server = await websockets.serve(ws_server, "0.0.0.0", 8080, ssl=ssl_context)  
    print("Secure WebSocket server started on wss://<YOURIPADDRESS>:8080")
    await server.wait_closed()

if __name__ == "__main__":
    asyncio.run(main())
