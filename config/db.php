<?php
$servername = "localhost";
$username   = "root";
$password   = ""; // No password for XAMPP by default
$database   = "gobus";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
