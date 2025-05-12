const CryptoJS = require('crypto-js'); // If using Node.js, otherwise use CDN in the HTML

// WARNING: Replace with a securely generated key. Do NOT commit secrets to public repositories.
const secretKey = "REPLACE_WITH_YOUR_SECRET_KEY";

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
