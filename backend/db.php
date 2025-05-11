<?php
$host = 'sql103.infinityfree.com';
$user = 'if0_38857895';
$pass = 'RCsgAyp68zzNyx';
$dbname = 'if0_38857895_chattitan';

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}
?>
