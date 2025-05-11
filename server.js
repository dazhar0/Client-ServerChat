const https = require('https');
const WebSocket = require('ws');

// Create an HTTPS server (Render will automatically handle SSL)
const server = https.createServer((req, res) => {
  res.writeHead(200);
  res.end('Secure WebSocket Server');
});

// Create WebSocket server attached to the HTTPS server
const wss = new WebSocket.Server({ server });

let users = []; // To keep track of online users

wss.on('connection', (ws) => {
  console.log('Client connected');
  
  ws.on('message', (message) => {
    const data = JSON.parse(message);

    if (data.type === 'join') {
      const user = { username: data.username, ws };
      users.push(user);
      sendPresenceUpdate();
    }

    if (data.type === 'message') {
      broadcastMessage(data);
    }
  });

  ws.on('close', () => {
    // Remove user when they disconnect
    users = users.filter(user => user.ws !== ws);
    sendPresenceUpdate();
  });

  function broadcastMessage(data) {
    users.forEach(user => {
      if (user.ws !== ws) { // Don't send the message back to the sender
        user.ws.send(JSON.stringify({
          type: "message",
          username: data.username,
          message: data.message
        }));
      }
    });
  }

  function sendPresenceUpdate() {
    const usernames = users.map(user => user.username);
    users.forEach(user => {
      user.ws.send(JSON.stringify({
        type: "presence",
        users: usernames
      }));
    });
  }
});

// Start the server on the port provided by Render
server.listen(process.env.PORT || 443, () => {
  console.log('WebSocket server listening on secure wss://');
});
