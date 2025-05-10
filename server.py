import asyncio
import websockets
import json
import os

# Dictionary to keep track of connected clients
connected_clients = {}

async def notify_presence():
    """Notify all connected clients about the presence of online users."""
    online_users = list(connected_clients.keys())
    data = json.dumps({"type": "presence", "users": online_users})
    await asyncio.gather(*[ws.send(data) for ws in connected_clients.values()])

async def handler(websocket, path):
    """Handle incoming WebSocket connections."""
    username = None
    try:
        # Receive the initial message with the username
        init_msg = await websocket.recv()
        user_info = json.loads(init_msg)
        username = user_info.get("username", "unknown")

        # Add the client to the connected clients dictionary
        connected_clients[username] = websocket
        print(f"{username} connected.")

        # Notify all clients of the new presence
        await notify_presence()

        while True:
            msg = await websocket.recv()
            data = json.loads(msg)

            if data["type"] == "message":
                # Broadcast message to all connected clients except the sender
                for client in connected_clients.values():
                    if client != websocket:
                        await client.send(json.dumps({
                            "type": "message",
                            "username": username,
                            "message": data["message"]
                        }))
    except websockets.exceptions.ConnectionClosed:
        # Handle the client disconnecting
        print(f"{username} disconnected.")
    finally:
        if username and username in connected_clients:
            # Remove the client from the connected_clients dictionary
            connected_clients.pop(username, None)
            # Notify all clients of the presence change
            await notify_presence()

async def main():
    """Start the WebSocket server."""
    port = int(os.environ.get("PORT", 10000))  # Get the port from environment or default to 10000
    async with websockets.serve(handler, "0.0.0.0", port):
        print(f"WebSocket server started on port {port}")
        await asyncio.Future()  # Run forever

if __name__ == "__main__":
    asyncio.run(main())
