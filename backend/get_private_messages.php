<?php
header('Content-Type: application/json');
require 'db.php';

// Check if user1 and user2 are provided
if (empty($_GET['user1']) || empty($_GET['user2'])) {
    echo json_encode(['status' => 'error', 'message' => 'User1 and User2 are required']);
    exit;
}

$user1 = $_GET['user1'];
$user2 = $_GET['user2'];

// Prepare and execute the query
$stmt = $conn->prepare("SELECT `from_user` AS `from`, `to_user` AS `to`, `message` FROM private_messages 
                        WHERE (`from_user` = ? AND `to_user` = ?) OR (`from_user` = ? AND `to_user` = ?) 
                        ORDER BY id ASC");
$stmt->bind_param("ssss", $user1, $user2, $user2, $user1);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(['status' => 'success', 'messages' => $messages]);
?>
