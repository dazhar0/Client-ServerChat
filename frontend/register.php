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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Titan Chat</title>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    /* Global and Body Styles */
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    
    /* Centering container */
    .register-container {
      text-align: center;
      padding: 20px;
    }
    
    /* Card styling for the registration form */
    .register-card {
      background: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
      width: 320px;
      margin: auto;
    }
    
    /* Heading */
    h1 {
      color: #333;
      margin-bottom: 20px;
    }
    
    /* Form Styles */
    form {
      text-align: left;
      margin-top: 20px;
    }
    
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
      color: #555;
    }
    
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
      box-sizing: border-box;
    }
    
    /* Button Styles */
    button {
      width: 100%;
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      transition: background-color 0.3s ease;
    }
    
    button:hover {
      background-color: #0056b3;
    }
    
    /* Footer Link Style */
    .register-footer {
      margin-top: 20px;
      font-size: 14px;
      color: #555;
    }
    
    .register-footer a {
      color: #007bff;
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease;
    }
    
    .register-footer a:hover {
      color: #0056b3;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="register-card">
      <h1>Register</h1>
      <form id="registerForm">
        <label for="email">Email:</label>
        <input type="email" id="email" required>
        
        <label for="username">Username:</label>
        <input type="text" id="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" required>
        
        <!-- reCAPTCHA -->
        <div class="g-recaptcha" data-sitekey="6LdWeDUrAAAAAN_eUiDGWbFifKU2MrEKYxHODEng"></div>
        
        <button type="submit">Register</button>
      </form>
      <div class="register-footer">
        <p>Already have an account? <a href="login.php">Login here</a></p>
      </div>
    </div>
  </div>
  
  <script>
    document.getElementById("registerForm").onsubmit = async function(event) {
      event.preventDefault();

      const email = document.getElementById('email').value;
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;
      const captcha = grecaptcha.getResponse();

      if (!captcha) {
        alert("Please complete the CAPTCHA.");
        return;
      }

      try {
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
      } catch (error) {
        alert("An error occurred. Please try again.");
      }
    };
  </script>
</body>
</html>
