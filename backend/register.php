<?php
// Enable error reporting (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    $secretKey = "6LeBJzYrAAAAAFMK3-u2c_DGrp4I3-qelS805PED";
    $verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaResponse");
    $responseData = json_decode($verifyResponse, true);

    if (!$responseData["success"]) {
        echo json_encode(["status" => "error", "message" => "CAPTCHA failed"]);
        exit();
    }

    // Correct db.php path
    require_once "db.php";

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
