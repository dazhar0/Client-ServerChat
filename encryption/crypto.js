const key = CryptoJS.enc.Utf8.parse("1234567812345678"); // Use proper key sharing in real life
const iv = CryptoJS.enc.Utf8.parse("1234567812345678");

function encrypt(text) {
  return CryptoJS.AES.encrypt(text, key, { iv }).toString();
}

function decrypt(cipher) {
  const bytes = CryptoJS.AES.decrypt(cipher, key, { iv });
  return bytes.toString(CryptoJS.enc.Utf8);
}
