<?php
$host = 'sql103.infinityfree.com';
$user = 'if0_38857895';
$pass = 'RCsgAyp68zzNyx';
$dbname = 'if0_38857895_chattitan';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
