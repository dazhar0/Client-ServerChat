<?php
// Handle POST registration logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents("php://input"), true);

    $email = trim($data['email']);
    $username = trim($data['username']);
    $password = trim($data['password']);
    $recaptchaResponse = $data['g-recaptcha-response'] ?? '';

    // reCAPTCHA check
    $secretKey = "6LdWeDUrAAAAABUeHMHApUy09VxayBj0mhE8wa-s"; // <- PUT YOUR SECRET KEY HERE
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Titan Chat</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <h1>Register</h1>
    <form id="registerForm">
        <label for="email">Email:</label><br>
        <input type="email" id="email" required><br><br>

        <label for="username">Username:</label><br>
        <input type="text" id="username" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" required><br><br>

        <!-- CAPTCHA -->
        <div class="g-recaptcha" data-sitekey="6LdWeDUrAAAAAN_eUiDGWbFifKU2MrEKYxHODEng"></div> <!-- <- PUT SITE KEY HERE -->

        <br><button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>

    <script>
        document.getElementById("registerForm").onsubmit = async function (event) {
            event.preventDefault();

            const email = document.getElementById('email').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const captcha = grecaptcha.getResponse();

            if (!captcha) {
                alert("Please complete the CAPTCHA.");
                return;
            }

            const response = await fetch('register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, username, password, 'g-recaptcha-response': captcha })
            });

            const result = await response.json();
            if (result.status === "success") {
                alert("Registration successful!");
                window.location.href = 'login.php';
            } else {
                alert(result.message);
            }
        };
    </script>
</body>
</html>
