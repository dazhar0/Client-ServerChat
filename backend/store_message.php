<?php
include 'db.php';
header("Content-Type: application/json");

// Check if all necessary parameters are provided
if (empty($_POST['from_user']) || empty($_POST['to_user']) || empty($_POST['message'])) {
    echo json_encode(['status' => 'error', 'message' => 'from_user, to_user, and message are required']);
    exit;
}

$from_user = $_POST['from_user'];
$to_user = $_POST['to_user'];
$message = $_POST['message'];

// Prepare and execute the query
$stmt = $conn->prepare("INSERT INTO private_messages (from_user, to_user, message, timestamp, delivered) VALUES (?, ?, ?, NOW(), 0)");
$stmt->bind_param("sss", $from_user, $to_user, $message);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message stored successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to store message']);
}
?>
