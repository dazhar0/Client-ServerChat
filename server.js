const https = require('https');
const WebSocket = require('ws');

// Create an HTTPS server (automatically handles SSL on Render)
const server = https.createServer((req, res) => {
  res.writeHead(200);
  res.end('WebSocket Server is running');
});

// Create the WebSocket server
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

// Start the server on the port Render provides (443 for HTTPS/WSS)
const port = process.env.PORT || 443;
server.listen(port, () => {
  console.log(`WebSocket server running on wss://client-serverchat.onrender.com`);
});
