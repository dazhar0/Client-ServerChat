<?php
header('Content-Type: application/json');
require 'db.php';

$user1 = $_GET['user1'];
$user2 = $_GET['user2'];

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
echo json_encode($messages);
?>
