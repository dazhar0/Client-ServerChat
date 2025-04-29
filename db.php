<?php
$host = "sqlXXX.infinityfree.com";  // Replace with your InfinityFree DB host
$user = "epiz_XXXXXXX";             // Replace with your InfinityFree DB username
$pass = "your_db_password";         // Replace with your password
$db   = "epiz_XXXXXXX_chat";        // Replace with your DB name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
