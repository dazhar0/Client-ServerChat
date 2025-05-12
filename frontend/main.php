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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Optimized CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .main-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
            width: 440px;
            max-width: 99vw;
            display: flex;
            flex-direction: column;
            min-height: 650px;
            max-height: 95vh;
            position: relative;
            transition: box-shadow 0.2s;
        }
        .main-header {
            text-align: center;
            padding: 32px 0 16px;
            color: #1a1a1a;
            font-size: 2.3rem;
            font-weight: 700;
            letter-spacing: 1px;
            border-bottom: 1px solid #e0e0e0;
            background: linear-gradient(90deg, #e3f0fc 0%, #f4f4f9 100%);
            box-shadow: 0 2px 8px rgba(0,123,255,0.04);
        }
        #online-users {
            padding: 14px 20px;
            background: #e3f0fc;
            color: #007bff;
            font-weight: bold;
            border-bottom: 1px solid #e0e0e0;
            font-size: 15px;
            min-height: 40px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .user {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border-radius: 16px;
            padding: 5px 14px 5px 8px;
            margin-right: 6px;
            margin-bottom: 4px;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            border: 1px solid #e0e0e0;
            font-size: 15px;
        }
        .user:hover {
            background: #e6f7ff;
            box-shadow: 0 2px 6px rgba(0,123,255,0.08);
        }
        .user-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #007bff;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 7px;
        }
        #messages {
            flex: 1 1 auto;
            padding: 20px 14px;
            overflow-y: auto;
            background: #f9fafd;
            border-bottom: 1px solid #e0e0e0;
            max-height: 52vh;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .bubble-row {
            display: flex;
            align-items: flex-end;
            margin-bottom: 2px;
            animation: fadeIn 0.25s;
        }
        .bubble-row.you {
            justify-content: flex-end;
        }
        .chat-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 15px;
            margin: 2px 0;
            background: #e3f0fc;
            color: #222;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            word-break: break-word;
            transition: background 0.2s;
        }
        .bubble-row.you .chat-bubble {
            background: #007bff;
            color: #fff;
            border-bottom-right-radius: 6px;
        }
        .bubble-row.other .chat-bubble {
            background: #f1f1f1;
            color: #222;
            border-bottom-left-radius: 6px;
        }
        .bubble-meta {
            font-size: 11px;
            color: #888;
            margin-top: 4px;
            margin-left: 4px;
        }
        .bubble-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #007bff;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            margin-left: 2px;
        }
        .bubble-row.you .bubble-avatar {
            display: none;
        }
        #typing-indicator {
            font-size: 13px;
            color: #888;
            margin: 0 0 8px 8px;
            font-style: italic;
        }
        #input-area {
            display: flex;
            align-items: center;
            padding: 18px 20px;
            background: #f4f4f9;
            border-top: 1px solid #e0e0e0;
            gap: 10px;
            position: sticky;
            bottom: 0;
            z-index: 2;
        }
        #input {
            flex: 1;
            padding: 12px;
            font-size: 15px;
            border: 1.5px solid #ccc;
            border-radius: 6px;
            background: #fff;
        }
        #send, #emojiBtn, #fileBtn {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 12px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            min-height: 44px;
        }
        #send:active, #emojiBtn:active, #fileBtn:active {
            box-shadow: 0 0 0 2px #b3d7ff;
            transform: scale(0.96);
        }
        #send:hover, #emojiBtn:hover, #fileBtn:hover {
            background-color: #0056b3;
        }
        .emoji-btn {
            font-size: 22px;
            background: #fff;
            color: #007bff;
            border: 1.5px solid #ccc;
        }
        .emoji-btn:hover {
            background: #e9f5fc;
        }
        .emoji-picker {
            display: none;
            padding: 10px;
            border: 1px solid #ddd;
            background: #fff;
            position: absolute;
            bottom: 70px;
            left: 30px;
            border-radius: 7px;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .emoji {
            cursor: pointer;
            font-size: 22px;
            margin: 5px;
        }
        /* Private chat area styling */
        #private-chat-area-container {
            position: fixed;
            right: 30px;
            top: 30px;
            bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            z-index: 20;
            gap: 18px;
        }
        .private-chat-area {
            width: 320px;
            height: 70vh;
            background-color: #f9fafd;
            border-radius: 14px;
            border: 1.5px solid #e0e0e0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            display: flex;
            flex-direction: column;
            animation: slideInRight 0.25s;
        }
        @keyframes slideInRight {
            from { transform: translateX(60px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .chat-tab-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            padding: 16px 18px 10px 18px;
            background: #e3f0fc;
            border-radius: 14px 14px 0 0;
            font-size: 1.1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .close-tab {
            cursor: pointer;
            color: #e74c3c;
            font-size: 22px;
            font-weight: bold;
            background: none;
            border: none;
            transition: color 0.2s;
        }
        .close-tab:hover {
            color: #b30000;
        }
        .private-messages {
            flex: 1 1 auto;
            overflow-y: auto;
            background: #f9fafd;
            padding: 14px 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .private-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f4f4f9;
            border-top: 1px solid #e0e0e0;
            padding: 14px 10px;
            border-radius: 0 0 14px 14px;
        }
        /* Responsive */
        @media (max-width: 900px) {
            #private-chat-area-container {
                right: 0;
                top: unset;
                bottom: 0;
                flex-direction: row;
                align-items: flex-end;
                gap: 8px;
            }
            .private-chat-area {
                width: 98vw;
            }
        }
        @media (max-width: 600px) {
            .main-container { width: 99vw; min-height: 90vh; max-height: 99vh; }
            #private-chat-area-container { right: 0; top: unset; bottom: 0; }
            .private-chat-area { width: 99vw; }
            #messages { max-height: 40vh; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <!-- Add Firebase SDKs -->
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-storage-compat.js"></script>
    <script>
      // Use your actual Firebase config (with correct storageBucket)
      const firebaseConfig = {
            apiKey: "AIzaSyCMgKRbtatng1C8_e1IZXG4pACPemKali4",
            authDomain: "titanchat-c7744.firebaseapp.com",
            projectId: "titanchat-c7744",
            storageBucket: "titanchat-c7744.appspot.com",
            messagingSenderId: "497019607900",
            appId: "1:497019607900:web:473307535da7871514ff99",
            measurementId: "G-31SZ89S1NC"
      };
      firebase.initializeApp(firebaseConfig);
      const storage = firebase.storage();
    </script>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="main-header" id="main-header">
            Titan Chat
            <button id="signout-btn" style="float:right; margin-right:20px; font-size:1rem; padding:6px 16px; border-radius:6px; border:none; background:#e74c3c; color:#fff; cursor:pointer;">Sign Out</button>
        </div>
        <!-- Online Users -->
        <div id="online-users"></div>
        <!-- Chat Messages -->
        <div id="messages" class="chat-messages"></div>
        <div id="typing-indicator" style="display:none;"></div>
        <!-- Input Area -->
        <div id="input-area" class="input-area">
            <input type="text" id="input" placeholder="Type your message..." autocomplete="off">
            <button id="send">Send</button>
            <button id="emojiBtn" class="emoji-btn" title="Emoji">&#128515;</button>
            <input type="file" id="fileInput" style="display: none;">
            <button id="fileBtn" title="Attach file">&#128206;</button>
        </div>
        <div id="emoji-picker" class="emoji-picker">
            <span class="emoji" onclick="addEmoji('😊')">😊</span>
            <span class="emoji" onclick="addEmoji('😢')">😢</span>
            <span class="emoji" onclick="addEmoji('😎')">😎</span>
            <span class="emoji" onclick="addEmoji('😂')">😂</span>
        </div>
    </div>
    <!-- Private Chat Area Container -->
    <div id="private-chat-area-container"></div>
    <!-- JavaScript -->
    <script>
        // Utility function to escape HTML strings (to prevent XSS)
        function escapeHTML(str) {
            return str.replace(/&/g, "&amp;")
                      .replace(/</g, "&lt;")
                      .replace(/>/g, "&gt;")
                      .replace(/"/g, "&quot;")
                      .replace(/'/g, "&#039;");
        }
        
        const username = "<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>";
        const ws = new WebSocket("wss://client-serverchat-2rxg.onrender.com");
        const SECRET_KEY = "your-very-strong-secret";
        let selectedUser = null;
        let typingTimeout = null;

        function getInitials(name) {
            return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);
        }

        function encrypt(text) {
            return CryptoJS.AES.encrypt(text, SECRET_KEY).toString();
        }

        function decrypt(text) {
            try {
                const bytes = CryptoJS.AES.decrypt(text, SECRET_KEY);
                return bytes.toString(CryptoJS.enc.Utf8) || "[Decryption failed]";
            } catch (e) {
                return "[Invalid message]";
            }
        }

        function addEmoji(emoji) {
            document.getElementById("input").value += emoji;
            document.getElementById("emoji-picker").style.display = "none";
        }

        function formatTime() {
            const d = new Date();
            return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function addMessage(sender, encryptedText, isPrivate = false, peer = null, container = null, timestamp = null) {
            const text = decrypt(encryptedText);
            const targetDiv = container || (isPrivate ? document.getElementById(`private-chat-${peer}-messages`) : document.getElementById("messages"));
            if (!targetDiv) return;

            const isYou = sender === username;
            const row = document.createElement("div");
            row.className = "bubble-row " + (isYou ? "you" : "other");

            if (!isYou) {
                const avatar = document.createElement("div");
                avatar.className = "bubble-avatar";
                avatar.textContent = getInitials(sender);
                row.appendChild(avatar);
            }

            const bubble = document.createElement("div");
            bubble.className = "chat-bubble";
            // Detect file message and render as link
            if (text.startsWith("[File: ")) {
                const match = text.match(/^\[File: (.+?)\]\n(.+)$/);
                if (match) {
                    const fileName = match[1];
                    const fileUrl = match[2];
                    bubble.innerHTML = `<span><a href="${escapeHTML(fileUrl)}" target="_blank" rel="noopener noreferrer">${escapeHTML(fileName)}</a></span>`;
                } else {
                    bubble.innerHTML = `<span>${escapeHTML(text)}</span>`;
                }
            } else {
                bubble.innerHTML = `<span>${escapeHTML(text)}</span>`;
            }
            row.appendChild(bubble);

            const meta = document.createElement("div");
            meta.className = "bubble-meta";
            meta.textContent = (isYou ? "You" : sender) + " • " + (timestamp || formatTime());
            bubble.appendChild(meta);

            targetDiv.appendChild(row);
            targetDiv.scrollTop = targetDiv.scrollHeight;
        }

        function showTypingIndicator(name) {
            const indicator = document.getElementById("typing-indicator");
            indicator.textContent = `${name} is typing...`;
            indicator.style.display = "block";
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => indicator.style.display = "none", 1500);
        }

        function updatePresence(status) {
            fetch("https://chatpageapp.kesug.com/backend/update_presence.php", {
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
                addMessage(data.from || 'Unknown', data.message, false, null, null, data.timestamp);
            } else if (data.type === "group_message_history") {
                // Clear messages area and show history
                const messagesDiv = document.getElementById("messages");
                messagesDiv.innerHTML = "";
                data.messages.forEach(msg => {
                    addMessage(msg.from, msg.message, false, null, null, msg.created_at);
                });
            } else if (data.type === "private_message") {
                const peer = data.from === username ? data.to : data.from;
                if (data.from !== username) {
                    openPrivateChat(peer);
                    addMessage(data.from, data.message, true, peer, null, data.timestamp);
                } else {
                    addMessage(data.from, data.message, true, peer, null, data.timestamp);
                }
            } else if (data.type === "old_private_messages") {
                // Show private message history in the private chat window
                if (data.messages && data.messages.length > 0) {
                    const peer = data.messages[0].from_user === username
                        ? data.messages[0].to_user
                        : data.messages[0].from_user;
                    openPrivateChat(peer);
                    const msgContainer = document.getElementById(`private-chat-${peer}-messages`);
                    msgContainer.innerHTML = "";
                    data.messages.forEach(msg => {
                        addMessage(msg.from_user, msg.message, true, peer, msgContainer, msg.timestamp);
                    });
                }
            } else if (data.type === "online_users") {
                updateOnlineUsers(data.users);
            } else if (data.type === "typing" && data.from !== username) {
                showTypingIndicator(data.from);
            }
        };

        ws.onerror = (error) => {
            console.error("WebSocket error:", error);
        };

        ws.onclose = () => updatePresence(0);
        window.addEventListener("beforeunload", () => {
            if (ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ type: "leave", username }));
            }
            updatePresence(0);
        });

        document.getElementById("send").addEventListener("click", () => {
            const input = document.getElementById("input");
            const text = input.value.trim();
            if (!text) return;
            const payload = {
                type: selectedUser ? "private_message" : "message",
                from: username,
                to: selectedUser || undefined,
                message: encrypt(text),
                timestamp: formatTime()
            };
            ws.send(JSON.stringify(payload));
            input.value = "";
            document.getElementById("send").style.transform = "scale(0.95)";
            setTimeout(() => document.getElementById("send").style.transform = "", 120);
        });

        document.getElementById("input").addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                document.getElementById("send").click();
            } else {
                ws.send(JSON.stringify({ type: "typing", from: username }));
            }
        });

        document.getElementById("fileBtn").addEventListener("click", () => {
            document.getElementById("fileInput").click();
        });

        document.getElementById("fileInput").addEventListener("change", async () => {
            const fileInput = document.getElementById("fileInput");
            const file = fileInput.files[0];
            if (!file) return;
            const storageRef = storage.ref().child('chat_files/' + Date.now() + '_' + file.name);
            try {
                const snapshot = await storageRef.put(file);
                const url = await snapshot.ref.getDownloadURL();
                const fileMsg = `[File: ${file.name}]\n${url}`;
                const payload = {
                    type: selectedUser ? "private_message" : "message",
                    from: username,
                    to: selectedUser || undefined,
                    message: encrypt(fileMsg),
                    timestamp: formatTime()
                };
                ws.send(JSON.stringify(payload));
                // Do NOT call addMessage here; let the server echo it back
                fileInput.value = "";
            } catch (e) {
                alert("Upload failed.");
                fileInput.value = "";
            }
        });

        document.getElementById("emojiBtn").addEventListener("click", () => {
            const picker = document.getElementById("emoji-picker");
            picker.style.display = picker.style.display === "block" ? "none" : "block";
        });

        // Sign out logic
        document.getElementById("signout-btn").addEventListener("click", async function() {
            try {
                await fetch("logout.php", { method: "POST", credentials: "same-origin" });
            } catch (e) {}
            window.location.href = "login.php";
        });

        function updateOnlineUsers(users) {
            const onlineDiv = document.getElementById("online-users");
            onlineDiv.innerHTML = users.filter(u => u.online == 1)
                .map(u => `<span class="user" data-username="${escapeHTML(u.username)}">
                            <span class="user-avatar">${getInitials(u.username)}</span>${escapeHTML(u.username)}
                         </span>`).join("");
            document.querySelectorAll(".user").forEach(el => {
                el.addEventListener("click", () => {
                    const to = el.dataset.username;
                    if (to !== username) openPrivateChat(to);
                });
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
                    <b>Private Chat with ${escapeHTML(to)}</b>
                    <span class="close-tab" onclick="closePrivateChat('${escapeHTML(to)}')">X</span>
                </div>
                <div class="private-messages" id="private-chat-${to}-messages"></div>
                <div class="private-input-wrapper" style="position: relative;">
                    <input type="text" class="private-input" id="private-input-${to}" placeholder="Type a message" autocomplete="off">
                    <button onclick="sendPrivateMessage('${escapeHTML(to)}')" id="private-send-btn-${to}">Send</button>
                    <button onclick="toggleEmojiPicker('${escapeHTML(to)}')" id="private-emoji-btn-${to}">&#128515;</button>
                    <input type="file" id="fileInput-${to}" style="display:none;">
                    <button onclick="document.getElementById('fileInput-${to}').click();" id="private-file-btn-${to}">&#128206;</button>
                    <div id="emoji-picker-${to}" class="emoji-picker" style="display:none; position:absolute; bottom: 40px; left: 0; background:#fff; border:1px solid #ccc; padding:5px; border-radius:5px; z-index:100;">
                        <span class="emoji" onclick="addEmojiTo('${escapeHTML(to)}', '😊')">😊</span>
                        <span class="emoji" onclick="addEmojiTo('${escapeHTML(to)}', '😂')">😂</span>
                        <span class="emoji" onclick="addEmojiTo('${escapeHTML(to)}', '😢')">😢</span>
                        <span class="emoji" onclick="addEmojiTo('${escapeHTML(to)}', '😎')">😎</span>
                    </div>
                </div>
            `;
            document.getElementById("private-chat-area-container").appendChild(chatArea);
            selectedUser = to;
            document.getElementById(`private-input-${to}`).addEventListener("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    document.getElementById(`private-send-btn-${to}`).click();
                } else {
                    ws.send(JSON.stringify({ type: "typing", from: username }));
                }
            });
            document.getElementById(`fileInput-${to}`).addEventListener("change", async () => {
                const fileInput = document.getElementById(`fileInput-${to}`);
                const file = fileInput.files[0];
                if (!file) return;
                const storageRef = storage.ref().child('chat_files/' + Date.now() + '_' + file.name);
                try {
                    const snapshot = await storageRef.put(file);
                    const url = await snapshot.ref.getDownloadURL();
                    const fileMsg = `[File: ${file.name}]\n${url}`;
                    const payload = {
                        type: "private_message",
                        from: username,
                        to,
                        message: encrypt(fileMsg),
                        timestamp: formatTime()
                    };
                    ws.send(JSON.stringify(payload));
                    // Do NOT call addMessage here; let the server echo it back
                    fileInput.value = "";
                } catch (e) {
                    alert("Upload failed.");
                    fileInput.value = "";
                }
            });
            fetch(`https://chatpageapp.kesug.com/backend/get_private_messages.php?user1=${encodeURIComponent(username)}&user2=${encodeURIComponent(to)}`)
                .then(res => res.json())
                .then(messages => {
                    const msgContainer = document.getElementById(`private-chat-${to}-messages`);
                    if (messages.status === "success") {
                        messages.messages.forEach(msg => {
                            addMessage(msg.from, msg.message, true, to, msgContainer, msg.timestamp);
                        });
                    }
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
                message: encrypt(msg),
                timestamp: formatTime()
            };
            ws.send(JSON.stringify(payload));
            document.getElementById(`private-send-btn-${to}`).style.transform = "scale(0.95)";
            setTimeout(() => document.getElementById(`private-send-btn-${to}`).style.transform = "", 120);
            fetch("https://chatpageapp.kesug.com/backend/save_private_message.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    from: username,
                    to,
                    message: msg
                })
            });
            addMessage(username, payload.message, true, to);
            input.value = "";
        }

        function closePrivateChat(to) {
            const el = document.getElementById(`private-chat-${to}`);
            if (el) el.remove();
            selectedUser = null;
        }

        function toggleEmojiPicker(to) {
            const picker = document.getElementById(`emoji-picker-${to}`);
            picker.style.display = (picker.style.display === "block") ? "none" : "block";
        }

        function addEmojiTo(to, emoji) {
            const input = document.getElementById(`private-input-${to}`);
            input.value += emoji;
            toggleEmojiPicker(to);
        }

        setInterval(() => {
            fetch("https://chatpageapp.kesug.com/backend/presence.php")
                .then(res => res.json())
                .then(data => updateOnlineUsers(data))
                .catch(err => console.error("Presence update error:", err));
        }, 5000);
    </script>
</body>
</html>
