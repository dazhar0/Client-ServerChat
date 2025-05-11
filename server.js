const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: process.env.PORT || 3000 });

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

console.log("WebSocket server listening on port 3000");
