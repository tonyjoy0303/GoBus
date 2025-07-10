<?php
require '../config/db.php';

$name = "Admin One";
$email = "admin@gmail.com";
$plainPassword = "admin";

// Securely hash the password
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// Insert admin into DB
$stmt = $conn->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hashedPassword);

if ($stmt->execute()) {
    echo "✅ Admin added successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}
?>
