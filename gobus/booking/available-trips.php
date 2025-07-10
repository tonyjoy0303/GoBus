<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
  header("Location: ../auth/login.php");
  exit();
}

$origin = trim($_GET['origin'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$date = $_GET['date'] ?? '';

if (!$origin || !$destination || !$date) {
  header("Location: dashboard.php");
  exit();
}

$stmt = $conn->prepare("
  SELECT routes.*, buses.name AS bus_name, conductors.name AS conductor_name
  FROM routes
  LEFT JOIN buses ON routes.bus_id = buses.id
  LEFT JOIN conductors ON routes.conductor_id = conductors.id
  WHERE origin LIKE ? AND destination LIKE ? AND travel_date = ? AND status = 'not_started'
");
$likeOrigin = '%' . htmlspecialchars($origin) . '%';
$likeDest = '%' . htmlspecialchars($destination) . '%';
$stmt->bind_param("sss", $likeOrigin, $likeDest, $date);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Available Trips – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary: #007C8C;
      --accent: #FFA534;
      --gray: #AAB8C2;
      --bg: #F4F4F4;
      --white: #ffffff;
      --dark: #012F34;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      margin: 0;
      padding: 0;
      color: var(--dark);
    }

    nav {
      background-color: var(--dark);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    nav a.logo {
      display: flex;
      align-items: center;
    }

    nav a.logo img {
      height: 42px;
    }

    .mobile-toggle {
      display: none;
      font-size: 1.5rem;
      background: none;
      border: none;
      color: white;
      cursor: pointer;
    }

    .nav-links {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .nav-links a {
      color: white;
      text-decoration: none;
      font-weight: 500;
    }

    .container {
      padding: 2rem 1.5rem;
      max-width: 960px;
      margin: auto;
    }

    h2 {
      margin-bottom: 1.5rem;
      font-size: 1.6rem;
      color: var(--primary);
    }

    .trip-card {
      background: #fff;
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
      transition: all 0.3s ease;
    }

    .trip-card:hover {
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .trip-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--primary);
    }

    .trip-title span {
      margin-left: 6px;
      font-size: 1.4rem;
      font-weight: 700;
    }

    .trip-price {
      border: 2px solid var(--primary);
      color: var(--primary);
      background: #ffffff;
      padding: 8px 18px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 1.1rem;
      display: inline-block;
    }

    .trip-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      font-size: 1.05rem;
      color: #333;
    }

    .trip-detail {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .time-badge {
      background: var(--dark);
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-weight: 500;
      font-size: 1rem;
    }

    .trip-footer {
      display: flex;
      justify-content: flex-end;
    }

    .btn-select {
      background: var(--accent);
      color: white;
      border: none;
      padding: 12px 26px;
      font-weight: 600;
      border-radius: 8px;
      text-decoration: none;
      font-size: 1.05rem;
      transition: background 0.3s;
    }

    .btn-select:hover {
      background: #e18e2c;
    }

    .no-result {
      background: #fff;
      padding: 2rem;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
    }

    @media (max-width: 768px) {
      .mobile-toggle {
        display: block;
      }

      .nav-links {
        flex-direction: column;
        width: 100%;
        display: none;
        margin-top: 1rem;
        background-color: var(--dark);
        padding: 1rem 0;
      }

      .nav-links.show {
        display: flex;
      }

      .nav-links a {
        padding: 0.5rem 1rem;
      }

      .trip-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.4rem;
      }

      .trip-details {
        grid-template-columns: 1fr;
      }

      .trip-footer {
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <nav>
    <a href="dashboard.php" class="logo">
      <img src="../assets/images/Logo.png" alt="GoBus Logo" />
    </a>
    <button class="mobile-toggle" onclick="toggleMenu()">☰</button>
    <div class="nav-links" id="navLinks">
      <a href="dashboard.php">Book Trip</a>
      <a href="history.php">My Bookings</a>
      <a href="../auth/logout.php">Logout</a>
    </div>
  </nav>

  <div class="container">
    <h2>Trips for <strong><?= htmlspecialchars($origin) ?> → <?= htmlspecialchars($destination) ?></strong> on <strong><?= htmlspecialchars($date) ?></strong></h2>

    <?php if ($results->num_rows > 0): ?>
      <?php while ($row = $results->fetch_assoc()): ?>
        <div class="trip-card">
          <div class="trip-card-header">
            <div class="trip-title">🚌 <span><?= htmlspecialchars($row['bus_name']) ?></span></div>
            <div class="trip-price">💰 ₹<?= htmlspecialchars($row['price']) ?></div>
          </div>
          <div class="trip-details">
            <div class="trip-detail">👨‍✈️ <strong>Conductor:</strong> <?= htmlspecialchars($row['conductor_name']) ?></div>
            <div class="trip-detail">🕒 <strong>Departure:</strong> <span class="time-badge"><?= date("h:i A", strtotime($row['travel_time'])) ?></span></div>
          </div>
          <div class="trip-footer">
            <a class="btn-select" href="seat-select.php?route_id=<?= $row['id'] ?>">Select Seat</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-result">
        <p>❌ No trips found for the selected date and route.</p>
        <a class="btn-select" href="dashboard.php">Search Again</a>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function toggleMenu() {
      document.getElementById("navLinks").classList.toggle("show");
    }
  </script>

</body>
</html>
