<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureChat - Chat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        #chat-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #e9f5fc;
        }
        #online-users {
            padding: 15px;
            background: #4CAF50;
            color: white;
            max-height: 150px;
            overflow-y: auto;
            border-bottom: 2px solid #ddd;
        }
        #messages {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
            background: white;
            border-bottom: 2px solid #ddd;
        }
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
        .user {
            cursor: pointer;
            color: #4CAF50;
            font-weight: bold;
            text-decoration: underline;
        }
        .emoji-btn {
            font-size: 24px;
            cursor: pointer;
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
        }
        .emoji {
            cursor: pointer;
            font-size: 24px;
            margin: 5px;
        }
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
        }
        .close-tab {
            cursor: pointer;
            color: red;
            font-size: 16px;
        }
        .private-message {
            background-color: #d0f7ff;
        }
        .sent-message {
            background-color: #f1f1f1;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>
<body>
    <div id="chat-container">
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

    <script>
        const username = "<?php echo $username; ?>";
        const messagesDiv = document.getElementById("messages");
        const input = document.getElementById("input");
        const sendBtn = document.getElementById("send");
        const fileInput = document.getElementById("fileInput");
        const fileBtn = document.getElementById("fileBtn");
        const onlineDiv = document.getElementById("online-users");
        const emojiBtn = document.getElementById("emojiBtn");
        const emojiPicker = document.getElementById("emoji-picker");

        const ws = new WebSocket("wss://client-serverchat.onrender.com");
        const SECRET_KEY = "your-very-strong-secret";

        function encrypt(text) {
            return CryptoJS.AES.encrypt(text, SECRET_KEY).toString();
        }

        function decrypt(text) {
            const bytes = CryptoJS.AES.decrypt(text, SECRET_KEY);
            return bytes.toString(CryptoJS.enc.Utf8) || "[Failed to decrypt]";
        }

        function addMessage(sender, text, isPrivate = false) {
            const msg = document.createElement("div");
            msg.className = "message";
            msg.innerHTML = `<span class="sender">${sender}${isPrivate ? " (private)" : ""}:</span> ${decrypt(text)}`;
            if (isPrivate) {
                const privateChatArea = document.getElementById(`private-chat-${sender}`);
                if (privateChatArea) {
                    const privateMsg = document.createElement("div");
                    privateMsg.className = 'private-message';
                    privateMsg.innerHTML = `<span class="sender">${sender}:</span> ${decrypt(text)}`;
                    privateChatArea.appendChild(privateMsg);
                }
            } else {
                messagesDiv.appendChild(msg);
            }
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
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
            const data = JSON.parse(event.data);
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

        let selectedUser = null;

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
            document.getElementById("messages").appendChild(privateChatArea);
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
