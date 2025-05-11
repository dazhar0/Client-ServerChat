<?php
$targetDir = "uploads/";
$targetFile = $targetDir . basename($_FILES["file"]["name"]);

if ($_FILES["file"]["size"] > 5000000) {
    die("File too large.");
}

if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    echo json_encode(["status" => "success", "url" => $targetFile]);
} else {
    echo json_encode(["status" => "error", "message" => "Upload failed."]);
}
?>
