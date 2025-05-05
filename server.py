import asyncio
import json
import os
from datetime import datetime
import websockets
from websockets.exceptions import ConnectionClosed

clients = {}

# Broadcast updated list of connected users
async def notify_presence():
    users = list(clients.keys())
    message = json.dumps({"type": "presence", "users": users})
    await asyncio.gather(*[ws.send(message) for ws in clients.values() if not ws.closed])

# Handle client communication
async def handler(websocket):
    username = None
    try:
        # First message should be login
        data = await websocket.recv()
        login = json.loads(data)
        username = login.get("username")
        if not username:
            await websocket.send(json.dumps({"type": "error", "message": "Username required"}))
            return

        clients[username] = websocket
        await notify_presence()

        async for message in websocket:
            try:
                data = json.loads(message)
                if data["type"] == "message":
                    response = json.dumps({
                        "type": "message",
                        "from": username,
                        "message": data["message"],
                        "timestamp": datetime.utcnow().isoformat()
                    })
                    await asyncio.gather(*[
                        client.send(response) for client in clients.values() if not client.closed
                    ])
                elif data["type"] == "typing":
                    notify = json.dumps({
                        "type": "typing",
                        "user": username
                    })
                    await asyncio.gather(*[
                        client.send(notify)
                        for client in clients.values()
                        if client != websocket and not client.closed
                    ])
            except Exception as e:
                print("Error processing message:", e)

    except ConnectionClosed:
        pass
    finally:
        if username and username in clients:
            del clients[username]
            await notify_presence()

# Entry point
async def main():
    port = int(os.getenv("PORT", 10000))  # Render uses env var "PORT"
    print(f"Starting WebSocket server on port {port}...")
    async with websockets.serve(handler, "0.0.0.0", port):
        await asyncio.Future()

if __name__ == "__main__":
    asyncio.run(main())
