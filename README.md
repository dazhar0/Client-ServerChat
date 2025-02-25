# Team Member Names
Daniyal Azhar, Adrian Perez (Group 16)

# Secure WebSocket Server and Client Communication
This project demonstrates how to set up a secure WebSocket server and client communication using SSL certificates with Python. The server and client will communicate over a secure WebSocket (wss) connection.

# Prerequisites
Python 3.x installed on your local system
OpenSSL installed for generating SSL certificates
websockets and ssl Python libraries



# Installation
Clone the repository:
git clone https://github.com/dazhar0/Client-ServerChat.git
cd Client-ServerChat
Install dependencies:
pip install websockets



# Generating SSL Certificates
You can provide SSL certificate by however you deem best.
You can generate SSL certificates, you can use GitBash. Run the following commands:

# Launch Gitbash then Enter folder in GitBash
cd Client-ServerChat

# Generate a private key and certificate
openssl req -newkey rsa:2048 -nodes -keyout key.pem -x509 -days 365 -out cert.pem



# You are going to need your IP Address to place into the files
Enter client.py file and where YOURIPADDRESS replace with your IP Address


# Running the Server
To start the WebSocket server, run:
python server.py

# Running the Client
To start the WebSocket client, run:
python client.py


# Project Structure
Client-ServerChat/  
├── private.key<br />
├── certificate.crt<br />
├── client.py<br />
├── server.py<br />
└── README.md<br />