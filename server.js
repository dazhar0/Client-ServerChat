const WebSocket = require('ws');
const mysql = require('mysql2');
const server = new WebSocket.Server({ port: process.env.PORT || 8080 });

let onlineUsers = {}; // stores users and their WebSocket connections

require('dotenv').config();

const db = mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME
});

db.connect((err) => {
    if (err) {
        console.error('Error connecting to the database: ' + err.stack);
        return;
    }
    console.log('Connected to MySQL database');
});

server.on('connection', (ws) => {
    let username = null;

    // When a message is received
    ws.on('message', (message) => {
        const data = JSON.parse(message);
        console.log('Received message:', data); // Debugging: Show received messages

        if (data.type === 'join') {
            username = data.username;
            onlineUsers[username] = ws;
            loadOfflineMessages(username);
            console.log(`${username} has joined.`); // Debugging: Log user joining
            sendOnlineUsers(); // Send the updated list of online users
            broadcast({ type: 'user_joined', username });

            // Update user presence in the database
            updatePresence(username, 1);

        } else if (data.type === 'leave') {
            if (username) {
                console.log(`${username} has left.`); // Debugging: Log user leaving
                broadcast({ type: 'user_left', username });
                updatePresence(username, 0);
                delete onlineUsers[username];
                sendOnlineUsers(); // Send the updated list of online users
            }

        } else if (data.type === 'message') {
            const messageToSend = {
                type: 'message',
                from: data.from,
                message: data.message
            };
            broadcast(JSON.stringify(messageToSend));
            savePublicMessage(data.from, data.message); // Save the public message

        } else if (data.type === 'private_message') {
            const toUser = data.to;
            const message = data.message;

            if (onlineUsers[toUser]) {
                // If the user is online, send the private message directly
                onlineUsers[toUser].send(JSON.stringify({
                    type: 'private_message',
                    from: data.from,
                    to: data.to,
                    message
                }));
            } else {
                // If the user is offline, save the message to the database
                savePrivateMessage(data.from, toUser, message);
            }
        }
    });

    // When the connection is closed
    ws.on('close', () => {
        if (username) {
            console.log(`${username} has disconnected.`); // Debugging: Log when user disconnects
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
        console.log('Sending online users:', users); // Debugging: Log online users
        for (const user in onlineUsers) {
            onlineUsers[user].send(JSON.stringify({
                type: 'online_users',
                users: users
            }));
        }
    }

    // Update user's online presence in the database
    function updatePresence(username, status) {
        db.query('UPDATE users SET online = ? WHERE username = ?', [status, username], (err, result) => {
            if (err) {
                console.error('Error updating presence: ' + err.stack);
            }
        });
    }

    // Save private message to the database for offline user
    function savePrivateMessage(from, to, message) {
        const query = 'INSERT INTO private_messages (from_user, to_user, message, timestamp) VALUES (?, ?, ?, ?)';
        const timestamp = new Date().toISOString();

        db.query(query, [from, to, message, timestamp], (err, result) => {
            if (err) {
                console.error('Error saving private message: ' + err.stack);
            }
        });
    }

    // Save public message to the database (for history)
    function savePublicMessage(from, message) {
        const query = 'INSERT INTO messages (`from`, message, is_private) VALUES (?, ?, 0)';
        db.query(query, [from, message], (err) => {
            if (err) console.error('Error saving public message:', err);
        });
    }

    // Load offline private messages for a user
    function loadOfflineMessages(username) {
        db.query('SELECT * FROM private_messages WHERE to_user = ? AND delivered = 0', [username], (err, results) => {
            if (err) {
                console.error('Error loading offline messages:', err);
                return;
            }
            results.forEach(row => {
                if (onlineUsers[username]) {
                    onlineUsers[username].send(JSON.stringify({
                        type: 'private_message',
                        from: row.from_user,
                        to: row.to_user,
                        message: row.message
                    }));
                    // Mark message as delivered
                    db.query('UPDATE private_messages SET delivered = 1 WHERE id = ?', [row.id]);
                }
            });
        });
    }
});

console.log("WebSocket server is running on port " + (process.env.PORT || 8080));
