import asyncio
import websockets
import json
import os

connected_clients = {}

async def handler(websocket, path):
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

            if data.get("type") == "message":
                await broadcast_message(username, data["message"])

    except websockets.exceptions.ConnectionClosed:
        print(f"{username} disconnected.")
    finally:
        connected_clients.pop(username, None)
        await notify_presence()

async def broadcast_message(sender, message):
    data = json.dumps({
        "type": "message",
        "username": sender,
        "message": message
    })
    await asyncio.gather(*[
        ws.send(data) for uname, ws in connected_clients.items() if uname != sender
    ])

async def notify_presence():
    data = json.dumps({
        "type": "presence",
        "users": list(connected_clients.keys())
    })
    await asyncio.gather(*[ws.send(data) for ws in connected_clients.values()])

start_server = websockets.serve(
    handler,
    "0.0.0.0",
    int(os.environ.get("PORT", 10000)),
    ping_interval=None  # Prevent idle disconnects on Render
)

asyncio.get_event_loop().run_until_complete(start_server)
asyncio.get_event_loop().run_forever()
