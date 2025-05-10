<?php
require 'db.php';

$sender = $_GET['sender'];
$receiver = $_GET['receiver'];

$stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender=? AND receiver=?) OR (sender=? AND receiver=?) ORDER BY timestamp ASC");
$stmt->execute([$sender, $receiver, $receiver, $sender]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
