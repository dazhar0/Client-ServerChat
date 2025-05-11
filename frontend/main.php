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
    <title>Titan Chat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            display: flex;
            height: 100vh;
        }
        #chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
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
        .message, .private-message {
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
            bottom: 60px; /* Position it above the input area */
            left: 10px; /* Adjust left position */
            border-radius: 5px;
            z-index: 10;
        }
        .emoji {
            cursor: pointer;
            font-size: 24px;
            margin: 5px;
        }
        #private-chat-area-container {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            padding: 10px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .private-chat-area {
            width: 300px;
            height: 70vh;
            background-color: #e6f7ff;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .chat-tab-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }
        .close-tab {
            cursor: pointer;
            color: red;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>
<body>
    <div id="chat-container">
        <div id="online-users"><b>Online Users:</b> Loading...</div>
        <div id="messages"></div>
        <div id="input-area">
            <input type="text" id="input" placeholder="Type your message..." />
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

    <div id="private-chat-area-container"></div>

    <script>
        const username = "<?php echo $username; ?>";
        const ws = new WebSocket("wss://client-serverchat.onrender.com");
        const SECRET_KEY = "your-very-strong-secret";
        let selectedUser = null;

        function encrypt(text) {
            return CryptoJS.AES.encrypt(text, SECRET_KEY).toString();
        }

        function decrypt(text) {
            try {
                return CryptoJS.AES.decrypt(text, SECRET_KEY).toString(CryptoJS.enc.Utf8) || "[Decryption failed]";
            } catch {
                return "[Invalid message]";
            }
        }

        function addEmoji(emoji) {
            document.getElementById("input").value += emoji;
            document.getElementById("emoji-picker").style.display = "none";
        }

        function addMessage(sender, encryptedText, isPrivate = false, peer = null, container = null) {
            const text = decrypt(encryptedText);
            const targetDiv = container || (isPrivate ? document.getElementById(`private-chat-${peer}-messages`) : document.getElementById("messages"));
            if (!targetDiv) return;

            const senderName = sender === username ? "You" : sender;
            const msg = document.createElement("div");
            msg.className = isPrivate ? "private-message" : "message";
            msg.innerHTML = `<span class="sender">${senderName}${isPrivate ? " (private)" : ""}:</span> ${text}`;
            targetDiv.appendChild(msg);
            targetDiv.scrollTop = targetDiv.scrollHeight;
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
            if (data.type === "message") {
                const sender = data.from || 'Unknown'; // always use 'from'
                addMessage(sender, data.message);
            } else if (data.type === "private_message") {
                const sender = data.from;
                const peer = sender === username ? data.to : sender;

                // Prevent adding the sender's message again for themselves
                if (sender !== username) {
                    openPrivateChat(peer); // Open private chat with the peer
                    addMessage(sender, data.message, true, peer);
                }

                // Only add the message to the sender's chat once, skip if it's the sender
                if (sender === username) {
                    addMessage(sender, data.message, true, peer);  // This will prevent duplication for the sender
                }
            } else if (data.type === "online_users") {
                updateOnlineUsers(data.users);
            }
        };

        ws.onclose = () => updatePresence(0);
        window.addEventListener("beforeunload", () => {
            if (ws.readyState === WebSocket.OPEN)
                ws.send(JSON.stringify({ type: "leave", username }));
            updatePresence(0);
        });

        document.getElementById("send").onclick = () => {
            const input = document.getElementById("input");
            const text = input.value.trim();
            if (!text) return;
            const payload = {
                type: selectedUser ? "private_message" : "message",
                from: username,
                to: selectedUser || undefined,
                message: encrypt(text)
            };
            ws.send(JSON.stringify(payload));
            input.value = "";
        };

        document.getElementById("fileBtn").onclick = () => {
            document.getElementById("fileInput").click();
        };

        document.getElementById("fileInput").onchange = () => {
            const file = document.getElementById("fileInput").files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append("file", file);
            fetch("backend/upload_files.php", {
                method: "POST",
                body: formData
            }).then(res => res.json()).then(data => {
                if (data.url) {
                    const fileMsg = `[File: ${file.name}]\n${data.url}`;
                    const payload = {
                        type: "private_message",
                        from: username,
                        to: selectedUser,
                        message: encrypt(fileMsg)
                    };
                    ws.send(JSON.stringify(payload));
                } else {
                    alert("Upload failed.");
                }
            });
        };

        document.getElementById("emojiBtn").onclick = () => {
            const picker = document.getElementById("emoji-picker");
            picker.style.display = picker.style.display === "block" ? "none" : "block";
        };

        function updateOnlineUsers(users) {
            const onlineDiv = document.getElementById("online-users");
            onlineDiv.innerHTML = "<b>Online Users:</b><br>" +
                users.filter(u => u.online == 1)
                    .map(u => `<span class="user" data-username="${u.username}">${u.username}</span>`)
                    .join("<br>");
            document.querySelectorAll(".user").forEach(el => {
                el.onclick = () => {
                    const to = el.dataset.username;
                    if (to !== username) openPrivateChat(to);
                };
            });
        }

        function openPrivateChat(to) {
            if (document.getElementById(`private-chat-${to}`)) {
                selectedUser = to;
                return;
            }

            const chatArea = document.createElement("div");
            chatArea.className = "private-chat-area";
            chatArea.id = `private-chat-${to}`;
            chatArea.innerHTML = `
                <div class="chat-tab-header">
                <b>Private Chat with ${to}</b>
                <span class="close-tab" onclick="closePrivateChat('${to}')">X</span>
            </div>

            <div id="private-chat-${to}-messages" style="height: 200px; overflow-y:auto;"></div>

            <!-- WRAPPER for input/buttons/emoji picker -->
            <div class="private-input-wrapper" style="position: relative; display: flex; align-items: center; gap: 5px;">

                <input type="text" class="private-input" id="private-input-${to}" placeholder="Type a message" />
                <input type="file" id="fileInput-${to}" style="display:none;" />

                <button onclick="sendPrivateMessage('${to}')">Send</button>
                <button onclick="document.getElementById('fileInput-${to}').click();">📎</button>
                <button onclick="toggleEmojiPicker('${to}')">😊</button>

                <!-- Moved inside the wrapper for correct positioning -->
                <div id="emoji-picker-${to}" class="emoji-picker" style="display:none; position:absolute; bottom: 40px; left: 0; background:#fff; border:1px solid #ccc; padding:5px; border-radius:5px; z-index:100;">
                    <span class="emoji" onclick="addEmojiTo('${to}', '😊')">😊</span>
                    <span class="emoji" onclick="addEmojiTo('${to}', '😂')">😂</span>
                    <span class="emoji" onclick="addEmojiTo('${to}', '😢')">😢</span>
                    <span class="emoji" onclick="addEmojiTo('${to}', '😎')">😎</span>
                </div>

            </div>
            `;
            document.getElementById("private-chat-area-container").appendChild(chatArea);
            selectedUser = to;

            document.getElementById(`fileInput-${to}`).onchange = () => {
                const file = document.getElementById(`fileInput-${to}`).files[0];
                if (!file) return;
                const formData = new FormData();
                formData.append("file", file);
                fetch("backend/upload_files.php", {
                    method: "POST",
                    body: formData
                }).then(res => res.json()).then(data => {
                    if (data.url) {
                        const fileMsg = `[File: ${file.name}]\n${data.url}`;
                        const payload = {
                            type: "private_message",
                            from: username,
                            to,
                            message: encrypt(fileMsg)
                        };
                        ws.send(JSON.stringify(payload));
                    }
                });
            };

            fetch(`backend/get_private_messages.php?user1=${username}&user2=${to}`)
                .then(res => res.json())
                .then(messages => {
                    const msgContainer = document.getElementById(`private-chat-${to}-messages`);
                    messages.forEach(msg => addMessage(msg.from, msg.message, true, to, msgContainer));
                });
        }

        function sendPrivateMessage(to) {
            const input = document.getElementById(`private-input-${to}`);
            const msg = input.value.trim();
            if (!msg) return;
            const payload = {
                type: "private_message",
                from: username,
                to,
                message: encrypt(msg)
            };
            ws.send(JSON.stringify(payload)); // Send message via WebSocket
            
            // Save the private message to the database via AJAX
            fetch("backend/save_private_message.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    from: username,
                    to,
                    message: msg
                })
            });

            addMessage(username, payload.message, true, to); // Show your sent message instantly
            input.value = "";
        }


        function closePrivateChat(to) {
            const el = document.getElementById(`private-chat-${to}`);
            if (el) el.remove();
            selectedUser = null;
        }

        function toggleEmojiPicker(to) {
            const picker = document.getElementById(`emoji-picker-${to}`);
            picker.style.display = picker.style.display === "block" ? "none" : "block";
        }

        function addEmojiTo(to, emoji) {
            const input = document.getElementById(`private-input-${to}`);
            input.value += emoji;
            toggleEmojiPicker(to);
        }

        setInterval(() => {
            fetch("backend/presence.php")
                .then(res => res.json())
                .then(data => updateOnlineUsers(data));
        }, 5000);
    </script>
</body>
</html>
