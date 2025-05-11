<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>SecureChat - Main</title>
    <style>
        /* Original styling preserved */
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        #chat { width: 80%; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; }
        #messages { height: 300px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; background: #fafafa; }
        .message { margin-bottom: 10px; }
        .sender { font-weight: bold; }
        #input, .private-input { width: 80%; padding: 10px; margin-top: 10px; }
        button { padding: 10px; }
        #users { margin-top: 20px; }
        .user { cursor: pointer; color: blue; }
        .private-tab { border: 1px solid #ccc; margin-top: 10px; padding: 10px; background: #eef; }
        .close-tab { float: right; cursor: pointer; }
        .emoji { cursor: pointer; margin: 0 5px; }
    </style>
</head>
<body>
<div id="chat">
    <h2>Welcome, <?php echo $username; ?>!</h2>
    <div id="messages"></div>
    <input type="text" id="input" placeholder="Type a message..." />
    <input type="file" id="fileInput" style="display:none;" />
    <button id="send">Send</button>
    <button id="fileBtn">📎</button>
    <button onclick="addEmoji('😊')">😊</button>
    <button onclick="addEmoji('😂')">😂</button>
    <div id="users"></div>
    <div id="private-chats"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
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
            const bytes = CryptoJS.AES.decrypt(text, SECRET_KEY);
            const original = bytes.toString(CryptoJS.enc.Utf8);
            return original || "[Encrypted]";
        } catch (e) {
            return "[Invalid]";
        }
    }

    function addEmoji(emoji) {
        document.getElementById("input").value += emoji;
    }

    function addPrivateEmoji(user, emoji) {
        const input = document.getElementById(`input-${user}`);
        if (input) input.value += emoji;
    }

    function showMessage(from, msg, isPrivate = false, user = null) {
        const text = decrypt(msg);
        const div = document.createElement("div");
        div.className = "message";
        div.innerHTML = `<span class="sender">${from}${isPrivate ? " (private)" : ""}:</span> ${text}`;
        if (isPrivate && user) {
            const container = document.getElementById(`messages-${user}`);
            if (container) container.appendChild(div);
        } else {
            document.getElementById("messages").appendChild(div);
        }
    }

    function updateUsers(users) {
        const list = document.getElementById("users");
        list.innerHTML = "<h4>Online Users</h4>";
        users.filter(u => u.username !== username && u.online == 1).forEach(user => {
            const div = document.createElement("div");
            div.className = "user";
            div.textContent = user.username;
            div.onclick = () => openPrivateChat(user.username);
            list.appendChild(div);
        });
    }

    function openPrivateChat(user) {
        selectedUser = user;
        if (document.getElementById(`tab-${user}`)) return;

        const container = document.createElement("div");
        container.className = "private-tab";
        container.id = `tab-${user}`;
        container.innerHTML = `
            <div><b>Chat with ${user}</b> <span class="close-tab" onclick="closePrivateChat('${user}')">x</span></div>
            <div id="messages-${user}" style="height:150px; overflow-y:scroll; background:#fff; padding:5px; border:1px solid #ccc;"></div>
            <input type="text" id="input-${user}" class="private-input" placeholder="Type a message..." />
            <input type="file" id="file-${user}" style="display:none;" />
            <button onclick="sendPrivate('${user}')">Send</button>
            <button onclick="document.getElementById('file-${user}').click()">📎</button>
            <button onclick="addPrivateEmoji('${user}', '😊')">😊</button>
            <button onclick="addPrivateEmoji('${user}', '😂')">😂</button>
        `;
        document.getElementById("private-chats").appendChild(container);

        document.getElementById(`file-${user}`).onchange = () => {
            const file = document.getElementById(`file-${user}`).files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append("file", file);
            fetch("backend/upload_files.php", {
                method: "POST",
                body: formData
            }).then(res => res.json()).then(data => {
                if (data.url) {
                    const fileMsg = `[File: ${file.name}]\n${data.url}`;
                    const encrypted = encrypt(fileMsg);
                    ws.send(JSON.stringify({ type: "private_message", from: username, to: user, message: encrypted }));
                    showMessage(username, encrypted, true, user);
                }
            });
        };

        fetch(`backend/get_private_messages.php?user1=${username}&user2=${user}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(msg => showMessage(msg.from, msg.message, true, user));
            });
    }

    function sendPrivate(user) {
        const input = document.getElementById(`input-${user}`);
        const text = input.value.trim();
        if (!text) return;
        const encrypted = encrypt(text);
        ws.send(JSON.stringify({ type: "private_message", from: username, to: user, message: encrypted }));
        showMessage(username, encrypted, true, user);
        input.value = "";
    }

    function closePrivateChat(user) {
        const tab = document.getElementById(`tab-${user}`);
        if (tab) tab.remove();
        selectedUser = null;
    }

    document.getElementById("send").onclick = () => {
        const input = document.getElementById("input");
        const text = input.value.trim();
        if (!text) return;
        const encrypted = encrypt(text);
        ws.send(JSON.stringify({ type: "message", from: username, message: encrypted }));
        showMessage(username, encrypted);
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
                const encrypted = encrypt(fileMsg);
                ws.send(JSON.stringify({ type: "message", from: username, message: encrypted }));
                showMessage(username, encrypted);
            }
        });
    };

    ws.onopen = () => {
        ws.send(JSON.stringify({ type: "join", username }));
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.type === "message") {
            showMessage(data.from, data.message);
        } else if (data.type === "private_message") {
            const user = data.from === username ? data.to : data.from;
            openPrivateChat(user);
            showMessage(data.from, data.message, true, user);
        } else if (data.type === "online_users") {
            updateUsers(data.users);
        }
    };

    setInterval(() => {
        fetch("backend/presence.php")
            .then(res => res.json())
            .then(data => updateUsers(data));
    }, 5000);
</script>
</body>
</html>
