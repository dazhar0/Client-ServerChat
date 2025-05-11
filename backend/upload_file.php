<?php
include 'db.php';
header("Content-Type: application/json");

if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileType = $_FILES['file']['type'];
    $fileSize = $_FILES['file']['size'];
    $uploadDir = 'uploads/';

    $dest_path = $uploadDir . $fileName;
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        echo json_encode(['url' => $dest_path]);
    } else {
        echo json_encode(['error' => 'File upload failed']);
    }
} else {
    echo json_encode(['error' => 'No file uploaded']);
}
?>
