const WebSocket = require("ws");
const axios = require("axios");
const wss = new WebSocket.Server({ port: 8080 });

const clients = new Map();

wss.on("connection", function connection(ws) {
    ws.on("message", async function incoming(message) {
        try {
            const data = JSON.parse(message);

            if (data.type === "join") {
                clients.set(data.username, ws);
                broadcastOnlineUsers();
            } else if (data.type === "leave") {
                clients.delete(data.username);
                broadcastOnlineUsers();
            } else if (data.type === "message") {
                broadcast({ type: "message", username: data.from, message: data.message });

                // Optional: store group messages if needed
            } else if (data.type === "private_message") {
                const receiverSocket = clients.get(data.to);
                const senderSocket = clients.get(data.from);

                // Send to receiver if online
                if (receiverSocket) {
                    receiverSocket.send(JSON.stringify({
                        type: "private_message",
                        from: data.from,
                        to: data.to,
                        message: data.message
                    }));
                }

                // Echo back to sender
                if (senderSocket && data.from !== data.to) {
                    senderSocket.send(JSON.stringify({
                        type: "private_message",
                        from: data.from,
                        to: data.to,
                        message: data.message
                    }));
                }

                // Store in DB
                await axios.post("https://chattitans.42web.io/backend/store_message.php", {
                    sender_id: data.from,
                    receiver_id: data.to,
                    message: data.message
                });
            }
        } catch (e) {
            console.error("Error:", e.message);
        }
    });

    ws.on("close", () => {
        for (const [user, client] of clients) {
            if (client === ws) {
                clients.delete(user);
                break;
            }
        }
        broadcastOnlineUsers();
    });
});

function broadcast(data) {
    const msg = JSON.stringify(data);
    for (const client of clients.values()) {
        client.send(msg);
    }
}

function broadcastOnlineUsers() {
    const users = Array.from(clients.keys()).map(username => ({ username, online: 1 }));
    broadcast({ type: "online_users", users });
}
