<?php
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['error' => 'Upload directory does not exist and could not be created.', 'cwd' => getcwd()]);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    echo json_encode(['error' => 'Upload directory is not writable.', 'dir' => $uploadDir]);
    exit;
}

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $filename = basename($_FILES['file']['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','pdf','txt','doc','docx','zip','rar','csv','mp3','mp4','webm','ogg','xlsx','ppt','pptx','json'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'File type not allowed.', 'ext' => $ext]);
        exit;
    }
    if ($_FILES['file']['size'] > $maxSize) {
        echo json_encode(['error' => 'File too large.', 'size' => $_FILES['file']['size']]);
        exit;
    }

    $unique = uniqid('file_', true) . '.' . $ext;
    $target = $uploadDir . $unique;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo json_encode(['url' => 'uploads/' . $unique]);
    } else {
        echo json_encode([
            'error' => 'move failed',
            'tmp_name' => $_FILES['file']['tmp_name'],
            'target' => $target,
            'perms' => substr(sprintf('%o', fileperms($uploadDir)), -4)
        ]);
    }
} else {
    echo json_encode(['error' => 'upload failed', 'details' => $_FILES['file'] ?? null]);
}
?>
