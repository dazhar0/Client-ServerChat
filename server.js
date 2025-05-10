const http = require("http");
const WebSocket = require("ws");

const PORT = process.env.PORT || 10000;

// Create an HTTP server
const server = http.createServer(); // required for Render

// Attach WebSocket server to the HTTP server
const wss = new WebSocket.Server({ server });

wss.on("connection", (ws) => {
  console.log("New client connected");

  ws.on("message", (data) => {
    console.log("Received:", data.toString());

    // Broadcast to others
    wss.clients.forEach((client) => {
      if (client !== ws && client.readyState === WebSocket.OPEN) {
        client.send(data.toString());
      }
    });
  });

  ws.on("close", () => console.log("Client disconnected"));
  ws.on("error", (err) => console.error("Error:", err));

  // For heartbeat
  ws.isAlive = true;
  ws.on("pong", () => (ws.isAlive = true));
});

// Heartbeat to terminate stale connections
setInterval(() => {
  wss.clients.forEach((ws) => {
    if (ws.isAlive === false) return ws.terminate();
    ws.isAlive = false;
    ws.ping();
  });
}, 30000);

// Start HTTP server on Render's assigned port
server.listen(PORT, () => {
  console.log(`WebSocket server listening on port ${PORT}`);
});
