<?php
require 'db.php';

$data = json_decode(file_get_contents("php://input"));
$sender = htmlspecialchars($data->sender);
$receiver = htmlspecialchars($data->receiver);
$message = htmlspecialchars($data->message);

$stmt = $pdo->prepare("INSERT INTO messages (sender, receiver, message) VALUES (?, ?, ?)");
echo json_encode([
    "success" => $stmt->execute([$sender, $receiver, $message])
]);
?>
