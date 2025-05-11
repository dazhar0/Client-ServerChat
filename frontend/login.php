<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Titan Chat</title>
</head>
<body>
    <h1>Login</h1>
    <form id="loginForm">
        <label for="username">Username:</label><br>
        <input type="text" id="username" required><br><br>
        <label for="password">Password:</label><br>
        <input type="password" id="password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a></p>

    <script>
        document.getElementById("loginForm").onsubmit = async function (event) {
            event.preventDefault();
            const username = document.getElementById("username").value;
            const password = document.getElementById("password").value;

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
        };
    </script>
</body>
</html>
