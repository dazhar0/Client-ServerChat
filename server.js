const http = require('http');
const WebSocket = require('ws');
const PORT = process.env.PORT || 3000;

const server = http.createServer();
const wss = new WebSocket.Server({ server, path: "/ws" });

let clients = new Map();

wss.on('connection', (ws) => {
  let username = "";

  ws.on('message', (msg) => {
    try {
      const data = JSON.parse(msg);

      if (data.type === "join") {
        username = data.username;
        clients.set(ws, username);
        broadcastPresence();
      }

      if (data.type === "message") {
        broadcast({
          type: "message",
          username: data.username,
          message: data.message
        });
      }
    } catch (e) {
      console.error("Invalid message format", e);
    }
  });

  ws.on('close', () => {
    clients.delete(ws);
    broadcastPresence();
  });
});

function broadcast(msgObj) {
  const data = JSON.stringify(msgObj);
  for (let client of clients.keys()) {
    if (client.readyState === WebSocket.OPEN) {
      client.send(data);
    }
  }
}

function broadcastPresence() {
  const userList = Array.from(clients.values());
  broadcast({ type: "presence", users: userList });
}

server.listen(PORT, () => console.log(`WebSocket server listening on port ${PORT}`));
