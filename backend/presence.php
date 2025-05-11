<?php
session_start();
include('db.php');

$query = "SELECT username, online FROM users";
$result = $mysqli->query($query);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
?>
