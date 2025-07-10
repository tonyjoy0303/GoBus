<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../auth/login.php");
  exit();
}

require '../config/db.php';

// CSRF token init
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to fetch total count safely
function getTotal($conn, $table) {
  $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM $table");
  $stmt->execute();
  $result = $stmt->get_result();
  return $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
}

$busCount = getTotal($conn, 'buses');
$routeCount = getTotal($conn, 'routes');
$conductorCount = getTotal($conn, 'conductors');
$userCount = getTotal($conn, 'users');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../assets/css/admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>
<body>

  <!-- Top Navbar -->
  <nav class="admin-navbar">
    <div class="nav-left">
      <a href="dashboard.php"><img src="../assets/images/Logo.png" alt="GoBus Logo" class="logo-img" /></a>
    </div>
    <div class="nav-right">
      <a href="../auth/logout.php" class="logout-btn">Logout</a>
    </div>
  </nav>

  <!-- Main Dashboard -->
  <div class="admin-container">
    <header class="admin-header">
      <h1>Admin Dashboard</h1>
      <p>Overview of system activity and quick management access</p>
    </header>

    <section class="stats">
      <div class="stat-card">
        <div class="icon">🚌</div>
        <div class="stat-info">
          <h2><?= $busCount ?></h2>
          <p>Buses</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="icon">📍</div>
        <div class="stat-info">
          <h2><?= $routeCount ?></h2>
          <p>Routes</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="icon">👨‍✈️</div>
        <div class="stat-info">
          <h2><?= $conductorCount ?></h2>
          <p>Conductors</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="icon">🧑‍🤝‍🧑</div>
        <div class="stat-info">
          <h2><?= $userCount ?></h2>
          <p>Users</p>
        </div>
      </div>
    </section>

    <section class="actions">
      <h3 class="section-title">Quick Management</h3>
      <div class="button-group">
        <a href="manage-buses.php" class="action-btn">Manage Buses</a>
        <a href="manage-routes.php" class="action-btn">Manage Routes</a>
        <a href="manage-conductors.php" class="action-btn">Manage Conductors</a>
      </div>
    </section>
  </div>

</body>
</html>
