import socket
import threading
import tkinter as tk
from tkinter import ttk, messagebox, filedialog
from auth import create_account, login
from encryption import generate_key, encrypt_file, decrypt_file
from logging_system import setup_logging, log_message
import emoji
import os
from datetime import datetime

# Client configuration
HOST = '192.168.12.247'
PORT = 12345

class ChatApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Chat Application")
        self.current_user = None
        self.selected_user = None
        self.users = {}  # Store chat history for each user
        self.logged_in_users = []  # Track logged-in users
        self.client_socket = None
        self.log_file = None
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

    def connect_to_server(self):
        try:
            self.client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.client_socket.connect((HOST, PORT))
            self.receive_thread = threading.Thread(target=self.receive_messages)
            self.receive_thread.start()
        except Exception as e:
            messagebox.showerror("Connection Error", f"Failed to connect to server: {e}")

    def login(self):
        username = self.username_entry.get()
        password = self.password_entry.get()
        result = login(username, password)
        messagebox.showinfo("Login", result)
        if result == "Login successful":
            self.current_user = username
            self.show_chat_interface()
            self.connect_to_server()
            if self.client_socket:
                self.client_socket.send(username.encode())
                if username not in self.logged_in_users:
                    self.logged_in_users.append(username)
                    self.users[username] = []
                self.request_online_users()
            self.start_logging(username)

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
        
        self.message_entry = tk.Text(self.chat_frame, height=2)
        self.message_entry.pack(fill=tk.X)
        
        self.send_button = ttk.Button(self.chat_frame, text="Send", command=self.send_message)
        self.send_button.pack()
        
        self.file_button = ttk.Button(self.chat_frame, text="Send File", command=self.send_file)
        self.file_button.pack()
        
        self.emoji_picker = ttk.Combobox(self.chat_frame, values=[emoji.emojize(":smile:", language='alias'), emoji.emojize(":crying_face:", language='alias'), emoji.emojize(":heart:", language='alias'), emoji.emojize(":thumbs_up:", language='alias'), emoji.emojize(":sunglasses:", language='alias')])
        self.emoji_picker.pack()
        self.emoji_picker.bind("<<ComboboxSelected>>", self.add_emoji)
        
        self.bold_button = ttk.Button(self.chat_frame, text="Bold", command=lambda: self.format_text('bold'))
        self.bold_button.pack()
        
        self.italic_button = ttk.Button(self.chat_frame, text="Italic", command=lambda: self.format_text('italic'))
        self.italic_button.pack()
        
        self.link_button = ttk.Button(self.chat_frame, text="Link", command=lambda: self.format_text('link'))
        self.link_button.pack()
        
        # Populate the user list with logged-in users
        self.user_list.delete(0, tk.END)
        for user in self.logged_in_users:
            self.user_list.insert(tk.END, user)

    def request_online_users(self):
        if self.client_socket:
            self.client_socket.send("ONLINE_USERS".encode())

    def switch_user(self, event):
        if self.user_list.curselection():
            selected_user = self.user_list.get(self.user_list.curselection())
            self.selected_user = selected_user
            self.chat_area.delete(1.0, tk.END)
            if selected_user not in self.users:
                self.users[selected_user] = []
            for message in self.users[selected_user]:
                self.chat_area.insert(tk.END, message + "\n")

    def send_message(self):
        if self.selected_user is None:
            messagebox.showerror("Error", "No user selected.")
            return
        message = self.message_entry.get("1.0", tk.END).strip()
        self.chat_area.insert(tk.END, f"You: {message}\n")
        self.users[self.selected_user].append(f"You: {message}")
        if self.client_socket:
            self.client_socket.send(f"PRIVATE:{self.selected_user}:{message}".encode())
        self.message_entry.delete("1.0", tk.END)
        log_message(message)
        self.write_log(f"You: {message}")

    def send_file(self):
        if self.selected_user is None:
            messagebox.showerror("Error", "No user selected.")
            return
        file_path = filedialog.askopenfilename()
        if file_path:
            key = generate_key()
            encrypted_file_path = encrypt_file(file_path, key)
            with open(encrypted_file_path, 'rb') as file:
                file_data = file.read()
            if self.client_socket:
                self.client_socket.send(f"FILE:{self.selected_user}:{file_path}".encode())
                self.client_socket.sendall(file_data)  # Use sendall to ensure all data is sent
            self.chat_area.insert(tk.END, f"File sent: {file_path}\n")
            self.users[self.selected_user].append(f"File sent: {file_path}")
            log_message(f"File sent: {file_path}")
            self.write_log(f"File sent: {file_path}")

    def add_emoji(self, event=None):
        emoji_code = self.emoji_picker.get()
        emoji_char = emoji.emojize(emoji_code, language='alias')
        self.message_entry.insert(tk.END, emoji_char)

    def format_text(self, style):
        try:
            selected_text = self.message_entry.selection_get()
            start_index = self.message_entry.index(tk.SEL_FIRST)
            end_index = self.message_entry.index(tk.SEL_LAST)
            if style == 'bold':
                formatted_text = f"**{selected_text}**"
            elif style == 'italic':
                formatted_text = f"*{selected_text}*"
            elif style == 'link':
                formatted_text = f"{selected_text}"
            self.message_entry.delete(tk.SEL_FIRST, tk.SEL_LAST)
            self.message_entry.insert(tk.INSERT, formatted_text)
        except tk.TclError:
            messagebox.showerror("Error", "No text selected.")

    def display_message(self, sender, message):
        self.chat_area.insert(tk.END, f"{sender}: {message}\n")
        self.apply_formatting_tags()
        self.write_log(f"{sender}: {message}")

    def apply_formatting_tags(self):
        content = self.chat_area.get("1.0", tk.END)
        self.chat_area.tag_remove('bold', "1.0", tk.END)
        self.chat_area.tag_remove('italic', "1.0", tk.END)
        self.chat_area.tag_remove('link', "1.0", tk.END)
        
        # Apply bold formatting
        bold_start = content.find("**")
        while bold_start != -1:
            bold_end = content.find("**", bold_start + 2)
            if bold_end != -1:
                self.chat_area.tag_add('bold', f"1.0 + {bold_start}c", f"1.0 + {bold_end - 2}c")
                self.chat_area.tag_config('bold', font=('Helvetica', 12, 'bold'))
                content = content[:bold_start] + content[bold_start + 2:bold_end] + content[bold_end + 2:]
                bold_start = content.find("**", bold_start)
            else:
                break

         # Apply italic formatting
        italic_start = content.find("*")
        while italic_start != -1:
            italic_end = content.find("*", italic_start + 1)
            if italic_end != -1:
                self.chat_area.tag_add('italic', f"1.0 + {italic_start}c", f"1.0 + {italic_end - 1}c")
                self.chat_area.tag_config('italic', font=('Helvetica', 12, 'italic'))
                content = content[:italic_start] + content[italic_start + 1:italic_end] + content[italic_end + 1:]
                italic_start = content.find("*", italic_start)
            else:
                break

        # Apply link formatting
        link_start = content.find("")
        while link_start != -1:
            link_end = content.find("", link_start)
            if link_end != -1:
                self.chat_area.tag_add('link', f"1.0 + {link_start}c", f"1.0 + {link_end - 1}c")
                self.chat_area.tag_config('link', foreground='blue', underline=True)
                content = content[:link_start] + content[link_start + 1:link_end] + content[link_end + 6:]
                link_start = content.find("[", link_start)
            else:
                break

        # Update the chat area with the formatted content
        self.chat_area.delete("1.0", tk.END)
        self.chat_area.insert("1.0", content)

    def receive_messages(self):
        while True:
            try:
                message = self.client_socket.recv(1024).decode()
                if message:
                    if hasattr(self, 'chat_area'):
                        if message.startswith("FILE:"):
                            _, sender, file_path = message.split(":", 2)
                            file_data = b""
                            while True:
                                chunk = self.client_socket.recv(1024)
                                if not chunk:
                                    break
                                file_data += chunk
                            with open(f"received_{file_path}", 'wb') as file:
                                file.write(file_data)
                            self.chat_area.insert(tk.END, f"File received from {sender}: {file_path}\n")
                        elif message.startswith("PRIVATE:"):
                            _, sender, private_message = message.split(":", 2)
                            self.display_message(sender, private_message)
                        elif message.startswith("ONLINE_USERS:"):
                            online_users = message.split(":")[1].split(",")
                            self.user_list.delete(0, tk.END)
                            for user in online_users:
                                self.user_list.insert(tk.END, user)
                        else:
                            self.chat_area.insert(tk.END, message + "\n")
                            self.apply_formatting_tags()
            except Exception as e:
                print(f"An error occurred: {e}")
                self.client_socket.close()
                break

    def start_logging(self, username):
        setup_logging(username)

    def write_log(self, message):
        log_message(message)

if __name__ == "__main__":
    root = tk.Tk()
    app = ChatApp(root)
    root.mainloop()