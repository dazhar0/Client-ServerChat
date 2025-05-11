<?php
include 'db.php';
$res = $conn->query("SELECT username, online FROM users");
$users = [];
while($row = $res->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode($users);
?>
