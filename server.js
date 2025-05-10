const http = require("http");
const WebSocket = require("ws");

const PORT = process.env.PORT || 10000; // Use Render's assigned port

// Create HTTP server
const server = http.createServer();

// Create WebSocket server bound to HTTP server
const wss = new WebSocket.Server({ server });

// Connection handling
wss.on("connection", (ws) => {
  console.log("New client connected");

  ws.on("message", (data) => {
    console.log("Received:", data.toString());

    wss.clients.forEach((client) => {
      if (client !== ws && client.readyState === WebSocket.OPEN) {
        client.send(data.toString());
      }
    });
  });

  ws.on("close", () => console.log("Client disconnected"));
  ws.on("error", (err) => console.error("WebSocket error:", err));

  ws.isAlive = true;
  ws.on("pong", () => (ws.isAlive = true));
});

// Heartbeat for dead connections
setInterval(() => {
  wss.clients.forEach((ws) => {
    if (ws.isAlive === false) return ws.terminate();
    ws.isAlive = false;
    ws.ping();
  });
}, 30000);

// ✅ Start HTTP server on correct port
server.listen(PORT, () => {
  console.log(`✅ WebSocket server listening on port ${PORT}`);
});
