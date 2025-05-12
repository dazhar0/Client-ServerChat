let socket;
let username = localStorage.getItem("username") || "";
const serverUrl = "wss://YOUR_WEBSOCKET_SERVER_URL"; // WebSocket server URL

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

  // Add this if you have a file input for the main chat
  document.getElementById("fileInput")?.addEventListener("change", async function() {
    const fileInput = document.getElementById("fileInput");
    const file = fileInput.files[0];
    if (!file) return;
    // You need to implement file upload logic here (e.g., Firebase or your backend)
    // For demonstration, we'll just show the file name in the chat
    const fileMsg = `[File: ${file.name}]`;
    socket.send(JSON.stringify({ type: "message", username, message: encryptMessage(fileMsg) }));
    displayMessage({ username, message: encryptMessage(fileMsg) });
    fileInput.value = "";
  });
});
