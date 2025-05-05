<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Simple validation
    if (!$username || !$email || !$password) {
        $error = "All fields are required.";
    } else {
        // Prepare JSON payload
        $data = json_encode([
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);

        // Replace this with your actual Render API URL
        $apiUrl = 'https://client-serverchat.onrender.com/api/register';

        // Initialize cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $success = "Registration successful!";
        } else {
            $error = "Registration failed. Server responded with status $httpCode.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SecureChat Register</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background-color: #f0f0f0; }
        form { background: white; padding: 20px; border-radius: 10px; max-width: 400px; margin: auto; box-shadow: 0 0 10px #ccc; }
        input { display: block; width: 100%; margin-bottom: 10px; padding: 8px; }
        .message { margin-bottom: 15px; color: red; }
        .success { color: green; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Register for SecureChat</h2>

<form method="POST">
    <?php if (!empty($error)) echo "<div class='message'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='message success'>$success</div>"; ?>

    <input type="text" name="username" placeholder="Username" required />
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <input type="submit" value="Register" />
</form>

</body>
</html>
