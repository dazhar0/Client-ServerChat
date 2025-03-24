import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from auth import create_account, login
from encryption import generate_key, encrypt_file, decrypt_file
from logging_system import setup_logging, log_message

class ChatApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Chat Application")
        self.current_user = None
        self.users = {}  # Store chat history for each user
        self.logged_in_users = []  # Track logged-in users
        self.setup_gui()

    def setup_gui(self):
        self.login_frame = ttk.Frame(self.root)
        self.login_frame.pack(fill=tk.BOTH, expand=True)
        
        self.username_label = ttk.Label(self.login_frame, text="Username:")
        self.username_label.pack()
        self.username_entry = ttk.Entry(self.login_frame)
        self.username_entry.pack()
        
        self.password_label = ttk.Label(self.login_frame, text="Password:")
        self.password_label.pack()
        self.password_entry = ttk.Entry(self.login_frame, show="*")
        self.password_entry.pack()
        
        self.login_button = ttk.Button(self.login_frame, text="Login", command=self.login)
        self.login_button.pack()
        
        self.register_button = ttk.Button(self.login_frame, text="Register", command=self.register)
        self.register_button.pack()

    def login(self):
        username = self.username_entry.get()
        password = self.password_entry.get()
        result = login(username, password)
        messagebox.showinfo("Login", result)
        if result == "Login successful":
            self.current_user = username
            if username not in self.logged_in_users:
                self.logged_in_users.append(username)
                self.users[username] = []
            self.show_chat_interface()

    def register(self):
        username = self.username_entry.get()
        password = self.password_entry.get()
        result = create_account(username, password)
        messagebox.showinfo("Register", result)

    def show_chat_interface(self):
        self.login_frame.pack_forget()
        self.user_frame = ttk.Frame(self.root)
        self.user_frame.pack(side=tk.LEFT, fill=tk.Y)
        
        self.chat_frame = ttk.Frame(self.root)
        self.chat_frame.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True)
        
        self.user_list = tk.Listbox(self.user_frame)
        self.user_list.pack(fill=tk.Y)
        self.user_list.bind('<<ListboxSelect>>', self.switch_user)
        
        self.chat_area = tk.Text(self.chat_frame)
        self.chat_area.pack(fill=tk.BOTH, expand=True)
        
        self.message_entry = ttk.Entry(self.chat_frame)
        self.message_entry.pack(fill=tk.X)
        
        self.send_button = ttk.Button(self.chat_frame, text="Send", command=self.send_message)
        self.send_button.pack()
        
        self.file_button = ttk.Button(self.chat_frame, text="Send File", command=self.send_file)
        self.file_button.pack()
        
        # Populate the user list with logged-in users
        self.user_list.delete(0, tk.END)
        for user in self.logged_in_users:
            self.user_list.insert(tk.END, user)

    def switch_user(self, event):
        selected_user = self.user_list.get(self.user_list.curselection())
        self.current_user = selected_user
        self.chat_area.delete(1.0, tk.END)
        for message in self.users[selected_user]:
            self.chat_area.insert(tk.END, message + "\n")

    def send_message(self):
        message = self.message_entry.get()
        self.chat_area.insert(tk.END, f"You: {message}\n")
        self.users[self.current_user].append(f"You: {message}")
        self.message_entry.delete(0, tk.END)
        log_message(message)

    def send_file(self):
        file_path = filedialog.askopenfilename()
        if file_path:
            key = generate_key()
            encrypt_file(file_path, key)
            self.chat_area.insert(tk.END, f"File sent: {file_path}\n")
            self.users[self.current_user].append(f"File sent: {file_path}")
            log_message(f"File sent: {file_path}")

if __name__ == "__main__":
    root = tk.Tk()
    app = ChatApp(root)
    root.mainloop()