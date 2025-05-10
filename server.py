import asyncio
import websockets
import json

connected_clients = {}

async def handler(websocket, path):
    # Get user identity from query param or init message
    try:
        init_msg = await websocket.recv()
        user_info = json.loads(init_msg)
        username = user_info.get("username", "unknown")

        connected_clients[username] = websocket
        print(f"{username} connected.")
        
        # Notify others
        await notify_presence()

        while True:
            msg = await websocket.recv()
            data = json.loads(msg)

            # Simple broadcast logic
            for client in connected_clients.values():
                if client != websocket:
                    await client.send(json.dumps({
                        "from": username,
                        "message": data["message"]
                    }))
    except websockets.exceptions.ConnectionClosed:
        print(f"{username} disconnected.")
    finally:
        connected_clients.pop(username, None)
        await notify_presence()

async def notify_presence():
    online_users = list(connected_clients.keys())
    data = json.dumps({"type": "presence", "users": online_users})
    await asyncio.gather(*[ws.send(data) for ws in connected_clients.values()])

start_server = websockets.serve(handler, "0.0.0.0", 10000)

asyncio.get_event_loop().run_until_complete(start_server)
asyncio.get_event_loop().run_forever()
