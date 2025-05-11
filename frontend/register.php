<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Titan Chat</title>
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
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>

    <script>
        document.getElementById("registerForm").onsubmit = async function (event) {
            event.preventDefault();
            const email = document.getElementById('email').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const response = await fetch('backend/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, username, password })
            });

            const result = await response.json();
            if (result.status === "success") {
                alert("Registration successful!");
                window.location.href = 'login.php';
            } else {
                alert(result.message);
            }
        }
    </script>
</body>
</html>
