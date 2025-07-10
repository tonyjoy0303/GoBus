<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../auth/login.php");
  exit();
}

// Add new route
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_route'])) {
  $stmt = $conn->prepare("INSERT INTO routes (origin, destination, bus_id, conductor_id, travel_date, travel_time, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started')");
  $stmt->bind_param("ssiissd", $_POST['origin'], $_POST['destination'], $_POST['bus_id'], $_POST['conductor_id'], $_POST['travel_date'], $_POST['travel_time'], $_POST['price']);
  $stmt->execute();
  header("Location: manage-routes.php");
  exit();
}

// Update route
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_route'])) {
  $stmt = $conn->prepare("UPDATE routes SET origin=?, destination=?, bus_id=?, conductor_id=?, travel_date=?, travel_time=?, price=?, status=? WHERE id=?");
  $stmt->bind_param("ssiissdsi", $_POST['origin'], $_POST['destination'], $_POST['bus_id'], $_POST['conductor_id'], $_POST['travel_date'], $_POST['travel_time'], $_POST['price'], $_POST['status'], $_POST['route_id']);
  $stmt->execute();
  header("Location: manage-routes.php");
  exit();
}

// Delete route
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM routes WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: manage-routes.php");
  exit();
}

// Reset trip
if (isset($_GET['reset'])) {
  $id = intval($_GET['reset']);

  $del = $conn->prepare("DELETE FROM bookings WHERE route_id = ?");
  $del->bind_param("i", $id);
  $del->execute();

  $up = $conn->prepare("UPDATE routes SET status = 'not_started' WHERE id = ?");
  $up->bind_param("i", $id);
  $up->execute();

  header("Location: manage-routes.php");
  exit();
}

// Fetch buses and conductors
$buses = $conn->query("SELECT id, name FROM buses ORDER BY name");
$conductors = $conn->query("SELECT id, name FROM conductors ORDER BY name");

// Fetch all routes
$routes = $conn->query("
  SELECT routes.*, buses.name AS bus_name, conductors.name AS conductor_name 
  FROM routes
  LEFT JOIN buses ON routes.bus_id = buses.id
  LEFT JOIN conductors ON routes.conductor_id = conductors.id
  ORDER BY routes.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Routes – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../assets/css/admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    .route-section { margin-top: 2rem; }
    .route-form {
      background: var(--white);
      padding: 2rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
    }
    .route-form input, .route-form select {
      width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px;
    }
    .route-form button {
      background: #007C8C;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      max-width: 200px;
      margin-top: 10px;
    }
    .route-table {
      width: 100%;
      background: var(--white);
      border-collapse: collapse;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .route-table th, .route-table td {
      padding: 14px 20px;
      border-bottom: 1px solid #eee;
      text-align: left;
    }
    .route-table th {
      background: var(--bg);
      color: var(--primary);
    }
    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .action-btn {
      background: #007C8C;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.9rem;
      cursor: pointer;
      text-decoration: none;
    }
    .action-btn:hover { opacity: 0.9; }
    .delete-btn { background: crimson; }
    .reset-btn { background: #555; }

    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.4);
      align-items: center;
      justify-content: center;
      z-index: 999;
    }
    .modal-content {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      max-width: 500px;
      width: 90%;
      position: relative;
    }
    .modal-content input, .modal-content select {
      width: 100%;
      margin-bottom: 1rem;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    .modal-content .btn {
      background: #007C8C;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
    }
    .close {
      position: absolute;
      top: 10px;
      right: 16px;
      font-size: 20px;
      cursor: pointer;
      color: #999;
    }

    @media screen and (max-width: 768px) {
      .route-form { grid-template-columns: 1fr; }
      .route-table thead { display: none; }
      .route-table tr {
        display: block;
        margin-bottom: 1rem;
        background: var(--white);
        border-radius: 12px;
        box-shadow: 0 1px 5px rgba(0,0,0,0.1);
        padding: 1rem;
      }
      .route-table td {
        display: block;
        padding: 8px 0;
        text-align: left;
      }
      .route-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--primary);
        display: inline-block;
        min-width: 120px;
      }
    }
  </style>
</head>
<body>



<nav class="admin-navbar">
  <div class="nav-left">
  <a href="dashboard.php">
    <img src="../assets/images/Logo.png" alt="GoBus Logo" class="logo-img" />
  </a>
</div>

  <div class="nav-right">
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>
</nav>

<div class="admin-container">
  <header class="admin-header">
    <h1>Manage Routes</h1>
    <p>Create, edit or reset trips as needed.</p>
  </header>

  <section class="route-section">
    <form method="POST" class="route-form">
      <h3>Add New Route</h3>
      <input type="text" name="origin" placeholder="Origin" required />
      <input type="text" name="destination" placeholder="Destination" required />
      <select name="bus_id" required>
        <option value="">Select Bus</option>
        <?php mysqli_data_seek($buses, 0); while($b = $buses->fetch_assoc()): ?>
          <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
        <?php endwhile; ?>
      </select>
      <select name="conductor_id" required>
        <option value="">Select Conductor</option>
        <?php mysqli_data_seek($conductors, 0); while($c = $conductors->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
        <?php endwhile; ?>
      </select>
      <input type="date" name="travel_date" required />
      <input type="time" name="travel_time" required />
      <input type="number" name="price" placeholder="Price (₹)" min="0" required />
      <button type="submit" name="add_route" class="action-btn">Add Route</button>
    </form>

    <table class="route-table">
      <thead>
        <tr><th>#</th><th>From → To</th><th>Bus</th><th>Conductor</th><th>Date</th><th>Time</th><th>Fare</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i = 1; while ($r = $routes->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= $r['origin'] ?> → <?= $r['destination'] ?></td>
          <td><?= $r['bus_name'] ?></td>
          <td><?= $r['conductor_name'] ?></td>
          <td><?= $r['travel_date'] ?></td>
          <td><?= $r['travel_time'] ?></td>
          <td>₹<?= $r['price'] ?></td>
          <td><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></td>
          <td class="action-buttons">
            <button class="action-btn" onclick="openEditModal(
              <?= $r['id'] ?>,
              '<?= $r['origin'] ?>',
              '<?= $r['destination'] ?>',
              <?= $r['bus_id'] ?>,
              <?= $r['conductor_id'] ?>,
              '<?= $r['travel_date'] ?>',
              '<?= $r['travel_time'] ?>',
              <?= $r['price'] ?>,
              '<?= $r['status'] ?>'
            )">Edit</button>
            <a href="?reset=<?= $r['id'] ?>" class="action-btn reset-btn" onclick="return confirm('Reset all bookings and trip status?')">Reset</a>
            <a href="?delete=<?= $r['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Delete this route?')">Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </section>
</div>

<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">×</span>
    <form method="POST">
      <input type="hidden" name="route_id" id="edit_id">
      <input type="text" name="origin" id="edit_origin" placeholder="Origin" required />
      <input type="text" name="destination" id="edit_destination" placeholder="Destination" required />
      <select name="bus_id" id="edit_bus_id" required>
        <?php mysqli_data_seek($buses, 0); while($b = $buses->fetch_assoc()): ?>
          <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
        <?php endwhile; ?>
      </select>
      <select name="conductor_id" id="edit_conductor_id" required>
        <?php mysqli_data_seek($conductors, 0); while($c = $conductors->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
        <?php endwhile; ?>
      </select>
      <input type="date" name="travel_date" id="edit_date" required />
      <input type="time" name="travel_time" id="edit_time" required />
      <input type="number" name="price" id="edit_price" required />
      <select name="status" id="edit_status" required>
        <option value="not_started">Not Started</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
      </select>
      <button class="action-btn" name="update_route">Update Route</button>
    </form>
  </div>
</div>

<script>
function openEditModal(id, origin, destination, busId, conductorId, date, time, price, status) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_origin').value = origin;
  document.getElementById('edit_destination').value = destination;
  document.getElementById('edit_bus_id').value = busId;
  document.getElementById('edit_conductor_id').value = conductorId;
  document.getElementById('edit_date').value = date;
  document.getElementById('edit_time').value = time;
  document.getElementById('edit_price').value = price;
  document.getElementById('edit_status').value = status;
  document.getElementById('editModal').style.display = 'flex';
}
function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}
</script>

</body>
</html>
