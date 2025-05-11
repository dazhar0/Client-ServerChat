const CryptoJS = require('crypto-js'); // If using Node.js, otherwise use CDN in the HTML

const secretKey = "supersecretkey123"; // Replace with a securely generated key (e.g., using a key exchange process)

// Encrypt a message
function encryptMessage(message) {
    const ciphertext = CryptoJS.AES.encrypt(message, secretKey).toString();
    return ciphertext;
}

// Decrypt a message
function decryptMessage(encryptedMessage) {
    const bytes = CryptoJS.AES.decrypt(encryptedMessage, secretKey);
    const originalMessage = bytes.toString(CryptoJS.enc.Utf8);
    return originalMessage;
}
