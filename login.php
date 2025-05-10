<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST["email"];
  $password = $_POST["password"];

  $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();
  
  if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $username, $hashedPassword);
    $stmt->fetch();

    if (password_verify($password, $hashedPassword)) {
      $_SESSION["user_id"] = $id;
      $_SESSION["username"] = $username;
      header("Location: chat.php");
    } else {
      echo "Invalid password";
    }
  } else {
    echo "No user found";
  }

  $stmt->close();
  $conn->close();
}
?>
