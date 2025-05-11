const key = "supersecretkey123"; // Replace with a secure key exchange later

function encryptMessage(msg) {
  return btoa(unescape(encodeURIComponent(msg))); // base64 encode
}

function decryptMessage(msg) {
  try {
    return decodeURIComponent(escape(atob(msg)));
  } catch {
    return msg;
  }
}
