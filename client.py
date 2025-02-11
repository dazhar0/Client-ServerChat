import asyncio
import websockets

async def connect_to_server():
    while True:
        try:
            async with websockets.connect("ws://localhost:8080") as websocket:
                print("Connected to server.")

                # Authentication
                print(await websocket.recv())  # "Enter username:"
                username = input("Enter username: ")
                await websocket.send(username)

                print(await websocket.recv())  # "Enter password:"
                password = input("Enter password: ")
                await websocket.send(password)

                # Authentication result
                response = await websocket.recv()
                print(response)
                if "authentication successful" not in response:
                    return  # Exit if auth fails

                print(await websocket.recv())  # "You can now start chatting!"

                # Start pinging to keep connection alive
                asyncio.create_task(heartbeat(websocket))

                # Chat loop
                while True:
                    message = input("Enter message (or 'exit' to quit): ")
                    if message.lower() == "exit":
                        break

                    await websocket.send(message)
                    response = await websocket.recv()
                    print(response)

        except websockets.exceptions.ConnectionClosedError:
            print("Connection lost. Reconnecting in 5 seconds...")
            await asyncio.sleep(5)  # Wait and retry connection

        except Exception as e:
            print(f"Error: {e}. Reconnecting in 5 seconds...")
            await asyncio.sleep(5)

async def heartbeat(websocket):
    """Keeps the connection alive by responding to pings."""
    try:
        while True:
            await asyncio.sleep(10)  # Check every 10 seconds
            pong = await websocket.ping()
            await pong  # Wait for the pong response
    except websockets.ConnectionClosed:
        pass  # Handle disconnection

asyncio.run(connect_to_server())
