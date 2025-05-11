<?php
// Start the session
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];  // Get the logged-in username from the session
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureChat - Chat</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 0; background: #f5f5f5; }
        #chat-container { display: flex; flex-direction: column; height: 100vh; }
        #messages { flex: 1; padding: 10px; overflow-y: auto; background: #fff; }
        #input-area { display: flex; padding: 10px; background: #eee; }
        #input { flex: 1; padding: 10px; font-size: 16px; }
        #send, #fileBtn { padding: 10px; margin-left: 5px; cursor: pointer; }
        .message { margin: 5px 0; }
        .sender { font-weight: bold; margin-right: 5px; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>
<body>
    <div id="chat-container">
        <div id="messages"></div>
        <div id="input-area">
            <input type="text" id="input" placeholder="Type your message with emojis 😊" />
            <input type="file" id="fileInput" style="display: none;" />
            <button id="fileBtn">📎</button>
            <button id="send">Send</button>
        </div>
    </div>

    <script>
        const username = "<?php echo $username; ?>"; // PHP to JS: Pass logged-in username from session
        const messagesDiv = document.getElementById("messages");
        const input = document.getElementById("input");
        const sendBtn = document.getElementById("send");
        const fileInput = document.getElementById("fileInput");
        const fileBtn = document.getElementById("fileBtn");

        const ws = new WebSocket("wss://client-serverchat.onrender.com");

        const SECRET_KEY = "your-very-strong-secret";

        function encrypt(text) {
            return CryptoJS.AES.encrypt(text, SECRET_KEY).toString();
        }

        function decrypt(text) {
            const bytes = CryptoJS.AES.decrypt(text, SECRET_KEY);
            return bytes.toString(CryptoJS.enc.Utf8) || "[Failed to decrypt]";
        }

        ws.onopen = () => {
            ws.send(JSON.stringify({ type: "join", username }));
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.type === "message") {
                const msg = document.createElement("div");
                msg.className = "message";
                msg.innerHTML = `<span class="sender">${data.username}:</span> ${decrypt(data.message)}`;
                messagesDiv.appendChild(msg);
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
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

        fileInput.onchange = () => {
            const file = fileInput.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function () {
                const base64 = reader.result;
                const payload = {
                    type: "message",
                    username,
                    message: encrypt(`[File: ${file.name}]\n${base64}`)
                };
                ws.send(JSON.stringify(payload));
            };
            reader.readAsDataURL(file);
        };

        window.addEventListener("beforeunload", () => {
            ws.send(JSON.stringify({ type: "leave", username }));
        });
    </script>
</body>
</html>
