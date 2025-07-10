<?php
require '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name     = trim($_POST['name']);
  $email    = trim($_POST['email']);
  $phone    = trim($_POST['phone']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssss", $name, $email, $phone, $password);

  if ($stmt->execute()) {
    header("Location: login.php?success=1");
    exit();
  } else {
    echo "❌ Error: " . $conn->error;
  }
}
?>
