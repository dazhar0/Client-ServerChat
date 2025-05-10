const http = require("http");
const WebSocket = require("ws");

const PORT = process.env.PORT || 10000;

// Create an HTTP server
const server = http.createServer();

// Create WebSocket server and attach it to the HTTP server
const wss = new WebSocket.Server({ server });

// Log when the server starts
server.listen(PORT, () => {
  console.log(`WebSocket server is running on port ${PORT}`);
});

// Handle WebSocket connections
wss.on("connection", (ws) => {
  console.log("New client connected");

  ws.on("message", (data) => {
    console.log("Received message:", data.toString());

    // Broadcast to other clients
    wss.clients.forEach((client) => {
      if (client !== ws && client.readyState === WebSocket.OPEN) {
        client.send(data.toString());
      }
    });
  });

  ws.on("close", () => {
    console.log("Client disconnected");
  });

  ws.on("error", (err) => {
    console.error("WebSocket error:", err);
  });

  // Mark connection as alive
  ws.isAlive = true;
  ws.on("pong", () => ws.isAlive = true);
});

// Optional: Heartbeat to close dead connections
setInterval(() => {
  wss.clients.forEach((ws) => {
    if (ws.isAlive === false) return ws.terminate();
    ws.isAlive = false;
    ws.ping();
  });
}, 30000);
