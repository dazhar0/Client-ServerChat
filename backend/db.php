<?php
$host = 'sql103.infinityfree.com';
$user = 'if0_38857895';
$pass = 'RCsgAyp68zzNyx';
$dbname = 'if0_38857895_chattitan';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>