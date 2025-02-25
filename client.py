import asyncio  # Importing the asyncio library for asynchronous programming
import websockets  # Importing the websockets library to handle WebSocket connections
import ssl  # Importing the ssl library for secure connections

# Asynchronous function to connect to the WebSocket server
async def connect_to_server():
    # Creating an SSL context for a secure connection
    ssl_context = ssl.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
    ssl_context.check_hostname = False  # Disabling hostname checking
    ssl_context.verify_mode = ssl.CERT_NONE  # Disabling certificate verification

    # Infinite loop to keep trying to connect to the server
    while True:
        try:
            # Establishing a secure WebSocket connection
            async with websockets.connect("wss://<YOURIPADDRESS>:8080", ssl=ssl_context) as websocket:
                print("You are connected to secure server now.")

                # Receiving and printing the initial message from the server
                print(await websocket.recv())  
                # Prompting the user to enter a username and sending it to the server
                username = input("Enter username: ")
                await websocket.send(username)

                # Receiving and printing the server's response to the username
                print(await websocket.recv())  
                # Prompting the user to enter a password and sending it to the server
                password = input("Enter password: ")
                await websocket.send(password)

                # Receiving and printing the server's response to the password
                response = await websocket.recv()
                print(response)
                # If authentication fails, exit the function
                if "authentication successful" not in response:
                    return  

                # Receiving and printing the next message from the server
                print(await websocket.recv())  

                # Starting the heartbeat task to keep the connection alive
                asyncio.create_task(heartbeat(websocket))

                # Loop to handle user input and send messages to the server
                while True:
                    message = input("Chat here (or type 'exit' to disconnect): ")
                    if message.lower() == "exit":
                        break

                    # Sending the user's message to the server and printing the response
                    await websocket.send(message)
                    response = await websocket.recv()
                    print(response)

        # Handling connection closed errors and attempting to reconnect after 5 seconds
        except websockets.exceptions.ConnectionClosedError:
            print("The Connection lost. Reconnecting in 5 seconds...")
            await asyncio.sleep(5)  

        # Handling other exceptions and attempting to reconnect after 5 seconds
        except Exception as e:
            print(f"Error: {e}. Reconnecting in 5 seconds...")
            await asyncio.sleep(5)

# Asynchronous function to send heartbeat messages to the server
async def heartbeat(websocket):
    try:
        while True:
            await asyncio.sleep(10)  # Waiting for 10 seconds between heartbeats
            pong = await websocket.ping()  # Sending a ping to the server
            await pong  # Waiting for the pong response
    except websockets.ConnectionClosed:
        pass  # Handling connection closed exceptions

# Running the connect_to_server function using asyncio
asyncio.run(connect_to_server())