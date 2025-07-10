<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
  header("Location: ../auth/login.php");
  exit();
}

if (!isset($_GET['route_id']) || !is_numeric($_GET['route_id'])) {
  header("Location: available-trips.php");
  exit();
}

$route_id = intval($_GET['route_id']);

// Fetch booked seats
$booked = [];
$stmt = $conn->prepare("SELECT seat_number FROM bookings WHERE route_id = ?");
$stmt->bind_param("i", $route_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $booked[] = $row['seat_number'];
}

// Fetch total seats for the route's bus
$stmt2 = $conn->prepare("SELECT buses.seats FROM buses JOIN routes ON buses.id = routes.bus_id WHERE routes.id = ?");
$stmt2->bind_param("i", $route_id);
$stmt2->execute();
$seatResult = $stmt2->get_result();
$bus = $seatResult->fetch_assoc();
$totalSeats = $bus['seats'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Select Seats – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../assets/css/seat.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    .button-group {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
      gap: 10px;
    }

    .btn-back, .btn-proceed {
      padding: 12px 24px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
    }

    .btn-back {
      background-color: #ccc;
      color: #333;
    }

    .btn-back:hover {
      background-color: #bbb;
    }

    .btn-proceed {
      background-color: #007C8C;
      color: #fff;
    }

    .btn-proceed:hover {
      background-color: #005f66;
    }
  </style>
</head>
<body>
  <div class="seat-wrapper">
    <h1>Select Your Seats</h1>
    <p class="subtitle">You may select up to 5 seats</p>

    <div class="seat-grid">
      <?php
        $cols = 4;
        $colMap = [0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D'];
        for ($i = 0; $i < $totalSeats; $i++) {
          if ($i % $cols == 0) echo '<div class="seat-row">';

          $col = $i % $cols;
          if ($col == 2) {
            echo '<div class="seat-aisle"></div>';
          }

          $rowNumber = floor($i / $cols) + 1;
          $colLetter = $colMap[$col];
          $seatId = "{$rowNumber}{$colLetter}";
          $isBooked = in_array($seatId, $booked);
          $img = $isBooked ? "seat-chair-booked.png" : "seat-chair.png";
          $cls = $isBooked ? "seat-img booked" : "seat-img";

          echo "<div class='seat-block'>";
          echo "<img src='../assets/images/{$img}' class='{$cls}' data-id='{$seatId}' onclick='toggleSeat(this)' />";
          echo "<label>{$seatId}</label>";
          echo "</div>";

          if ($col == $cols - 1) echo '</div>';
        }
      ?>
    </div>

    <div class="selected-info">
      <h2>Passenger Details</h2>
      <div id="selected-seats"></div>
    </div>

    <div class="button-group">
      <button class="btn-back" onclick="history.back()">← Back</button>
      <button class="btn-proceed" onclick="proceedBooking()">Proceed</button>
    </div>
  </div>

  <script>
    const maxSeats = 5;
    const selectedSeats = new Map();

    function toggleSeat(img) {
      const seatId = img.dataset.id;
      if (img.classList.contains("booked")) return;

      const isSelected = img.classList.toggle("selected");

      if (isSelected) {
        if (selectedSeats.size >= maxSeats) {
          img.classList.remove("selected");
          alert("You can only select up to 5 seats.");
          return;
        }
        img.src = "../assets/images/seat-chair-selected.png";
        selectedSeats.set(seatId, "");
      } else {
        img.src = "../assets/images/seat-chair.png";
        selectedSeats.delete(seatId);
      }

      updateSelected();
    }

    function updateSelected() {
      const container = document.getElementById("selected-seats");
      container.innerHTML = "";
      selectedSeats.forEach((_, id) => {
        const wrapper = document.createElement("div");
        wrapper.className = "seat-input";

        const label = document.createElement("label");
        label.textContent = `${id} Passenger Name`;

        const input = document.createElement("input");
        input.type = "text";
        input.placeholder = "Passenger Name";
        input.required = true;
        input.oninput = e => selectedSeats.set(id, e.target.value);

        wrapper.appendChild(label);
        wrapper.appendChild(input);
        container.appendChild(wrapper);
      });
    }

    function proceedBooking() {
      if (selectedSeats.size === 0) {
        alert("Please select at least one seat.");
        return;
      }

      let allNamed = true;
      selectedSeats.forEach(name => {
        if (!name.trim()) allNamed = false;
      });

      if (!allNamed) {
        alert("Please enter all passenger names.");
        return;
      }

      const form = document.createElement("form");
      form.method = "POST";
      form.action = "save-booking.php";

      const routeInput = document.createElement("input");
      routeInput.type = "hidden";
      routeInput.name = "route_id";
      routeInput.value = "<?= $route_id ?>";
      form.appendChild(routeInput);

      selectedSeats.forEach((name, seat) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = `seats[${seat}]`;
        input.value = name;
        form.appendChild(input);
      });

      document.body.appendChild(form);
      form.submit();
    }
  </script>
</body>
</html>
