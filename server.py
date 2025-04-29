import asyncio
import json
import websockets
from websockets.exceptions import InvalidHandshake, InvalidMessage
from datetime import datetime
from http.server import BaseHTTPRequestHandler, HTTPServer
import threading

clients = {}

async def notify_presence():
    online_users = list(clients.keys())
    message = json.dumps({"type": "presence", "users": online_users})
    await asyncio.gather(*[client.send(message) for client in clients.values()])

async def handler(websocket):
    try:
        # First message is expected to be the username JSON
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
        # Log and ignore invalid handshakes (HEAD, bad requests)
        print("Invalid connection attempt - probably a health check.")
    finally:
        if 'username' in locals() and username in clients:
            del clients[username]
            await notify_presence()

# --- Health Check HTTP Server ---

class HealthCheckHandler(BaseHTTPRequestHandler):
    def do_HEAD(self):
        self.send_response(200)
        self.end_headers()

    def do_GET(self):
        self.send_response(200)
        self.end_headers()
        self.wfile.write(b"Server is alive.")

    def log_message(self, format, *args):
        return  # Silence HTTP server logging

def start_http_server():
    server = HTTPServer(("0.0.0.0", 8080), HealthCheckHandler)
    print("HTTP health server running on port 8080...")
    server.serve_forever()

async def main():
    # Start health check server in a background thread
    threading.Thread(target=start_http_server, daemon=True).start()

    # Start WebSocket server
    async with websockets.serve(handler, "0.0.0.0", 8765):
        print("WebSocket server running on port 8765...")
        await asyncio.Future()  # Run forever

if __name__ == "__main__":
    asyncio.run(main())
