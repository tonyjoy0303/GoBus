<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../vendor/autoload.php';
require '../config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;

if (!isset($_GET['booking_id'])) {
  die("Booking ID is required.");
}

$booking_id = intval($_GET['booking_id']);

// Fetch booking info
$stmt = $conn->prepare("
  SELECT b.*, r.origin, r.destination, r.travel_date, r.travel_time, r.price, u.name AS user_name
  FROM bookings b
  JOIN routes r ON b.route_id = r.id
  JOIN users u ON b.user_id = u.id
  WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  die("Invalid booking ID.");
}

$data = $result->fetch_assoc();

// ✅ QR Code content
$qrText = "GoBus Ticket\nBooking ID: {$booking_id}\nPassenger: {$data['passenger_name']}\nSeat: {$data['seat_number']}\nDate: {$data['travel_date']}";

// ✅ Save QR to temp folder
$qrImagePath = realpath(__DIR__ . '/../temp') . "/qr_{$booking_id}.png";

// ✅ Generate QR (use defaults)
$qrCode = new QrCode($qrText);
$qrCode->setSize(300);
$qrCode->setMargin(10);
$qrCode->setEncoding('UTF-8');
// DO NOT set error correction level
$qrCode->writeFile($qrImagePath);

// ✅ DomPDF image path
$qrImageURI = 'file://' . $qrImagePath;

// ✅ Build HTML
$html = "
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h2 { color: #007C8C; }
    .info { margin-bottom: 12px; }
    .label { font-weight: bold; }
    .qr { margin-top: 25px; }
  </style>
</head>
<body>
  <h2>GoBus Ticket</h2>
  <div class='info'><span class='label'>Passenger:</span> {$data['passenger_name']}</div>
  <div class='info'><span class='label'>Seat:</span> {$data['seat_number']}</div>
  <div class='info'><span class='label'>Route:</span> {$data['origin']} → {$data['destination']}</div>
  <div class='info'><span class='label'>Date:</span> {$data['travel_date']} at {$data['travel_time']}</div>
  <div class='info'><span class='label'>Fare:</span> ₹{$data['price']}</div>
  <div class='qr'>
    <span class='label'>QR Code:</span><br>
    <img src='{$qrImageURI}' width='140' height='140' />
  </div>
</body>
</html>
";

// ✅ Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("ticket_booking_{$booking_id}.pdf", ["Attachment" => false]);

// ✅ Cleanup
if (file_exists($qrImagePath)) {
    unlink($qrImagePath);
}
?>
