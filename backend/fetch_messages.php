<?php
include 'db.php';
header("Content-Type: application/json");

// Check if 'sender' and 'receiver' are provided
if (empty($_GET['sender']) || empty($_GET['receiver'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sender and receiver are required']);
    exit;
}

$sender = $_GET['sender'];
$receiver = $_GET['receiver'];

// Prepare and execute the query
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

echo json_encode(['status' => 'success', 'messages' => $messages]);
?>
