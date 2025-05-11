const WebSocket = require('ws');
const mysql = require('mysql2');
require('dotenv').config();

const server = new WebSocket.Server({ port: process.env.PORT || 8080 });

let onlineUsers = {}; // { username: WebSocket }

const db = mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME
});

db.connect((err) => {
    if (err) {
        console.error('Database connection error:', err);
    } else {
        console.log('Connected to MySQL');
    }
});

server.on('connection', (ws) => {
    let username = null;

    ws.on('message', (msg) => {
        let data;
        try {
            data = JSON.parse(msg);
        } catch (e) {
            console.error('Invalid JSON:', msg);
            return;
        }

        if (data.type === 'join') {
            username = data.username;
            onlineUsers[username] = ws;
            updatePresence(username, 1);
            loadOfflineMessages(username);
            sendOnlineUsers();
            broadcast({ type: 'user_joined', username });

        } else if (data.type === 'leave') {
            if (username) {
                updatePresence(username, 0);
                delete onlineUsers[username];
                sendOnlineUsers();
                broadcast({ type: 'user_left', username });
            }

        } else if (data.type === 'message') {
            const payload = {
                type: 'message',
                from: data.from,
                message: data.message
            };
            broadcast(payload);
            savePublicMessage(data.from, data.message);

        } else if (data.type === 'private_message') {
            const toUser = data.to;
            const payload = {
                type: 'private_message',
                from: data.from,
                to: toUser,
                message: data.message
            };

            // Send to recipient if online
            if (data.from !== data.to && onlineUsers[data.to]) {
                onlineUsers[data.to].send(JSON.stringify(payload));  // Send only to recipient
            }
            
            // Always send a copy to sender (this might be unnecessary if not required)
            if (onlineUsers[data.from]) {
                onlineUsers[data.from].send(JSON.stringify(payload));
            }

            // Save once only
            savePrivateMessage(data.from, toUser, data.message);
        }
    });

    ws.on('close', () => {
        if (username) {
            updatePresence(username, 0);
            delete onlineUsers[username];
            sendOnlineUsers();
            broadcast({ type: 'user_left', username });
        }
    });

    function broadcast(data) {
        const msg = JSON.stringify(data);
        for (let user in onlineUsers) {
            onlineUsers[user].send(msg);
        }
    }

    function sendOnlineUsers() {
        const userList = Object.keys(onlineUsers).map(user => ({ username: user, online: 1 }));
        const payload = JSON.stringify({ type: 'online_users', users: userList });
        for (let user in onlineUsers) {
            onlineUsers[user].send(payload);
        }
    }

    function updatePresence(username, status) {
        db.query("UPDATE users SET online = ? WHERE username = ?", [status, username], (err) => {
            if (err) console.error('Presence error:', err);
        });
    }

    function savePublicMessage(from, message) {
        db.query("INSERT INTO messages (`from`, message, is_private) VALUES (?, ?, 0)", [from, message], (err) => {
            if (err) console.error('Save public message error:', err);
        });
    }

    function savePrivateMessage(from, to, message) {
        const timestamp = new Date().toISOString();
        db.query("INSERT INTO private_messages (from_user, to_user, message, timestamp, delivered) VALUES (?, ?, ?, ?, ?)", 
            [from, to, message, timestamp, onlineUsers[to] ? 1 : 0], 
            (err) => {
                if (err) console.error('Save private message error:', err);
            }
        );
    }

    function loadOfflineMessages(username) {
        db.query("SELECT * FROM private_messages WHERE to_user = ? AND delivered = 0", [username], (err, results) => {
            if (err) {
                console.error('Load offline messages error:', err);
                return;
            }
            results.forEach(row => {
                if (onlineUsers[username]) {
                    onlineUsers[username].send(JSON.stringify({
                        type: "private_message",
                        from: row.from_user,
                        to: row.to_user,
                        message: row.message
                    }));
                    db.query("UPDATE private_messages SET delivered = 1 WHERE id = ?", [row.id]);
                }
            });
        });
    }
});

console.log("WebSocket server running on port", process.env.PORT || 8080);
