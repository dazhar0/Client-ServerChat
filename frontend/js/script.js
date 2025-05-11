let socket;
let username = localStorage.getItem("username") || "";
const serverUrl = "wss://client-serverchat.onrender.com"; // WebSocket server URL

function connectWebSocket() {
  if (!username) {
    username = prompt("Please enter your username:");
    localStorage.setItem("username", username);
  }

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
  socket.onclose = (event) => {
    console.warn("Disconnected. Reconnecting...");
    if (!event.wasClean) {
      console.error("WebSocket error:", event);
    }
    setTimeout(connectWebSocket, 3000);
  };
}

// Send a message (encrypt before sending)
function sendMessage() {
  const input = document.getElementById("messageInput");
  const message = input.value;
  if (!message || !socket || socket.readyState !== 1) return;

  const encryptedMessage = encryptMessage(message);
  socket.send(JSON.stringify({ type: "message", username, message: encryptedMessage }));
  displayMessage({ username, message: encryptedMessage });
  input.value = "";
}

// Display received messages (decrypt before displaying)
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

  if (username) connectWebSocket();
});
