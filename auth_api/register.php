<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$email = $data['email'] ?? '';
$public_key = $data['public_key'] ?? '';

if (!$username || !$password || !$email || !$public_key) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

$password_hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, public_key) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password_hash, $email, $public_key]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Username or email already exists']);
}
?>
