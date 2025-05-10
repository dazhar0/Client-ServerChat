let socket = null;
let username = "";

function connect() {
  username = document.getElementById("usernameInput").value.trim();
  if (!username) {
    alert("Enter a username");
    return;
  }

  // Change this to your deployed Render WebSocket URL
  const wsUrl = "wss://client-serverchat.onrender.com";

  socket = new WebSocket(wsUrl);

  socket.onopen = () => {
    socket.send(JSON.stringify({ username }));
    document.getElementById("loginSection").style.display = "none";
    document.getElementById("chatSection").style.display = "block";
  };

  socket.onmessage = (event) => {
    const data = JSON.parse(event.data);

    if (data.type === "presence") {
      document.getElementById("onlineUsers").textContent = data.users.join(", ");
    } else {
      const messageBox = document.getElementById("messages");
      const msg = document.createElement("div");
      msg.innerText = `${data.from}: ${data.message}`;
      messageBox.appendChild(msg);
      messageBox.scrollTop = messageBox.scrollHeight;
    }
  };

  socket.onclose = () => {
    alert("Disconnected. Please refresh the page.");
  };
}

function sendMessage() {
  const input = document.getElementById("messageInput");
  const text = input.value.trim();
  if (text && socket) {
    socket.send(JSON.stringify({ message: text }));
    input.value = "";
  }
}
