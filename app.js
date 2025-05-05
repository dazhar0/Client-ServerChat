let socket = new WebSocket("wss://client-serverchat.onrender.com");

socket.onopen = () => {
    console.log("Connected to WebSocket");
};

socket.onmessage = (event) => {
    const message = JSON.parse(event.data);
    displayMessage(message);
};

socket.onclose = () => {
    console.log("Disconnected from WebSocket");
};

socket.onerror = (error) => {
    console.error("WebSocket Error: " + error);
};

function sendMessage(message) {
    socket.send(JSON.stringify({ message: message }));
}

function displayMessage(message) {
    const chatBox = document.getElementById("chat-box");
    const messageElement = document.createElement("div");
    messageElement.textContent = message.text;
    chatBox.appendChild(messageElement);
}

document.getElementById("send-message").addEventListener("click", () => {
    const message = document.getElementById("message-input").value;
    sendMessage(message);
    document.getElementById("message-input").value = '';
});
