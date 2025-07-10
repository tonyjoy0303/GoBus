<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
  header("Location: ../auth/login.php");
  exit();
}

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
  die("❌ Invalid booking ID.");
}

$bookingId = intval($_GET['booking_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Payment Success – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #e6f8f9;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      flex-direction: column;
      text-align: center;
    }

    .card {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 90%;
    }

    .success-checkmark {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #007C8C;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      animation: pop 0.4s ease;
    }

    .success-checkmark::after {
      content: '✓';
      color: white;
      font-size: 48px;
      line-height: 1;
    }

    @keyframes pop {
      0% { transform: scale(0); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }

    .thankyou {
      font-size: 20px;
      margin-top: 10px;
    }

    .qr {
      margin: 20px auto;
      display: flex;
      justify-content: center;
    }

    .btn {
      background: #007C8C;
      color: white;
      border: none;
      padding: 12px 24px;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 20px;
    }

    .btn:hover {
      background: #005f66;
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="success-checkmark"></div>
    <h2>Payment Successful!</h2>
    <p class="thankyou">Thank you for booking with GoBus.</p>

    <div class="qr">
      <div id="qrcode"></div>
    </div>

    <button class="btn" onclick="window.location.href='dashboard.php'">Go to Dashboard</button>
  </div>

  <script>
    const bookingId = <?= json_encode($bookingId) ?>;
    const qrText = `GoBus|ID:${bookingId}`;
    new QRCode(document.getElementById("qrcode"), {
      text: qrText,
      width: 180,
      height: 180,
      correctLevel: QRCode.CorrectLevel.H
    });
  </script>

</body>
</html>
