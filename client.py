import asyncio
import websockets
import ssl

async def connect_to_server():
    # Disable SSL verification (ONLY FOR LOCAL TESTING)
    ssl_context = ssl._create_unverified_context()

    while True:
        try:
            async with websockets.connect("wss://localhost:8080", ssl=ssl_context) as websocket:
                print("You are connected to secure server now.")

                print(await websocket.recv())  
                username = input("Enter username: ")
                await websocket.send(username)

                print(await websocket.recv())  
                password = input("Enter password: ")
                await websocket.send(password)

                response = await websocket.recv()
                print(response)
                if "authentication successful" not in response:
                    return  

                print(await websocket.recv())  

                asyncio.create_task(heartbeat(websocket))

                while True:
                    message = input("Chat here (or type 'exit' to disconnect): ")
                    if message.lower() == "exit":
                        break

                    await websocket.send(message)
                    response = await websocket.recv()
                    print(response)

        except websockets.exceptions.ConnectionClosedError:
            print("The Connection lost. Reconnecting in 5 seconds...")
            await asyncio.sleep(5)  

        except Exception as e:
            print(f"Error: {e}. Reconnecting in 5 seconds...")
            await asyncio.sleep(5)

async def heartbeat(websocket):
    try:
        while True:
            await asyncio.sleep(10)
            pong = await websocket.ping()
            await pong
    except websockets.ConnectionClosed:
        pass

asyncio.run(connect_to_server())
