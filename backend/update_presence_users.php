<?php
// backend/update_presence.php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if ($data && isset($data['username'], $data['online'])) {
    $username = $data['username'];
    $online = $data['online'];

    // Update the user's presence in the database (set online or offline)
    $mysqli = new mysqli("localhost", "username", "password", "database");

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("UPDATE users SET online = ? WHERE username = ?");
    $stmt->bind_param("is", $online, $username);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();

    echo json_encode(['status' => 'success']);
}
?>