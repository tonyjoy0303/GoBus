<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $role = $_POST['role'];

  switch ($role) {
    case 'admin':
      $table = 'admins';
      $redirect = '../admin/dashboard.php';
      break;
    case 'conductor':
      $table = 'conductors';
      $redirect = '../conductor/dashboard.php';
      break;
    default:
      $table = 'users';
      $redirect = '../booking/dashboard.php';
      break;
  }

  // Protect against SQL injection in table name
  $allowedTables = ['admins', 'conductors', 'users'];
  if (!in_array($table, $allowedTables)) {
    header("Location: login.php?error=Invalid%20login%20role.");
    exit();
  }

  $stmt = $conn->prepare("SELECT id, name, password FROM $table WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $name, $hashedPassword);
    $stmt->fetch();

    if (password_verify($password, $hashedPassword)) {
      $_SESSION['user_id'] = $id;
      $_SESSION['name'] = $name;
      $_SESSION['role'] = $role;
      header("Location: $redirect");
      exit();
    } else {
      header("Location: login.php?error=Incorrect%20password");
      exit();
    }
  } else {
    header("Location: login.php?error=No%20$role%20found%20with%20that%20email");
    exit();
  }
}
?>
