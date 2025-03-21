import asyncio
import websockets
import ssl
import tkinter as tk
from tkinter import scrolledtext, messagebox
import bcrypt  # Updated for bcrypt password hashing

class LoginWindow:
    def __init__(self, root, on_login):
        self.root = root
        self.root.title("Login or Create Account")
        self.on_login = on_login

        self.login_button = tk.Button(root, text="Login", command=self.show_login_window)
        self.login_button.pack(padx=10, pady=10)

        self.create_button = tk.Button(root, text="Create Account", command=self.show_create_account_window)
        self.create_button.pack(padx=10, pady=10)

    def show_login_window(self):
        self.login_window = tk.Toplevel(self.root)
        self.login_window.title("Login")

        self.username_label = tk.Label(self.login_window, text="Username:")
        self.username_label.pack(padx=10, pady=5)
        self.username_entry = tk.Entry(self.login_window)
        self.username_entry.pack(padx=10, pady=5)

        self.password_label = tk.Label(self.login_window, text="Password:")
        self.password_label.pack(padx=10, pady=5)
        self.password_entry = tk.Entry(self.login_window, show='*')
        self.password_entry.pack(padx=10, pady=5)

        self.submit_button = tk.Button(self.login_window, text="Submit", command=self.login)
        self.submit_button.pack(padx=10, pady=10)

    def show_create_account_window(self):
        self.create_account_window = tk.Toplevel(self.root)
        self.create_account_window.title("Create Account")

        self.username_label = tk.Label(self.create_account_window, text="Username:")
        self.username_label.pack(padx=10, pady=5)
        self.username_entry = tk.Entry(self.create_account_window)
        self.username_entry.pack(padx=10, pady=5)

        self.password_label = tk.Label(self.create_account_window, text="Password:")
        self.password_label.pack(padx=10, pady=5)
        self.password_entry = tk.Entry(self.create_account_window, show='*')
        self.password_entry.pack(padx=10, pady=5)

        self.confirm_password_label = tk.Label(self.create_account_window, text="Confirm Password:")
        self.confirm_password_label.pack(padx=10, pady=5)
        self.confirm_password_entry = tk.Entry(self.create_account_window, show='*')
        self.confirm_password_entry.pack(padx=10, pady=5)

        self.submit_button = tk.Button(self.create_account_window, text="Submit", command=self.create_account)
        self.submit_button.pack(padx=10, pady=10)

    def login(self):
        username = self.username_entry.get()
        password = self.password_entry.get()
        print(f"Login attempt: Username={username}, Password={password}")  # Debug log
        self.root.after(0, asyncio.run, self.on_login("login", username, password))
        self.login_window.destroy()

    def create_account(self):
        username = self.username_entry.get()
        password = self.password_entry.get()
        confirm_password = self.confirm_password_entry.get()
        if password == confirm_password:
            print(f"Account creation attempt: Username={username}, Password={password}")  # Debug log
            self.root.after(0, asyncio.run, self.on_login("create", username, password))
            self.create_account_window.destroy()
        else:
            print(f"Password mismatch: Password={password}, Confirm Password={confirm_password}")  # Debug log
            messagebox.showerror("Error", "Passwords do not match")

class ChatClient:
    def __init__(self, root):
        self.root = root
        self.root.title("Chat Client")

        self.chat_area = scrolledtext.ScrolledText(root, wrap=tk.WORD)
        self.chat_area.pack(padx=10, pady=10, fill=tk.BOTH, expand=True)
        self.chat_area.config(state=tk.DISABLED)

        self.entry = tk.Entry(root)
        self.entry.pack(padx=10, pady=10, fill=tk.X, expand=True)
        self.entry.bind("<Return>", lambda event: asyncio.create_task(self.send_message()))
        self.entry.config(state=tk.DISABLED)

        self.ssl_context = ssl.create_default_context()
        # If using a self-signed certificate, load it here:
        self.ssl_context.load_verify_locations(cafile="cert.pem")

        self.websocket = None
        self.username = None

        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)

    async def connect_to_server(self, action, username, password):
        try:
            print(f"Connecting to server: Action={action}, Username={username}")  # Debug log
            async with websockets.connect("wss://localhost:8080", ssl=self.ssl_context) as websocket:
                self.websocket = websocket
                self.chat_area.config(state=tk.NORMAL)
                self.chat_area.insert(tk.END, "You are connected to a secure server.\n")
                self.chat_area.config(state=tk.DISABLED)

                # Hash password using bcrypt before transmission
                hashed_password = bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()
                credentials = f"{action},{username},{password}"
                print(f"Sending credentials: {credentials}")  # Debug log
                await websocket.send(credentials)

                response = await websocket.recv()
                print(f"Response from server: {response}")  # Debug log
                self.chat_area.config(state=tk.NORMAL)
                self.chat_area.insert(tk.END, response + "\n")
                self.chat_area.config(state=tk.DISABLED)

                # Validate login/account creation
                if "authentication successful" not in response.lower() and "account created successfully" not in response.lower():
                    messagebox.showerror("Error", "Login or Account Creation Failed. Please try again.")
                    return

                self.entry.config(state=tk.NORMAL)  # Enable the entry field after success
                await self.receive_messages()

        except websockets.exceptions.ConnectionClosedError:
            print("Connection closed unexpectedly. Attempting to reconnect...")  # Debug log
            self.chat_area.config(state=tk.NORMAL)
            self.chat_area.insert(tk.END, "Connection lost. Reconnecting...\n")
            self.chat_area.config(state=tk.DISABLED)
            await self.reconnect(action, username, password)

        except Exception as e:
            print(f"Error while connecting to server: {e}")  # Debug log
            self.chat_area.config(state=tk.NORMAL)
            self.chat_area.insert(tk.END, f"Error: {e}\n")
            self.chat_area.config(state=tk.DISABLED)

    async def receive_messages(self):
        try:
            while True:
                message = await self.websocket.recv()
                print(f"Message received: {message}")  # Debug log
                self.chat_area.config(state=tk.NORMAL)
                self.chat_area.insert(tk.END, message + "\n")
                self.chat_area.config(state=tk.DISABLED)
        except websockets.ConnectionClosedError:
            print("Connection closed during message reception.")  # Debug log

    async def reconnect(self, action, username, password):
        print("Reconnecting...")  # Debug log
        await asyncio.sleep(5)  # Wait before reconnecting
        await self.connect_to_server(action, username, password)

    async def send_message(self):
        message = self.entry.get()
        if message.lower() == "exit":
            print("User requested exit.")  # Debug log
            await self.websocket.send("exit")
            self.root.quit()
        else:
            print(f"Sending message: {message}")  # Debug log
            await self.websocket.send(message)
            self.entry.delete(0, tk.END)

    def on_closing(self):
        if self.websocket:
            print("Closing WebSocket connection.")  # Debug log
            asyncio.run(self.websocket.close())
        self.root.quit()

def main():
    root = tk.Tk()
    root.withdraw()  # Hide the main chat window initially
    client = ChatClient(root)
    login_root = tk.Toplevel()
    login_window = LoginWindow(login_root, client.connect_to_server)
    root.mainloop()

if __name__ == "__main__":
    main()
