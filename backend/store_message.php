<?php
include 'db.php';
header("Content-Type: application/json");

// Check if all necessary parameters are provided
if (empty($_POST['sender_id']) || empty($_POST['receiver_id']) || empty($_POST['message'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sender ID, Receiver ID, and message are required']);
    exit;
}

$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

// Prepare and execute the query
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message stored successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to store message']);
}
?>
