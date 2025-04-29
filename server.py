# server.py
import asyncio
import json
import websockets
import ssl
from datetime import datetime

clients = {}

async def notify_presence():
    online_users = list(clients.keys())
    message = json.dumps({"type": "presence", "users": online_users})
    await asyncio.gather(*[client.send(message) for client in clients.values()])

async def handler(websocket, path):
    try:
        # Receive username on connect
        data = await websocket.recv()
        login = json.loads(data)
        username = login.get("username", "anonymous")
        clients[username] = websocket

        await notify_presence()

        async for msg in websocket:
            try:
                data = json.loads(msg)
                if data["type"] == "message":
                    # Broadcast to all users
                    payload = json.dumps({
                        "type": "message",
                        "from": username,
                        "message": data["message"],
                        "timestamp": datetime.utcnow().isoformat()
                    })
                    await asyncio.gather(*[client.send(payload) for client in clients.values()])
                elif data["type"] == "typing":
                    notify = json.dumps({
                        "type": "typing",
                        "user": username
                    })
                    await asyncio.gather(*[client.send(notify) for client in clients.values() if client != websocket])
            except Exception as e:
                print("Error handling message:", e)
    except websockets.exceptions.ConnectionClosed:
        pass
    finally:
        # Cleanup on disconnect
        if username in clients:
            del clients[username]
            await notify_presence()

# SSL (for Render/Fly.io use built-in HTTPS termination, no cert needed here)
start_server = websockets.serve(handler, "0.0.0.0", 8765)

print("WebSocket server started on port 8765...")

asyncio.get_event_loop().run_until_complete(start_server)
asyncio.get_event_loop().run_forever()
