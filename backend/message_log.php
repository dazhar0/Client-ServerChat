<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
include 'db.php';

// Get input data from the frontend
$data = json_decode(file_get_contents('php://input'), true);

// Store a message in the database
if (isset($data['sender_id']) && isset($data['receiver_id']) && isset($data['message'])) {
    $sender_id = $data['sender_id'];
    $receiver_id = $data['receiver_id'];
    $message = $data['message'];

    // Query to insert message into the messages table
    $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    $stmt->execute();
    
    echo json_encode(["status" => "success", "message" => "Message sent successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Missing message data"]);
}
?>
