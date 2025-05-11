<?php
if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    $filename = basename($_FILES['file']['name']);
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo json_encode(['url' => 'uploads/' . $filename]);
    } else {
        echo json_encode(['error' => 'move failed']);
    }
} else {
    echo json_encode(['error' => 'upload failed']);
}
?>
