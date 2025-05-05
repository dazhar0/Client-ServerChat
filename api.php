<?php
// Connect to the database
require 'db.php'; 

// Registration logic
if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO users (email, password) VALUES ('$email', '$password')";
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
        header('Location: index.html');
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Login logic
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Successful login
            header('Location: chat.html');
        } else {
            echo "Invalid credentials.";
        }
    } else {
        echo "No user found with that email.";
    }
}
?>
