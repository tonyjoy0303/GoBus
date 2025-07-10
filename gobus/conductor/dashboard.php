<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'conductor') {
  header("Location: ../auth/login.php");
  exit();
}

$conductor_id = $_SESSION['user_id'];

// Fetch assigned route
$stmt = $conn->prepare("
  SELECT id, origin, destination, travel_date, travel_time, status 
  FROM routes 
  WHERE conductor_id = ? AND travel_date >= CURDATE()
  ORDER BY travel_date ASC, travel_time ASC
  LIMIT 1
");
$stmt->bind_param("i", $conductor_id);
$stmt->execute();
$route = $stmt->get_result()->fetch_assoc();

$bookings = [];
if ($route) {
  $route_id = $route['id'];
  $bookStmt = $conn->prepare("
    SELECT seat_number, passenger_name
    FROM bookings
    WHERE route_id = ? AND scanned = 1
    ORDER BY seat_number ASC
  ");
  $bookStmt->bind_param("i", $route_id);
  $bookStmt->execute();
  $bookings = $bookStmt->get_result();
}

// Handle trip status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $new_status = $_POST['update_status'];
  $route_id = intval($_POST['route_id']);
  $update = $conn->prepare("UPDATE routes SET status = ? WHERE id = ?");
  $update->bind_param("si", $new_status, $route_id);
  $update->execute();
  header("Location: dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Conductor Dashboard – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/html5-qrcode"></script>
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
      background-color: var(--bg);
      margin: 0;
      padding: 0;
      color: var(--dark);
    }
    .nav {
      background: var(--dark);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .nav img {
      height: 40px;
    }
    .nav a {
      color: white;
      text-decoration: none;
      font-weight: 500;
    }
    .container {
      max-width: 800px;
      margin: 2rem auto;
      background: var(--white);
      padding: 2rem;
      border-radius: 14px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }
    h2 {
      color: var(--primary);
      margin-bottom: 1rem;
    }
    .row {
      margin-bottom: 1rem;
    }
    .label {
      color: var(--gray);
      font-weight: 500;
    }
    .status {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      display: inline-block;
      margin-top: 0.5rem;
    }
    .not_started { background: #eee; color: #777; }
    .in_progress { background: #FFE08A; color: #996700; }
    .completed { background: #A6F3A6; color: #1C6B1C; }
    .btn, .scan-btn {
      background: var(--accent);
      color: white;
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 1rem;
    }
    .scan-btn {
      background: var(--primary);
    }
    .passenger-list {
      margin-top: 2rem;
    }
    .passenger-list ul {
      list-style: none;
      padding-left: 0;
    }
    .passenger-list li {
      padding: 0.6rem 1rem;
      background: #f9f9f9;
      border: 1px solid #eee;
      border-radius: 8px;
      margin-bottom: 0.6rem;
    }
    .empty {
      padding: 1rem;
      background: #f0f0f0;
      border-radius: 10px;
      text-align: center;
      color: #777;
    }
    .modal {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 999;
    }
    .modal-content {
      background: white;
      padding: 2rem;
      border-radius: 16px;
      width: 90%;
      max-width: 400px;
      text-align: center;
      position: relative;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }
    .modal .close {
      position: absolute;
      right: 1rem;
      top: 1rem;
      font-size: 1.5rem;
      cursor: pointer;
      color: #999;
    }
    .modal .close:hover {
      color: black;
    }
    #reader {
      width: 100%;
      max-width: 300px;
      margin: auto;
      border-radius: 12px;
    }
    #result {
      margin-top: 1rem;
      padding: 0.8rem;
      background: #f1f1f1;
      border-radius: 8px;
      font-size: 0.95rem;
    }
    .valid { color: green; font-weight: bold; }
    .invalid { color: red; font-weight: bold; }
  </style>
</head>
<body>
<div class="nav">
  <img src="../assets/images/Logo.png" alt="GoBus Logo" />
  <a href="../auth/logout.php">Logout</a>
</div>

<div class="container">
  <h2>Assigned Route</h2>
  <?php if ($route): ?>
    <div class="row"><span class="label">Route:</span> <?= htmlspecialchars($route['origin']) ?> → <?= htmlspecialchars($route['destination']) ?></div>
    <div class="row"><span class="label">Date:</span> <?= htmlspecialchars($route['travel_date']) ?> at <?= htmlspecialchars(date('h:i A', strtotime($route['travel_time']))) ?></div>
    <div class="row">
      <span class="label">Status:</span>
      <span class="status <?= $route['status'] ?>"><?= ucfirst(str_replace('_', ' ', $route['status'])) ?></span>
    </div>
    <form method="POST">
      <input type="hidden" name="route_id" value="<?= $route['id'] ?>" />
      <?php if ($route['status'] === 'not_started'): ?>
        <button type="submit" name="update_status" value="in_progress" class="btn">Start Trip</button>
      <?php elseif ($route['status'] === 'in_progress'): ?>
        <button type="submit" name="update_status" value="completed" class="btn">Mark as Completed</button>
        <button type="button" onclick="openScanner()" class="scan-btn">🎫 Scan Ticket</button>
      <?php endif; ?>
    </form>
    <div class="passenger-list">
      <h2>Logged Passengers</h2>
      <?php if ($bookings->num_rows > 0): ?>
        <ul id="passengerList">
          <?php while ($b = $bookings->fetch_assoc()): ?>
            <li><strong><?= htmlspecialchars($b['seat_number']) ?></strong> – <?= htmlspecialchars($b['passenger_name']) ?></li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <div class="empty">No passengers scanned yet.</div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="empty">No route assigned for today.</div>
  <?php endif; ?>
</div>

<div id="scannerModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeScanner()">×</span>
    <h3>Scan Ticket</h3>
    <div id="reader"></div>
    <div id="result">Awaiting scan...</div>
  </div>
</div>

<script>
let scanner = null;
let lastScanned = null;

function openScanner() {
  document.getElementById("scannerModal").style.display = "flex";
  document.getElementById("result").textContent = "Awaiting scan...";
  lastScanned = null;
  scanner = new Html5Qrcode("reader");
  scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: 250 },
    onScanSuccess
  ).catch(err => {
    document.getElementById('result').innerHTML = `<span class='invalid'>❌ Camera error: ${err}</span>`;
  });
}

function closeScanner() {
  document.getElementById("scannerModal").style.display = "none";
  if (scanner) {
    scanner.stop().then(() => scanner.clear());
  }
}

function onScanSuccess(decodedText) {
  if (!decodedText.startsWith("GoBus|ID:")) return;
  const bookingId = decodedText.split("ID:")[1];
  if (bookingId && bookingId.trim() !== lastScanned) {
    lastScanned = bookingId.trim();
    validateBooking(lastScanned);
  }
}

function validateBooking(id) {
  fetch('validate-booking.php?preview=1&id=' + id)
    .then(res => res.json())
    .then(data => {
      const resultBox = document.getElementById('result');
      const existingSeats = Array.from(document.querySelectorAll('#passengerList li strong')).map(e => e.textContent.trim());
      if (data.valid) {
        if (existingSeats.includes(data.seat.trim())) {
          resultBox.innerHTML = `<span class='invalid'>❌ Already Scanned</span>`;
          return;
        }
        resultBox.innerHTML = `<span class='valid'>✅ Valid Ticket for ${data.passenger}</span>`;
        window.postMessage({ action: 'logPassenger', id: id, name: data.passenger, seat: data.seat }, "*");
        setTimeout(() => {
          fetch('validate-booking.php?id=' + id);
          closeScanner();
        }, 2000);
      } else {
        resultBox.innerHTML = `<span class='invalid'>❌ Invalid Ticket</span>`;
      }
    });
}

window.addEventListener("message", function(event) {
  if (event.data && event.data.action === 'logPassenger') {
    const { name, seat } = event.data;
    const ul = document.getElementById("passengerList") || createList();
    const li = document.createElement("li");
    li.innerHTML = `<strong>${seat}</strong> – ${name}`;
    li.style.background = "#e1ffe1";
    li.style.transition = "all 0.3s ease";
    li.style.opacity = "0";
    ul.appendChild(li);
    setTimeout(() => {
      li.style.opacity = "1";
      li.style.background = "#f9f9f9";
    }, 100);

    const audio = new Audio("../assets/success-beep.mp3");
    audio.play().catch(() => {});
  }
});

function createList() {
  const list = document.createElement("ul");
  list.id = "passengerList";
  document.querySelector(".passenger-list").appendChild(list);
  return list;
}
</script>
</body>
</html>
