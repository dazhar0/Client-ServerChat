let socket;
let username = localStorage.getItem("username") || "";
const serverUrl = "wss://client-serverchat.onrender.com"; // Replace with your Render WebSocket URL

function connectWebSocket() {
  if (!username) {
    username = prompt("Please enter your username:"); // Prompt the user if no username is set
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
    setTimeout(connectWebSocket, 3000); // auto-reconnect
  };
}

function sendMessage() {
  const input = document.getElementById("messageInput");
  const message = input.value;
  if (!message || !socket || socket.readyState !== 1) return;

  const encrypted = encryptMessage(message); // Ensure this is properly implemented
  socket.send(JSON.stringify({ type: "message", username, message: encrypted }));
  displayMessage({ username, message: encrypted }); // display local copy
  input.value = "";
}

function displayMessage({ username, message }) {
  const container = document.getElementById("messages");
  const el = document.createElement("div");
  el.textContent = `${username}: ${decryptMessage(message)}`; // Ensure this is properly implemented
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
