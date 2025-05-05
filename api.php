<?php
require 'db.php';
header('Content-Type: application/json');

// Detect JSON or form
$input = json_decode(file_get_contents("php://input"), true);
$isJson = is_array($input);

// Helper to send JSON response
function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

$email = $isJson ? ($input['email'] ?? '') : ($_POST['email'] ?? '');
$password = $isJson ? ($input['password'] ?? '') : ($_POST['password'] ?? '');
$action = $isJson ? ($input['action'] ?? '') : (isset($_POST['register']) ? 'register' : (isset($_POST['login']) ? 'login' : ''));

if (!$email || !$password) {
    sendResponse(false, "Email and password are required.");
}

// REGISTRATION
if ($action === 'register') {
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $hashed);

    if ($stmt->execute()) {
        if ($isJson) {
            sendResponse(true, "Registration successful.");
        } else {
            header("Location: index.html");
            exit;
        }
    } else {
        sendResponse(false, "Error registering: " . $stmt->error);
    }
}

// LOGIN
elseif ($action === 'login') {
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            if ($isJson) {
                sendResponse(true, "Login successful.");
            } else {
                header("Location: chat.html");
                exit;
            }
        } else {
            sendResponse(false, "Invalid credentials.");
        }
    } else {
        sendResponse(false, "No user found.");
    }
}

else {
    sendResponse(false, "Invalid action.");
}
?>
