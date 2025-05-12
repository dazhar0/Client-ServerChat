<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Titan Chat</title>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .register-container {
      text-align: center;
      padding: 20px;
    }

    .register-card {
      background: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
      width: 320px;
      margin: auto;
    }

    h1 {
      color: #333;
      margin-bottom: 20px;
    }

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

        <div class="g-recaptcha" data-sitekey="6LeBJzYrAAAAAK-9nXYvSGFfXFQSLuEY9b3nBOoU"></div>

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

      const formData = new URLSearchParams();
      formData.append('email', email);
      formData.append('username', username);
      formData.append('password', password);
      formData.append('g-recaptcha-response', captcha);

      try {
        const response = await fetch('https://chatpageapp.kesug.com/backend/register.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: formData.toString()
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
