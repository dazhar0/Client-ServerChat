<?php
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $targetDir = "../uploads/";
    $targetFile = $targetDir . basename($file["name"]);
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        echo json_encode(['url' => 'https://chattitans.42web.io/uploads/' . basename($file["name"])]);
    } else {
        echo json_encode(['error' => 'File upload failed.']);
    }
}
?>
