const WebSocket = require('ws');
const server = new WebSocket.Server({ port: process.env.PORT || 8080 });

let onlineUsers = {}; // stores users and their WebSocket connections

server.on('connection', (ws) => {
    let username = null;

    // When a message is received
    ws.on('message', (message) => {
        const data = JSON.parse(message);

        if (data.type === 'join') {
            username = data.username;
            onlineUsers[username] = ws;
            // Send the updated list of online users to the requesting user
            sendOnlineUsers();
            // Notify all users that a new user has joined
            broadcast({ type: 'user_joined', username });

            // Update user presence in the database (optional)
            updatePresence(username, 1);

        } else if (data.type === 'leave') {
            if (username) {
                // Notify all users that the user has left
                broadcast({ type: 'user_left', username });
                // Update user presence in the database (optional)
                updatePresence(username, 0);
                delete onlineUsers[username];
                sendOnlineUsers(); // Send the updated list of online users
            }

        } else if (data.type === 'message') {
            broadcast({ type: 'message', username: data.username, message: data.message });

        } else if (data.type === 'private_message') {
            const toUser = data.to;
            if (onlineUsers[toUser]) {
                onlineUsers[toUser].send(JSON.stringify({
                    type: 'private_message',
                    from: data.from,
                    to: data.to,
                    message: data.message
                }));
            }
        }
    });

    // When the connection is closed
    ws.on('close', () => {
        if (username) {
            delete onlineUsers[username];
            broadcast({ type: 'user_left', username });
            updatePresence(username, 0);
            sendOnlineUsers(); // Send the updated list of online users
        }
    });

    // Broadcast a message to all connected users
    function broadcast(data) {
        const message = JSON.stringify(data);
        for (const user in onlineUsers) {
            if (onlineUsers.hasOwnProperty(user)) {
                onlineUsers[user].send(message);
            }
        }
    }

    // Send the list of online users to all clients
    function sendOnlineUsers() {
        const users = Object.keys(onlineUsers).map(username => ({ username, online: 1 }));
        for (const user in onlineUsers) {
            onlineUsers[user].send(JSON.stringify({
                type: 'online_users',
                users: users
            }));
        }
    }

    // Update user's online presence in the database
    function updatePresence(username, status) {
        fetch("https://your-backend-url.com/backend/update_presence.php", {  // Update this URL
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, online: status })
        });
    }
});

console.log("WebSocket server is running on wss://client-serverchat.onrender.com");
