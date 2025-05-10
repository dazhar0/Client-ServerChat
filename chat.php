<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: index.html");
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>SecureChat - Chat</title>
</head>
<body>
  <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
  <p>This is a placeholder chat page.</p>
  <a href="logout.php">Logout</a>
</body>
</html>
