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
        body { font-family: Arial; margin: 0; padding: 0; background: #f5f5f5; }
        #chat-container { display: flex; flex-direction: column; height: 100vh; }
        #online-users { padding: 10px; background: #ddd; max-height: 100px; overflow-y: auto; }
        #messages { flex: 1; padding: 10px; overflow-y: auto; background: #fff; }
        #input-area { display: flex; padding: 10px; background: #eee; }
        #input { flex: 1; padding: 10px; font-size: 16px; }
        #send, #fileBtn { padding: 10px; margin-left: 5px; cursor: pointer; }
        .message { margin: 5px 0; }
        .sender { font-weight: bold; margin-right: 5px; }
        .user { cursor: pointer; color: blue; }
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
            messagesDiv.appendChild(msg);
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

        fileBtn.onclick = () => fileInput.click();

        let selectedUser = null; // track if sending private file

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

        function fetchOnlineUsers() {
            fetch("backend/presence.php")
                .then(res => res.json())
                .then(users => {
                    onlineDiv.innerHTML = "<b>Online Users:</b><br>" +
                        users.filter(u => u.online == 1)
                            .map(u => `<span class="user" data-username="${u.username}">${u.username}</span>`)
                            .join("<br>");

                    document.querySelectorAll('.user').forEach(userEl => {
                        userEl.onclick = () => {
                            const to = userEl.dataset.username;
                            if (to === username) return alert("You can't message yourself.");
                            const text = prompt(`Send private message to ${to}:`);
                            if (text) {
                                const payload = {
                                    type: "private_message",
                                    from: username,
                                    to: to,
                                    message: encrypt(text)
                                };
                                ws.send(JSON.stringify(payload));
                            }
                            // set for private file send
                            selectedUser = to;
                        };
                    });
                });
        }

        fetchOnlineUsers();
        setInterval(fetchOnlineUsers, 5000);

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
