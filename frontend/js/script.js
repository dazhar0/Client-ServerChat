let socket;
let username = localStorage.getItem("username") || "";
let serverUrl = "wss://client-serverchat.onrender.com/ws"; // Replace with your Render WebSocket URL

function connectWebSocket() {
  if (!username) return;

  socket = new WebSocket(serverUrl);

  socket.onopen = () => {
    console.log("Connected to WebSocket");
    socket.send(JSON.stringify({ type: "join", username }));
  };

  socket.onmessage = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === "message") {
      displayMessage(data);
    } else if (data.type === "presence") {
      updateOnlineUsers(data.users);
    }
  };

  socket.onerror = (err) => console.error("WebSocket error", err);
  socket.onclose = () => {
    console.warn("Disconnected. Reconnecting...");
    setTimeout(connectWebSocket, 3000); // auto-reconnect
  };
}

function sendMessage() {
  const input = document.getElementById("messageInput");
  const message = input.value;
  if (!message || !socket || socket.readyState !== 1) return;

  const encrypted = encryptMessage(message);
  socket.send(JSON.stringify({ type: "message", username, message: encrypted }));
  displayMessage({ username, message }); // display local copy
  input.value = "";
}

function displayMessage({ username, message }) {
  const container = document.getElementById("messages");
  const el = document.createElement("div");
  el.textContent = `${username}: ${decryptMessage(message)}`;
  container.appendChild(el);
  container.scrollTop = container.scrollHeight;
}

function updateOnlineUsers(users) {
  document.getElementById("onlineUsers").textContent = `Online: ${users.join(", ")}`;
}

document.addEventListener("DOMContentLoaded", () => {
  const input = document.getElementById("messageInput");
  input?.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });

  username = localStorage.getItem("username");
  if (username) connectWebSocket();
});
