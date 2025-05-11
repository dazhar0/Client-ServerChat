<?php
// backend/upload_files.php
if ($_FILES['file']['error'] == UPLOAD_ERR_OK && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($file['name']);
    move_uploaded_file($file['tmp_name'], $uploadFile);

    echo json_encode(['url' => "https://your-domain.com/{$uploadFile}"]);
} else {
    echo json_encode(['error' => 'Failed to upload file']);
}
?>
