const WebSocket = require('ws');
const mysql = require('mysql2');
require('dotenv').config();

const server = new WebSocket.Server({ port: process.env.PORT || 8080 });

let onlineUsers = {}; // { username: WebSocket }

// Use a connection pool instead of a single connection
const pool = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Remove db.connect(...) -- not needed for pools

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

            // Send latest group messages to the user
            sendGroupMessages(ws);

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

            // Save once only
            savePrivateMessage(data.from, toUser, data.message);
        }

        // Fetch old messages when a user opens a chat with another user
        if (data.type === 'open_private_chat') {
            const toUser = data.to;
            const fromUser = data.from;
            
            // Fetch old private messages
            fetchOldMessages(fromUser, toUser, (messages) => {
                // Send old messages to the client
                ws.send(JSON.stringify({
                    type: 'old_private_messages',
                    messages: messages
                }));
            });
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

    // Function to fetch old private messages
    function fetchOldMessages(fromUser, toUser, callback) {
        pool.query(
            "SELECT * FROM private_messages WHERE (from_user = ? AND to_user = ?) OR (from_user = ? AND to_user = ?) ORDER BY timestamp ASC",
            [fromUser, toUser, toUser, fromUser],
            (err, results) => {
                if (err) {
                    console.error('Error fetching old messages:', err);
                    callback([]);
                    return;
                }

                // Format messages and pass to callback
                const messages = results.map(row => ({
                    from_user: row.from_user,
                    to_user: row.to_user,
                    message: row.message,
                    timestamp: row.timestamp
                }));

                callback(messages);
            }
        );
    }

    // Fetch and send latest group messages to a user
    function sendGroupMessages(ws) {
        // Fetch last 50 public messages (adjust as needed)
        pool.query(
            "SELECT `from`, message, created_at FROM messages WHERE is_private = 0 ORDER BY created_at ASC LIMIT 50",
            [],
            (err, results) => {
                if (err) {
                    console.error('Error fetching group messages:', err);
                    return;
                }
                ws.send(JSON.stringify({
                    type: 'group_message_history',
                    messages: results
                }));
            }
        );
    }

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
        pool.query("UPDATE users SET online = ? WHERE username = ?", [status, username], (err) => {
            if (err) console.error('Presence error:', err);
        });
    }

    function savePublicMessage(from, message) {
        pool.query("INSERT INTO messages (`from`, message, is_private) VALUES (?, ?, 0)", [from, message], (err) => {
            if (err) console.error('Save public message error:', err);
        });
    }

    function savePrivateMessage(from, to, message) {
        const timestamp = new Date().toISOString();
        pool.query("INSERT INTO private_messages (from_user, to_user, message, timestamp, delivered) VALUES (?, ?, ?, ?, ?)", 
            [from, to, message, timestamp, onlineUsers[to] ? 1 : 0], 
            (err) => {
                if (err) console.error('Save private message error:', err);
            }
        );
    }

    function loadOfflineMessages(username) {
        pool.query("SELECT * FROM private_messages WHERE to_user = ? AND delivered = 0", [username], (err, results) => {
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
                    pool.query("UPDATE private_messages SET delivered = 1 WHERE id = ?", [row.id]);
                }
            });
        });
    }
});

console.log("WebSocket server running on port", process.env.PORT || 8080);
