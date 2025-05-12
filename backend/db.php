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

$sql = "CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `from` VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_private TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Table created successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error creating table: " . $conn->error]);
}
?>
