const socket = new WebSocket("wss://client-serverchat.onrender.com");

socket.onopen = () => console.log("WebSocket connected");
socket.onmessage = (event) => {
  const data = JSON.parse(event.data);
  const decrypted = decrypt(data.message); // from crypto.js
  document.getElementById("chatLog").value += `${data.sender}: ${decrypted}\n`;
};

function sendMessage() {
  const toUser = document.getElementById("toUser").value;
  const message = document.getElementById("message").value;
  const encrypted = encrypt(message); // from crypto.js
  socket.send(JSON.stringify({ sender: "myUsername", receiver: toUser, message: encrypted }));
}
