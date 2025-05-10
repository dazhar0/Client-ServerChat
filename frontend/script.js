let socket;
let username = "";
let onlineUsers = [];

function connect() {
  username = document.getElementById("usernameInput").value;
  
  if (!username) {
    alert("Please enter a username!");
    return;
  }

  socket = new WebSocket("wss://client-serverchat.onrender.com");

  socket.onopen = () => {
    console.log("Connected to WebSocket server");
    document.getElementById("loginSection").style.display = "none";
    document.getElementById("chatSection").style.display = "block";

    // Send username to server
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
    alert("WebSocket error. Try refreshing.");
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
  document.getElementById("onlineUsers").textContent = users.join(", ");
}
