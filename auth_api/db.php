<?php
$host = 'sql103.infinityfree.com';
$user = 'if0_38857895';
$pass = 'RCsgAyp68zzNyx';
$dbname = 'if0_38857895_chattitan';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>
