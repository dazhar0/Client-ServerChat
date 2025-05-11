<?php
// Assuming you have a database connection already
require 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$from = mysqli_real_escape_string($conn, $data['from']);
$to = mysqli_real_escape_string($conn, $data['to']);
$message = mysqli_real_escape_string($conn, $data['message']);

$sql = "INSERT INTO private_messages (from_user, to_user, message, timestamp, delivered) 
        VALUES ('$from', '$to', '$message', NOW(), 0)";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
}
?>
