import asyncio
import websockets
import json
import os

connected_clients = {}

async def notify_presence():
    online_users = list(connected_clients.keys())
    data = json.dumps({"type": "presence", "users": online_users})
    await asyncio.gather(*[ws.send(data) for ws in connected_clients.values()])

async def handler(websocket):
    try:
        init_msg = await websocket.recv()
        user_info = json.loads(init_msg)
        username = user_info.get("username", "unknown")

        connected_clients[username] = websocket
        print(f"{username} connected.")

        await notify_presence()

        while True:
            msg = await websocket.recv()
            data = json.loads(msg)

            if data["type"] == "message":
                for client in connected_clients.values():
                    if client != websocket:
                        await client.send(json.dumps({
                            "type": "message",
                            "username": username,
                            "message": data["message"]
                        }))
    except websockets.exceptions.ConnectionClosed:
        print(f"{username} disconnected.")
    finally:
        connected_clients.pop(username, None)
        await notify_presence()

async def main():
    port = int(os.environ.get("PORT", 10000))
    async with websockets.serve(handler, "0.0.0.0", port):
        print(f"WebSocket server started on port {port}")
        await asyncio.Future()  # run forever

if __name__ == "__main__":
    asyncio.run(main())
