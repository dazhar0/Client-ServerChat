<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"));
$username = htmlspecialchars($data->username);
$password = $data->password;

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
?>
