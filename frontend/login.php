<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Titan Chat</title>
  <style>
    /* Reset and Global Styles */
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    
    /* Container for centering content */
    .login-container {
      text-align: center;
      padding: 20px;
    }
    
    /* Login card styling */
    .login-card {
      background: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
      width: 320px;
      margin: 0 auto;
    }
    
    h1 {
      color: #333;
      margin-bottom: 20px;
    }
    
    /* Form styling */
    form {
      text-align: left;
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
    
    /* Button styling */
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
    
    /* Footer link and text styling */
    .login-footer {
      margin-top: 20px;
      color: #555;
      font-size: 14px;
    }
    .login-footer a {
      color: #007bff;
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease;
    }
    .login-footer a:hover {
      color: #0056b3;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <h1>Login</h1>
      <form id="loginForm">
        <label for="username">Username:</label>
        <input type="text" id="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" required>
        <button type="submit">Login</button>
      </form>
      <div class="login-footer">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
      </div>
    </div>
  </div>
  
  <script>
    document.getElementById("loginForm").onsubmit = async function(event) {
      event.preventDefault();
      
      const username = document.getElementById("username").value;
      const password = document.getElementById("password").value;
      
      try {
        const response = await fetch("backend/login.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ username, password })
        });
        
        const result = await response.json();
        if (result.status === "success") {
          window.location.href = "main.php";
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
