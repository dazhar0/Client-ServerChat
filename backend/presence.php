<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);
$user = $data['username'];
$status = $data['online'];

$stmt = $conn->prepare("UPDATE users SET online=? WHERE username=?");
$stmt->bind_param("is", $status, $user);
$stmt->execute();
?>
