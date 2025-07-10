<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user' || !isset($_GET['route_id']) || !isset($_GET['booking_id'])) {
  header("Location: ../auth/login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$route_id = intval($_GET['route_id']);
$booking_id = intval($_GET['booking_id']);

// Fetch route info
$routeStmt = $conn->prepare("SELECT origin, destination, travel_date, travel_time, price FROM routes WHERE id = ?");
$routeStmt->bind_param("i", $route_id);
$routeStmt->execute();
$routeData = $routeStmt->get_result()->fetch_assoc();

if (!$routeData) {
  die("❌ Invalid route ID.");
}

$routeName = $routeData['origin'] . " → " . $routeData['destination'];
$departure = $routeData['travel_date'] . " at " . $routeData['travel_time'];
$price_per_seat = (float) $routeData['price'];

// Fetch bookings
$bookStmt = $conn->prepare("SELECT seat_number, passenger_name FROM bookings WHERE user_id = ? AND route_id = ?");
$bookStmt->bind_param("ii", $user_id, $route_id);
$bookStmt->execute();
$bookings = $bookStmt->get_result();

$seats = [];
$passengers = [];
while ($row = $bookings->fetch_assoc()) {
  $seats[] = $row['seat_number'];
  $passengers[] = $row['seat_number'] . " – " . $row['passenger_name'];
}

if (empty($seats)) {
  die("❌ No valid bookings found.");
}

$total = count($seats) * $price_per_seat;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Booking Summary – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../assets/css/payment.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    ul { padding-left: 20px; margin: 5px 0; }
    .card-footer button {
      background-color: #007C8C;
      color: #fff;
      font-size: 16px;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
    }
    .card-footer button:hover {
      background-color: #005f66;
    }

    .modal-overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.4);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal-box {
      background: #fff;
      border-radius: 12px;
      padding: 30px;
      width: 90%;
      max-width: 400px;
      font-family: 'Poppins', sans-serif;
      text-align: center;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    }

    .modal-box h3 {
      margin-bottom: 10px;
      font-size: 22px;
      color: #007C8C;
    }

    .modal-box p {
      font-size: 16px;
      margin-bottom: 20px;
    }

    .modal-actions {
      display: flex;
      justify-content: space-around;
      gap: 15px;
    }

    .btn-primary, .btn-secondary {
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 15px;
      font-family: 'Poppins', sans-serif;
      border: none;
      cursor: pointer;
    }

    .btn-primary {
      background-color: #007C8C;
      color: white;
    }

    .btn-primary:hover {
      background-color: #005f66;
    }

    .btn-secondary {
      background-color: #ddd;
      color: #333;
    }

    .btn-secondary:hover {
      background-color: #bbb;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="card">
    <div class="card-header">
      <img src="../assets/images/logo.png" alt="GoBus Logo" />
      <h2>Booking Confirmation</h2>
      <p>Your seat is almost reserved. Review details below.</p>
    </div>

    <div class="card-body">
      <div class="row">
        <span>🚌 Route</span>
        <strong><?= htmlspecialchars($routeName) ?></strong>
      </div>
      <div class="row">
        <span>⏰ Departure Time</span>
        <strong><?= htmlspecialchars($departure) ?></strong>
      </div>
      <div class="row">
        <span>🎟️ Seats</span>
        <strong><?= implode(", ", array_map('htmlspecialchars', $seats)) ?></strong>
      </div>
      <div class="row">
        <span>👥 Passengers</span>
        <ul>
          <?php foreach ($passengers as $p): ?>
            <li><?= htmlspecialchars($p) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="row total">
        <span>Total Price</span>
        <strong>₹<?= number_format($total, 2) ?></strong>
      </div>
    </div>

    <div class="card-footer">
      <button id="rzp-button1">Pay with Razorpay</button>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="modal-overlay">
  <div class="modal-box">
    <h3>Confirm Payment</h3>
    <p>You're about to pay <strong>₹<?= number_format($total, 2) ?></strong> for your trip from <strong><?= htmlspecialchars($routeName) ?></strong>.</p>
    <div class="modal-actions">
      <button id="cancel-pay" class="btn-secondary">Cancel</button>
      <button id="confirm-pay" class="btn-primary">Pay Now</button>
    </div>
  </div>
</div>

<!-- Razorpay Checkout -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
  const rzpAmount = <?= intval($total * 100) ?>;
  const bookingId = <?= json_encode($booking_id) ?>;

  const options = {
    "key": "rzp_test_GOdQI9cIWiwQsQ",
    "amount": rzpAmount,
    "currency": "INR",
    "name": "GoBus",
    "description": "Trip Booking Payment",
    "image": "../assets/images/logo.png",
    "handler": function (response) {
      // alert("✅ Payment successful! ID: " + response.razorpay_payment_id);
      window.location.href = "payment-success.php?booking_id=" + bookingId;
    },
    "modal": {
      "ondismiss": function () {
        alert("❌ Payment process was cancelled.");
      }
    },
    "theme": {
      "color": "#007C8C"
    }
  };

  const rzp1 = new Razorpay(options);

  document.getElementById("rzp-button1").onclick = function (e) {
    e.preventDefault();
    document.getElementById("confirm-modal").style.display = "flex";
  };

  document.getElementById("cancel-pay").onclick = function () {
    document.getElementById("confirm-modal").style.display = "none";
  };

  document.getElementById("confirm-pay").onclick = function () {
    document.getElementById("confirm-modal").style.display = "none";
    rzp1.open();
  };
</script>

</body>
</html>
