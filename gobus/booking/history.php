<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
  header("Location: ../auth/login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
  SELECT b.id AS booking_id, b.seat_number, b.passenger_name, b.booked_at, b.scanned,
         r.origin, r.destination, r.travel_date, r.travel_time, r.price, r.status
  FROM bookings b
  JOIN routes r ON b.route_id = r.id
  WHERE b.user_id = ?
  ORDER BY r.travel_date DESC, r.travel_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$current = [];
$past = [];

while ($row = $result->fetch_assoc()) {
  if ($row['status'] === 'completed') {
    $past[] = $row;
  } else {
    $current[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Bookings – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
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
    nav a img {
      height: 42px;
    }
    .menu-toggle {
      display: none;
      font-size: 1.6rem;
      color: white;
      cursor: pointer;
    }
    .nav-links {
      display: flex;
      align-items: center;
    }
    .nav-links a {
      color: white;
      margin-left: 1rem;
      text-decoration: none;
      font-weight: 500;
    }
    .container {
      padding: 2rem 1rem;
      max-width: 960px;
      margin: auto;
    }
    h2, h3 {
      font-size: 1.8rem;
      margin-bottom: 1rem;
      color: var(--primary);
    }
    .section {
      margin-top: 2rem;
    }
    .booking-card {
      background: var(--white);
      padding: 1.5rem;
      border-radius: 14px;
      margin-bottom: 1.5rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    .booking-card .row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.6rem;
      flex-wrap: wrap;
      word-break: break-word;
    }
    .booking-card .row span {
      font-weight: 500;
      color: var(--gray);
    }
    .tag {
      font-size: 0.75rem;
      padding: 0.3rem 0.7rem;
      border-radius: 6px;
      font-weight: 600;
      text-transform: uppercase;
    }
    .tag.not-started { background: #fddede; color: #a33a3a; }
    .tag.in-progress { background: #fff4c4; color: #8a6d00; }
    .tag.completed { background: #d0f0d0; color: #2d662d; }
    .btn-download {
      background: var(--primary);
      color: white;
      padding: 0.7rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      text-align: center;
      border: none;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 0.5rem;
    }
    .btn-download:hover {
      background: #005f6c;
    }
    .empty {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      text-align: center;
      color: var(--gray);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
    }
    .modal {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      backdrop-filter: blur(6px);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 999;
    }
    .modal-content {
      background: white;
      padding: 2rem;
      border-radius: 14px;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      position: relative;
      animation: slideDown 0.3s ease;
    }
    .qr-box {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1rem 0;
    }
    .ticket-details p {
      margin: 0.5rem 0;
      font-size: 1rem;
      color: #333;
    }
    .ticket-details strong {
      color: var(--primary);
    }
    .modal .close {
      position: absolute;
      right: 1rem;
      top: 1rem;
      font-size: 1.4rem;
      cursor: pointer;
      color: #aaa;
    }
    .modal .close:hover {
      color: #000;
    }
    @keyframes slideDown {
      from { transform: translateY(-20px); opacity: 0; }
      to   { transform: translateY(0); opacity: 1; }
    }

    @media (max-width: 768px) {
      .menu-toggle {
        display: block;
      }

      .nav-links {
        width: 100%;
        flex-direction: column;
        display: none;
        background-color: var(--dark);
        padding: 1rem 0;
      }

      .nav-links.show {
        display: flex;
      }

      .nav-links a {
        padding: 0.7rem 1.5rem;
        margin-left: 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        width: 100%;
      }

      .booking-card .row {
        flex-direction: column;
        align-items: flex-start;
      }

      .btn-download {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<nav>
  <a href="dashboard.php"><img src="../assets/images/Logo.png" alt="GoBus Logo"></a>
  <div class="menu-toggle" onclick="toggleMenu()">☰</div>
  <div class="nav-links" id="navLinks">
    <a href="dashboard.php">Book Trip</a>
    <a href="history.php">My Bookings</a>
    <a href="../auth/logout.php">Logout</a>
  </div>
</nav>

<div class="container">
  <h2>My Bookings</h2>

  <div class="section">
    <h3>🟢 Current Trips</h3>
    <?php if (count($current)): ?>
      <?php foreach ($current as $row): ?>
        <?php
          $statusText = ucfirst(str_replace('_', ' ', $row['status']));
          $statusClass = str_replace('_', '-', $row['status']);
        ?>
        <div class="booking-card">
          <div class="row"><span>🚌 Route:</span> <?= htmlspecialchars($row['origin']) ?> → <?= htmlspecialchars($row['destination']) ?></div>
          <div class="row"><span>📅 Date:</span> <?= date('d M Y', strtotime($row['travel_date'])) ?> at <?= date('h:i A', strtotime($row['travel_time'])) ?></div>
          <div class="row"><span>🧍 Passenger:</span> <?= htmlspecialchars($row['passenger_name']) ?></div>
          <div class="row"><span>💺 Seat:</span> <?= $row['seat_number'] ?></div>
          <div class="row"><span>💰 Fare:</span> ₹<?= $row['price'] ?></div>
          <div class="row"><span>🕓 Booked At:</span> <?= date("d M Y, h:i A", strtotime($row['booked_at'])) ?></div>
          <div class="row"><span>Status:</span> <span class="tag <?= $statusClass ?>"><?= $statusText ?></span></div>
          <div class="row">
            <button type="button" class="btn-download" onclick="showTicket(
              '<?= $row['booking_id'] ?>',
              '<?= htmlspecialchars($row['origin']) ?> → <?= htmlspecialchars($row['destination']) ?>',
              '<?= date('d M Y', strtotime($row['travel_date'])) ?> at <?= date('h:i A', strtotime($row['travel_time'])) ?>',
              <?= $row['scanned'] ? 'true' : 'false' ?>
            )">View Ticket</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty">No current trips.</div>
    <?php endif; ?>
  </div>

  <div class="section">
    <h3>🔴 Past Trips</h3>
    <?php if (count($past)): ?>
      <?php foreach ($past as $row): ?>
        <?php
          $statusText = ucfirst(str_replace('_', ' ', $row['status']));
          $statusClass = str_replace('_', '-', $row['status']);
        ?>
        <div class="booking-card">
          <div class="row"><span>🚌 Route:</span> <?= htmlspecialchars($row['origin']) ?> → <?= htmlspecialchars($row['destination']) ?></div>
          <div class="row"><span>📅 Date:</span> <?= date('d M Y', strtotime($row['travel_date'])) ?> at <?= date('h:i A', strtotime($row['travel_time'])) ?></div>
          <div class="row"><span>🧍 Passenger:</span> <?= htmlspecialchars($row['passenger_name']) ?></div>
          <div class="row"><span>💺 Seat:</span> <?= $row['seat_number'] ?></div>
          <div class="row"><span>💰 Fare:</span> ₹<?= $row['price'] ?></div>
          <div class="row"><span>🕓 Booked At:</span> <?= date("d M Y, h:i A", strtotime($row['booked_at'])) ?></div>
          <div class="row"><span>Status:</span> <span class="tag <?= $statusClass ?>"><?= $statusText ?></span></div>
          <div class="row">
            <button type="button" class="btn-download" onclick="showTicket(
              '<?= $row['booking_id'] ?>',
              '<?= htmlspecialchars($row['origin']) ?> → <?= htmlspecialchars($row['destination']) ?>',
              '<?= date('d M Y', strtotime($row['travel_date'])) ?> at <?= date('h:i A', strtotime($row['travel_time'])) ?>',
              <?= $row['scanned'] ? 'true' : 'false' ?>
            )">View Ticket</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty">No past trips.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Ticket Modal -->
<div id="ticketModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="ticketTitle">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">×</span>
    <h3 id="ticketTitle">Your Ticket</h3>
    <p id="scanStatus" style="font-weight: 600; color: #007C8C; margin-bottom: 0.8rem;"></p>
    <div class="qr-box">
      <div id="qrcode"></div>
    </div>
    <div class="ticket-details">
      <p><strong>Route:</strong> <span id="ticketRoute"></span></p>
      <p><strong>Boarding Time:</strong> <span id="ticketTime"></span></p>
    </div>
  </div>
</div>

<script>
function showTicket(bookingId, route, time, scanned) {
  const qrData = `GoBus|ID:${bookingId}`;
  document.getElementById('ticketRoute').textContent = route;
  document.getElementById('ticketTime').textContent = time;
  document.getElementById('scanStatus').textContent = scanned ? "✅ Ticket Scanned" : "❌ Not Yet Scanned";
  document.getElementById('qrcode').innerHTML = '';
  new QRCode(document.getElementById('qrcode'), {
    text: qrData,
    width: 180,
    height: 180,
    correctLevel: QRCode.CorrectLevel.H
  });
  document.getElementById('ticketModal').style.display = 'flex';
}
function closeModal() {
  document.getElementById('ticketModal').style.display = 'none';
}
window.onclick = function(e) {
  const modal = document.getElementById('ticketModal');
  if (e.target === modal) {
    closeModal();
  }
}
function toggleMenu() {
  document.getElementById('navLinks').classList.toggle('show');
}
</script>

</body>
</html>
