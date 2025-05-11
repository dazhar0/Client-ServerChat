const WebSocket = require('ws');

// Create an HTTP server (Render handles SSL)
const server = require('http').createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'text/html' });
  res.end('<h1>Welcome to Secure Chat!</h1><p>The WebSocket server is running!</p>');
});

// Create WebSocket server on the HTTP server
const wss = new WebSocket.Server({ server });

let users = []; // To track connected users

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
    users = users.filter(user => user.ws !== ws);
    sendPresenceUpdate();
  });

  function broadcastMessage(data) {
    users.forEach(user => {
      user.ws.send(JSON.stringify({
        type: "message",
        username: data.username,
        message: data.message
      }));
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

const port = process.env.PORT || 443;
server.listen(port, () => {
  console.log(`WebSocket server running on wss://client-serverchat.onrender.com`);
});
