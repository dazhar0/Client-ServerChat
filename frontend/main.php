<?php
session_start();

// If no session exists, show the login interface.
if (!isset($_SESSION['username'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Titan Chat</title>
  <style>
    /* Container centering and background */
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    /* Login Card */
    .login-container {
      text-align: center;
      padding: 20px;
    }
    .login-card {
      background: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 6px 10px rgba(0,0,0,0.15);
      width: 320px;
      margin: auto;
    }
    h1 {
      color: #333;
      margin-bottom: 20px;
    }
    form {
      text-align: left;
      margin-top: 20px;
    }
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
      color: #555;
    }
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }
    button {
      width: 100%;
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #0056b3;
    }
    .login-footer {
      margin-top: 20px;
      font-size: 14px;
      color: #555;
    }
    .login-footer a {
      color: #007bff;
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease;
    }
    .login-footer a:hover {
      color: #0056b3;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <h1>Login</h1>
      <form id="loginForm">
        <label for="username">Username:</label>
        <input type="text" id="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" required>
        <button type="submit">Login</button>
      </form>
      <div class="login-footer">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
      </div>
    </div>
  </div>
  <script>
    // Login form submission via AJAX
    document.getElementById("loginForm").onsubmit = async function (event) {
      event.preventDefault();
      const username = document.getElementById("username").value;
      const password = document.getElementById("password").value;
      try {
        const response = await fetch("backend/login.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ username, password })
        });
        const result = await response.json();
        if (result.status === "success") {
          // Reload page to load the chat interface upon successful login.
          window.location.reload();
        } else {
          alert(result.message);
        }
      } catch (error) {
        alert("An error occurred. Please try again.");
      }
    };
  </script>
</body>
</html>
<?php
  exit();
} else {
  // User has logged in – load the chat interface.
  $username = $_SESSION['username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SecureChat - Chat</title>
  <!-- Include CryptoJS for encryption/decryption -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <style>
    /* Universal reset and box sizing */
    *, *::before, *::after {
      box-sizing: border-box;
    }
    /* Center the chat interface */
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    /* Chat wrapper card styling */
    .chat-wrapper {
      width: 100%;
      max-width: 900px;
      height: 90vh;
      display: flex;
      flex-direction: column;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      background-color: #e9f5fc;
      position: relative;
    }
    /* Chat container structure */
    #chat-container {
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    /* Header branding */
    #chat-header {
      background: #007bff;
      color: #fff;
      padding: 15px;
      text-align: center;
      font-size: 24px;
      font-weight: bold;
    }
    /* Online Users panel */
    #online-users {
      padding: 15px;
      background: #4CAF50;
      color: white;
      max-height: 150px;
      overflow-y: auto;
      border-bottom: 2px solid #ddd;
    }
    /* Messages area styling */
    #messages {
      flex: 1;
      padding: 10px;
      overflow-y: auto;
      background: #ffffff;
      border-bottom: 2px solid #ddd;
    }
    .message {
      margin: 5px 0;
      padding: 8px;
      background-color: #f1f1f1;
      border-radius: 5px;
    }
    .sender {
      font-weight: bold;
      margin-right: 5px;
      color: #4CAF50;
    }
    /* Input controls */
    #input-area {
      display: flex;
      padding: 10px;
      background: #eeeeee;
      border-top: 2px solid #ddd;
    }
    #input {
      flex: 1;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    #send, #fileBtn {
      padding: 10px;
      margin-left: 5px;
      cursor: pointer;
      border-radius: 5px;
      background-color: #4CAF50;
      color: white;
      border: none;
      transition: background-color 0.3s ease;
    }
    #send:hover, #fileBtn:hover {
      background-color: #45a049;
    }
    /* Emoji button and picker */
    .emoji-btn {
      font-size: 24px;
      cursor: pointer;
      background: none;
      border: none;
      margin-left: 5px;
    }
    .emoji-picker {
      display: none;
      padding: 10px;
      border: 1px solid #ddd;
      background: #fff;
      position: absolute;
      bottom: 80px;
      right: 10px;
      border-radius: 5px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .emoji {
      cursor: pointer;
      font-size: 24px;
      margin: 5px;
    }
    /* Private chat area styling */
    .private-chat-area {
      margin-top: 20px;
      background-color: #e6f7ff;
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ddd;
      max-height: 300px;
      overflow-y: auto;
    }
    .chat-tab-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    .close-tab {
      cursor: pointer;
      color: red;
      font-size: 16px;
    }
    .private-message {
      background-color: #d0f7ff;
      margin: 5px 0;
      padding: 8px;
      border-radius: 5px;
    }
  </style>
</head>
<body>
  <div class="chat-wrapper">
    <div id="chat-container">
      <div id="chat-header">SecureChat</div>
      <div id="online-users"><b>Online Users:</b> Loading...</div>
      <div id="messages"></div>
      <div id="input-area">
        <input type="text" id="input" placeholder="Type your message with emojis 😊" />
        <input type="file" id="fileInput" style="display: none;" />
        <button id="fileBtn">📎</button>
        <button id="send">Send</button>
        <button id="emojiBtn" class="emoji-btn">😊</button>
      </div>
      <div id="emoji-picker" class="emoji-picker">
        <span class="emoji" onclick="addEmoji('😊')">😊</span>
        <span class="emoji" onclick="addEmoji('😢')">😢</span>
        <span class="emoji" onclick="addEmoji('😎')">😎</span>
        <span class="emoji" onclick="addEmoji('😂')">😂</span>
      </div>
    </div>
  </div>

  <script>
    // Retrieve the username from PHP
    const username = "<?php echo $username; ?>";
    const messagesDiv = document.getElementById("messages");
    const input = document.getElementById("input");
    const sendBtn = document.getElementById("send");
    const fileInput = document.getElementById("fileInput");
    const fileBtn = document.getElementById("fileBtn");
    const onlineDiv = document.getElementById("online-users");
    const emojiBtn = document.getElementById("emojiBtn");
    const emojiPicker = document.getElementById("emoji-picker");

    // Setup WebSocket for real-time messaging and encryption key
    const ws = new WebSocket("wss://client-serverchat.onrender.com");
    const SECRET_KEY = "your-very-strong-secret";
    let selectedUser = null;

    function encrypt(text) {
      return CryptoJS.AES.encrypt(text, SECRET_KEY).toString();
    }

    function decrypt(text) {
      try {
        const bytes = CryptoJS.AES.decrypt(text, SECRET_KEY);
        return bytes.toString(CryptoJS.enc.Utf8) || "[Failed to decrypt]";
      } catch (e) {
        return "[Decryption error]";
      }
    }

    function addMessage(sender, encryptedText, isPrivate = false) {
      const decryptedText = decrypt(encryptedText);
      if (isPrivate) {
        const privateChatArea = document.getElementById(`private-chat-${sender}`);
        if (privateChatArea) {
          const privateMsg = document.createElement("div");
          privateMsg.className = "private-message";
          privateMsg.innerHTML = `<span class="sender">${sender}:</span> ${decryptedText}`;
          privateChatArea.appendChild(privateMsg);
          privateChatArea.scrollTop = privateChatArea.scrollHeight;
        }
      } else {
        const msg = document.createElement("div");
        msg.className = "message";
        msg.innerHTML = `<span class="sender">${sender}:</span> ${decryptedText}`;
        messagesDiv.appendChild(msg);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
      }
    }

    function updatePresence(status) {
      fetch("backend/update_presence.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, online: status })
      });
    }

    ws.onopen = () => {
      ws.send(JSON.stringify({ type: "join", username }));
      updatePresence(1);
    };

    ws.onmessage = (event) => {
      let data;
      try {
        data = JSON.parse(event.data);
      } catch (e) {
        console.error("Invalid JSON:", event.data);
        return;
      }
      if (data.type === "message" || data.type === "private_message") {
        const isPrivate = data.type === "private_message";
        addMessage(data.username, data.message, isPrivate);
      }
      if (data.type === "online_users") {
        updateOnlineUsers(data.users);
      }
    };

    sendBtn.onclick = () => {
      const text = input.value.trim();
      if (text !== "") {
        const payload = {
          type: "message",
          username,
          message: encrypt(text)
        };
        ws.send(JSON.stringify(payload));
        input.value = "";
      }
    };

    emojiBtn.onclick = () => {
      emojiPicker.style.display = emojiPicker.style.display === "block" ? "none" : "block";
    };

    function addEmoji(emoji) {
      input.value += emoji;
      emojiPicker.style.display = "none";
    }

    fileBtn.onclick = () => fileInput.click();

    fileInput.onchange = () => {
      const file = fileInput.files[0];
      if (!file) return;
      const formData = new FormData();
      formData.append("file", file);
      fetch("backend/upload_files.php", {
        method: "POST",
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.url) {
          const encryptedMessage = encrypt(`[File: ${file.name}]\n${data.url}`);
          const payload = {
            type: selectedUser ? "private_message" : "message",
            username,
            to: selectedUser || undefined,
            message: encryptedMessage
          };
          ws.send(JSON.stringify(payload));
        } else {
          alert("Upload failed");
        }
        selectedUser = null;
      });
    };

    function updateOnlineUsers(users) {
      onlineDiv.innerHTML = "<b>Online Users:</b><br>" +
        users.filter(u => u.online == 1)
          .map(u => `<span class="user" data-username="${u.username}">${u.username}</span>`)
          .join("<br>");
      
      document.querySelectorAll('.user').forEach(userEl => {
        userEl.onclick = () => {
          const to = userEl.dataset.username;
          if (to === username) return alert("You can't message yourself.");
          openPrivateChat(to);
        };
      });
    }

    function openPrivateChat(to) {
      if (document.getElementById(`private-chat-${to}`)) return;
      const privateChatArea = document.createElement("div");
      privateChatArea.id = `private-chat-${to}`;
      privateChatArea.className = "private-chat-area";
      privateChatArea.innerHTML = `
        <div class="chat-tab-header">
          <b>Private Chat with ${to}</b>
          <span class="close-tab" onclick="closePrivateChat('${to}')">X</span>
        </div>
        <div id="private-chat-${to}-messages"></div>
      `;
      messagesDiv.appendChild(privateChatArea);
      selectedUser = to;
    }

    function closePrivateChat(to) {
      const chatArea = document.getElementById(`private-chat-${to}`);
      if (chatArea) chatArea.remove();
    }

    setInterval(() => {
      fetchOnlineUsers();
    }, 5000);

    function fetchOnlineUsers() {
      fetch("backend/presence.php")
        .then(res => res.json())
        .then(users => updateOnlineUsers(users));
    }

    window.addEventListener("beforeunload", () => {
      ws.send(JSON.stringify({ type: "leave", username }));
      updatePresence(0);
    });

    ws.onclose = () => {
      updatePresence(0);
    };
  </script>
</body>
</html>
