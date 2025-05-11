<?php
include 'db.php';
header("Content-Type: application/json");

$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $sender_id, $receiver_id, $message);
$stmt->execute();

echo json_encode(['status' => 'success']);
?>
