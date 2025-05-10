<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"));
$username = htmlspecialchars($data->username);
$password = password_hash($data->password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
echo json_encode([
    "success" => $stmt->execute([$username, $password])
]);
?>
