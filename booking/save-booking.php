<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
  header("Location: ../auth/login.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (
    !isset($_POST['route_id']) || 
    !isset($_POST['seats']) || 
    !is_array($_POST['seats'])
  ) {
    die("❌ Invalid booking data.");
  }

  $user_id = $_SESSION['user_id'];
  $route_id = intval($_POST['route_id']);
  $seats = $_POST['seats'];

  // Check route
  $routeStmt = $conn->prepare("SELECT id FROM routes WHERE id = ?");
  $routeStmt->bind_param("i", $route_id);
  $routeStmt->execute();
  $routeResult = $routeStmt->get_result();
  if ($routeResult->num_rows === 0) {
    die("❌ Route not found.");
  }

  $stmt = $conn->prepare("INSERT INTO bookings (user_id, route_id, seat_number, passenger_name) VALUES (?, ?, ?, ?)");
  $firstBookingId = null;

  foreach ($seats as $seat => $name) {
    $seat = strtoupper(trim($seat));
    $name = trim($name);
    if (!$seat || !$name) continue;

    $stmt->bind_param("iiss", $user_id, $route_id, $seat, $name);
    $stmt->execute();

    if ($firstBookingId === null) {
      $firstBookingId = $conn->insert_id; // capture the first inserted ID
    }
  }

  // Redirect to payment page with booking ID
  if ($firstBookingId) {
    header("Location: confirm-payment.php?route_id=$route_id&booking_id=$firstBookingId");
    exit();
  } else {
    die("❌ No bookings inserted.");
  }
} else {
  echo "❌ Invalid request method.";
}
?>
