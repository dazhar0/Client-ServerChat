<?php
$host = 'sql100.infinityfree.com';
$user = 'if0_38953579';
$pass = 'jB1XtOLoTZc'; // Replace with your actual password if different
$dbname = 'if0_38953579_phpinfinite';

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}
?>
