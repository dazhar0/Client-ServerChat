<?php
session_start();
include('db.php'); // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $uploadDir = '../uploads/'; // Path to the uploads folder
    $uploadFile = $uploadDir . basename($file['name']);

    // Move the file to the uploads folder
    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
        $fileUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $file['name']; // URL to access the file

        // Insert file details into the database
        $query = "INSERT INTO file_messages (from_user, to_user, file_url, file_name, timestamp) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sssss', $_SESSION['username'], $_POST['to_user'], $fileUrl, $file['name'], date('Y-m-d H:i:s'));
        $stmt->execute();
        $stmt->close();

        echo json_encode(['url' => $fileUrl]);
    } else {
        echo json_encode(['error' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['error' => 'No file uploaded']);
}
?>
