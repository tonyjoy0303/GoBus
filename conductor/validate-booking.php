<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'conductor') {
  echo json_encode(['valid' => false]);
  exit();
}

$booking_id = intval($_GET['id']);
$conductor_id = $_SESSION['user_id'];

// Get route
$stmt = $conn->prepare("SELECT id FROM routes WHERE conductor_id = ? AND status = 'in_progress' LIMIT 1");
$stmt->bind_param("i", $conductor_id);
$stmt->execute();
$routeRes = $stmt->get_result();

if ($routeRes->num_rows === 0) {
  echo json_encode(['valid' => false]);
  exit();
}

$route_id = $routeRes->fetch_assoc()['id'];

// Check booking
$check = $conn->prepare("SELECT passenger_name, seat_number FROM bookings WHERE id = ? AND route_id = ?");
$check->bind_param("ii", $booking_id, $route_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
  echo json_encode(['valid' => false]);
} else {
  $row = $res->fetch_assoc();
  $name = $row['passenger_name'];
  $seat = $row['seat_number'];

  if (!isset($_GET['preview'])) {
    $update = $conn->prepare("UPDATE bookings SET scanned = 1 WHERE id = ?");
    $update->bind_param("i", $booking_id);
    $update->execute();
  }

  echo json_encode(['valid' => true, 'passenger' => $name, 'seat' => $seat]);
}
