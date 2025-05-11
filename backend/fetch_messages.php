<?php
include 'db.php';
header("Content-Type: application/json");

$sender = $_GET['sender'] ?? '';
$receiver = $_GET['receiver'] ?? '';

$stmt = $conn->prepare("SELECT * FROM messages WHERE 
    (sender_id = ? AND receiver_id = ?) OR 
    (sender_id = ? AND receiver_id = ?) 
    ORDER BY created_at ASC");
$stmt->bind_param("ssss", $sender, $receiver, $receiver, $sender);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
?>
