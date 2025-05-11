# Titan Secure Chat

Titan Secure Chat is a real-time, secure chat application that allows users to communicate via public and private messages. It features user authentication, message encryption, file sharing, and online presence tracking. The application is built using a combination of PHP, JavaScript, and WebSocket technologies, and it leverages hosting services like **Render** and **InfinityFree**.

---

## Team Members
- **Daniyal Azhar**
- **Adrian Perez**  
(Group 16)

---

## Features

### Core Features
- **User Authentication**: Users can register and log in securely.
- **Real-Time Messaging**: Chat with other users in real-time using WebSocket.
- **Private Messaging**: Send private messages to specific users.
- **File Sharing**: Upload and share files with other users.
- **Online Presence**: View which users are currently online.
- **Message Encryption**: Messages are encrypted using AES for secure communication.

### Hosting
- **Backend**: Hosted on **InfinityFree** for PHP-based APIs and database interactions.
- **WebSocket Server**: Hosted on **Render** for real-time communication.

---

## Project Structure

```
Client-ServerChat/
├── backend/
│   ├── db.php                  # Database connection
│   ├── decrypt.php             # Decrypt messages on the server
│   ├── fetch_messages.php      # Fetch chat messages
│   ├── get_private_messages.php # Fetch private messages
│   ├── get_users.php           # Fetch online/offline users
│   ├── login.php               # User login API
│   ├── message_log.php         # Log messages in the database
│   ├── presence.php            # Manage user presence
│   ├── register.php            # User registration API
│   ├── store_message.php       # Store messages in the database
│   ├── update_presence_users.php # Update user presence
│   ├── upload_file.php         # Handle file uploads
├── frontend/
│   ├── js/
│   │   ├── crypto.js           # AES encryption and decryption
│   │   ├── script.js           # WebSocket client logic
│   ├── login.php               # Login page
│   ├── main.php                # Main chat interface
│   ├── message.html            # Chat UI
│   ├── register.php            # Registration page
├── server.js                   # WebSocket server
├── package.json                # Node.js dependencies and scripts
├── index.php                   # Redirect to login page
```

---

## Installation

### Prerequisites
- **Node.js** (v14 or higher)
- **MySQL** database
- **PHP** (for backend APIs)
- **Google reCAPTCHA**: Obtain your own reCAPTCHA keys from [Google reCAPTCHA](https://www.google.com/recaptcha/).
- Hosting accounts on **Render** and **InfinityFree**

### Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/your-repo/titan-secure-chat.git
   cd titan-secure-chat
   ```

2. Install dependencies for the WebSocket server:
   ```bash
   npm install
   ```

3. Configure the database:
   - Import the `securechat.sql` file into your MySQL database (located in the `backend/` folder).
   - Update the database credentials in `backend/db.php`.

4. Ensure the `uploads/` directory exists in the `backend/` folder and has write permissions.

5. Start the WebSocket server:
   ```bash
   npm run dev
   ```

6. Deploy the backend:
   - Upload the `backend/` folder to your InfinityFree hosting account.

7. Update URLs:
   - Replace API URLs in the frontend files (e.g., `register.php`, `login.php`, etc.) with your InfinityFree domain.
   - Ensure the WebSocket URL in `frontend/js/script.js` and `frontend/message.html` points to your Render WebSocket server.

---

## Usage

### Register
1. Navigate to the registration page (`register.php`).
2. Fill in your email, username, and password.
3. Complete the CAPTCHA and click "Register".

### Login
1. Navigate to the login page (`login.php`).
2. Enter your username and password to log in.

### Chat
1. After logging in, you will be redirected to the chat interface.
2. Use the input box to send messages.
3. Click the file upload button to share files.
4. View online users in the "Online Users" panel.

---

## Technologies Used

### Frontend
- **HTML/CSS**: For the user interface.
- **JavaScript**: For WebSocket communication and client-side logic.
- **CryptoJS**: For AES encryption and decryption.

### Backend
- **PHP**: For user authentication, file uploads, and database interactions.
- **MySQL**: For storing user data, messages, and presence information.

### Real-Time Communication
- **WebSocket**: For real-time messaging, hosted on Render.

### Security Features
- **AES Encryption**: Messages are encrypted on the client-side using a shared secret key.
- **Password Hashing**: User passwords are hashed using `password_hash()` in PHP.
- **CAPTCHA**: Google reCAPTCHA is used to prevent bot registrations.

---

## Hosting Details
- **Render**: Hosts the WebSocket server (`server.js`).
- **InfinityFree**: Hosts the PHP backend and MySQL database.

---

## Scripts

### Start WebSocket Server
```bash
npm start
```

### Development Mode
```bash
npm run dev
```

---

## Known Issues
- **CAPTCHA Secret Key**: Ensure the secret key in `register.php` is updated with your own Google reCAPTCHA key.
- **File Upload Path**: The `upload_file.php` script saves files to the `uploads/` directory, which must have write permissions.

---

## Future Enhancements
- Add support for group chats.
- Implement end-to-end encryption for private messages.
- Improve UI/UX for the chat interface.
- Add user profile pictures.

---

## License
This project is licensed under the ISC License.
