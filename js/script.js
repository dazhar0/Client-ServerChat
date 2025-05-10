let socket;
let username = "";

function connect() {
  username = document.getElementById("usernameInput").value;

  if (!username) {
    alert("Please enter a username!");
    return;
  }

  // Replace with your actual Render WebSocket URL
  socket = new WebSocket("wss://client-serverchat.onrender.com");

  socket.onopen = () => {
    console.log("Connected to WebSocket server");
    document.getElementById("loginSection").style.display = "none";
    document.getElementById("chatSection").style.display = "block";

    socket.send(JSON.stringify({ type: "join", username: username }));
  };

  socket.onmessage = (event) => {
    const messageData = JSON.parse(event.data);
    if (messageData.type === "message") {
      displayMessage(messageData);
    } else if (messageData.type === "presence") {
      updateOnlineUsers(messageData.users);
    }
  };

  socket.onerror = (error) => {
    console.error("WebSocket error:", error);
  };

  socket.onclose = () => {
    console.log("Disconnected from WebSocket server");
    alert("Disconnected. Please refresh the page.");
  };
}

function sendMessage() {
  const message = document.getElementById("messageInput").value;

  if (message) {
    const messageData = {
      type: "message",
      username: username,
      message: message,
    };
    socket.send(JSON.stringify(messageData));
    document.getElementById("messageInput").value = "";
  }
}

function displayMessage(data) {
  const messagesDiv = document.getElementById("messages");
  const messageElement = document.createElement("div");
  messageElement.textContent = `${data.username}: ${data.message}`;
  messagesDiv.appendChild(messageElement);
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function updateOnlineUsers(users) {
  const onlineUsersDiv = document.getElementById("onlineUsers");
  onlineUsersDiv.textContent = users.join(", ");
}
// Optional: Add simple form toggling
document.addEventListener("DOMContentLoaded", () => {
  // You could add JS-based redirection or success messages here
});
