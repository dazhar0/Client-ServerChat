const https = require('https');
const WebSocket = require('ws');

// Create an HTTPS server (Render automatically handles SSL)
const server = https.createServer((req, res) => {
  // Respond to HTTP requests (you can customize this to return HTML or any content)
  res.writeHead(200, { 'Content-Type': 'text/html' });
  res.end('<h1>Welcome to Secure Chat!</h1><p>The WebSocket server is running!</p>');
});

// Create WebSocket server
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
    // Remove user when they disconnect
    users = users.filter(user => user.ws !== ws);
    sendPresenceUpdate();
  });

  // Broadcast message to all connected clients except the sender
  function broadcastMessage(data) {
    users.forEach(user => {
      if (user.ws !== ws) { // Don't send back to the sender
        user.ws.send(JSON.stringify({
          type: "message",
          username: data.username,
          message: data.message
        }));
      }
    });
  }

  // Send the list of online users to everyone
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

// Ensure the server listens on the appropriate port (Render will assign it automatically)
const port = process.env.PORT || 443;
server.listen(port, () => {
  console.log(`WebSocket server running on wss://client-serverchat.onrender.com`);
});
