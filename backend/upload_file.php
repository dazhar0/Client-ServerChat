<?php
$targetDir = "uploads/";

if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
    $fileName = basename($_FILES["file"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        echo json_encode(["url" => "https://chattitans.42web.io/" . $targetFile]);
    } else {
        echo json_encode(["error" => "File upload failed"]);
    }
} else {
    echo json_encode(["error" => "No file uploaded"]);
}
?>
