<?php
$host = 'sql103.infinityfree.com';
$db   = 'if0_38857895_chattitan';
$user = 'if0_38857895';
$pass = 'RCsgAyp68zzNyx';  // Your MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die(json_encode(["status" => "error", "message" => "DB Connection failed: " . $e->getMessage()]));
}
?>
