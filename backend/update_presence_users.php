<?php
session_start();
include('db.php');

$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'];
$online = $data['online'];

$query = "UPDATE users SET online = ? WHERE username = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('is', $online, $username);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success']);
?>
