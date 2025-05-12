<?php
// WARNING: Replace with your actual secret key. Do NOT commit secrets to public repositories.
define('SECRET_KEY', 'REPLACE_WITH_YOUR_SECRET_KEY');

// Function to decrypt the message using AES
function decryptMessage($encryptedMessage) {
    $iv = substr($encryptedMessage, 0, 16); // Get the IV from the first 16 bytes
    $encryptedMessage = substr($encryptedMessage, 16); // Get the rest of the encrypted message

    // Decrypt using OpenSSL
    $decrypted = openssl_decrypt(base64_decode($encryptedMessage), 'AES-128-CBC', SECRET_KEY, 0, $iv);
    return $decrypted;
}

// Example usage
$encryptedMessage = $_POST['message']; // The message from the frontend
$decryptedMessage = decryptMessage($encryptedMessage);
echo $decryptedMessage;
?>
