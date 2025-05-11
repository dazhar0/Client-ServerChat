<?php
// backend/presence.php
header('Content-Type: application/json');

// Query the database for online users
$mysqli = new mysqli("localhost", "username", "password", "database");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$result = $mysqli->query("SELECT username FROM users WHERE online = 1");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);

$mysqli->close();
?>
