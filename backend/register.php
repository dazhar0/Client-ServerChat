<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');

    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (!$email || !$username || !$password || !$recaptchaResponse) {
        echo json_encode(["status" => "error", "message" => "Missing required fields."]);
        exit();
    }

    // Verify CAPTCHA
    $secretKey = "6LdWeDUrAAAAABUeHMHApUy09VxayBj0mhE8wa-s";
    $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse");
    $responseData = json_decode($verifyResponse, true);

    if (!$responseData["success"]) {
        echo json_encode(["status" => "error", "message" => "CAPTCHA failed"]);
        exit();
    }

    require_once "backend/db.php";

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $username, $hashed);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Username or email may already exist."]);
    }
    exit();
}
?>
