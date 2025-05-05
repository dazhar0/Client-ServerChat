import asyncio
import json
import os
from datetime import datetime
from http.server import BaseHTTPRequestHandler, HTTPServer
import websockets
from websockets.exceptions import InvalidHandshake, InvalidMessage

clients = {}

# Health check HTTP handler
class HealthCheckHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"Server is alive.")
        else:
            self.send_response(404)
            self.end_headers()

# Broadcast updated list of connected users
async def notify_presence():
    online_users = list(clients.keys())
    message = json.dumps({"type": "presence", "users": online_users})
    await asyncio.gather(*[client.send(message) for client in clients.values()])

# Handle incoming WebSocket connections
async def handler(websocket):
    try:
        # First message must be the login with username
        data = await websocket.recv()
        login = json.loads(data)
        username = login.get("username", "anonymous")
        clients[username] = websocket

        await notify_presence()

        async for msg in websocket:
            try:
                data = json.loads(msg)
                if data["type"] == "message":
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
                    await asyncio.gather(
                        *[client.send(notify) for client in clients.values() if client != websocket]
                    )
            except Exception as e:
                print("Error handling message:", e)

    except websockets.exceptions.ConnectionClosed:
        pass
    except (InvalidHandshake, InvalidMessage):
        print("Invalid connection attempt - probably a health check.")
    finally:
        if 'username' in locals() and username in clients:
            del clients[username]
            await notify_presence()

async def main():
    port = int(os.getenv("PORT", 10000))  # Use PORT env var if set, default to 8080

    loop = asyncio.get_event_loop()

    # Start HTTP health check server in background
    server = HTTPServer(("0.0.0.0", port), HealthCheckHandler)
    loop.run_in_executor(None, server.serve_forever)
    print(f"Health check HTTP server running on port {port}...")

    # Start WebSocket server
    async with websockets.serve(handler, "0.0.0.0", port):
        print(f"WebSocket server running on port {port}...")
        await asyncio.Future()  # Run forever

if __name__ == "__main__":
    asyncio.run(main())
