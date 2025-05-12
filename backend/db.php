<?php
// WARNING: Do not commit real database credentials to public repositories.
// Use environment variables or a .env file for sensitive information.
$host = 'DB_HOST_HERE';
$user = 'DB_USER_HERE';
$pass = 'DB_PASS_HERE';
$dbname = 'DB_NAME_HERE';

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}
?>
