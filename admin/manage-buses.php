<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../auth/login.php");
  exit();
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = null;
$message_type = '';

// Add bus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bus'])) {
  if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $message = "CSRF token mismatch!";
    $message_type = 'error';
  } else {
    $bus_name = trim($_POST['bus_name']);
    $seats = intval($_POST['seats']);

    $stmt = $conn->prepare("INSERT INTO buses (name, seats) VALUES (?, ?)");
    $stmt->bind_param("si", $bus_name, $seats);
    $stmt->execute();
    header("Location: manage-buses.php"); // Prevent form resubmission
    exit();
  }
}

// Delete bus (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bus'])) {
  if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $message = "CSRF token mismatch!";
    $message_type = 'error';
  } else {
    $id = intval($_POST['delete_bus']);
    $check = $conn->prepare("SELECT id FROM routes WHERE bus_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows === 0) {
      $stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      header("Location: manage-buses.php"); // Prevent form resubmission
      exit();
    } else {
      $message = "Cannot delete: Bus is assigned to a route.";
      $message_type = 'error';
    }
  }
}

$buses = $conn->query("SELECT * FROM buses ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Buses – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../assets/css/admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    .bus-section { margin-top: 2rem; }

    .bus-form {
      background: var(--white);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
    }

    .bus-form h3 { margin-bottom: 1rem; font-size: 1.3rem; }

    .bus-form input {
      width: 100%;
      padding: 12px;
      margin-bottom: 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }

    .bus-form button {
      background: var(--accent);
      border: none;
      color: white;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
    }

    .bus-table {
      width: 100%;
      border-collapse: collapse;
      background: var(--white);
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      border-radius: 12px;
      overflow: hidden;
    }

    .bus-table th, .bus-table td {
      padding: 14px 20px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    .bus-table th {
      background-color: var(--bg);
      color: var(--primary);
    }

    .delete-btn {
      background: crimson;
      color: white;
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      font-size: 0.9rem;
      cursor: pointer;
    }

    .delete-btn:hover { background: darkred; }

    .alert-card {
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
      border-radius: 10px;
      font-weight: 500;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: opacity 0.5s ease;
    }

    .alert-success {
      background-color: #e6f8f4;
      color: #007C8C;
      border-left: 5px solid #007C8C;
    }

    .alert-error {
      background-color: #fff4f4;
      color: #a94442;
      border-left: 5px solid crimson;
    }

    .modal-overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 999;
    }

    .modal-card {
      background: #fff;
      padding: 2rem;
      border-radius: 12px;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .modal-card h3 {
      color: var(--primary);
      font-size: 1.3rem;
      margin-bottom: 0.5rem;
    }

    .modal-card p {
      color: #444;
      margin-bottom: 1.5rem;
    }

    .modal-actions {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
    }

    .confirm-btn {
      background: crimson;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }

    .cancel-btn {
      background: #ddd;
      color: #333;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }

    .confirm-btn:hover { background: darkred; }
    .cancel-btn:hover { background: #bbb; }

    @media (max-width: 600px) {
      .bus-form input,
      .bus-form button {
        font-size: 0.95rem;
      }

      .delete-btn {
        padding: 6px 10px;
        font-size: 0.8rem;
      }

      .bus-table th, .bus-table td {
        padding: 10px;
      }
    }
  </style>
</head>
<body>

  <nav class="admin-navbar">
    <div class="nav-left">
      <a href="dashboard.php"><img src="../assets/images/Logo.png" alt="GoBus Logo" class="logo-img" /></a>
    </div>
    <div class="nav-right">
      <a href="../auth/logout.php" class="logout-btn">Logout</a>
    </div>
  </nav>

  <div class="admin-container">
    <header class="admin-header">
      <h1>Manage Buses</h1>
      <p>Add or remove buses and set seat capacity.</p>
    </header>

    <section class="bus-section">
      <?php if ($message): ?>
        <div class="alert-card <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>" id="alertBox">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="bus-form">
        <h3>Add New Bus</h3>
        <input type="text" name="bus_name" placeholder="Bus Name / Number" required />
        <input type="number" name="seats" placeholder="Total Seats" required min="1" />
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit" name="add_bus">Add Bus</button>
      </form>

      <div style="overflow-x:auto;">
        <table class="bus-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Bus Name</th>
              <th>Total Seats</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; while ($row = $buses->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= $row['seats'] ?></td>
              <td>
                <button type="button" class="delete-btn" onclick="openDeleteModal(<?= $row['id'] ?>)">Delete</button>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal-overlay">
    <div class="modal-card">
      <h3>Are you sure?</h3>
      <p>This action cannot be undone. Do you really want to delete this bus?</p>
      <form method="POST">
        <input type="hidden" name="delete_bus" id="deleteBusId">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="modal-actions">
          <button type="submit" class="confirm-btn">Yes, Delete</button>
          <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openDeleteModal(busId) {
      document.getElementById('deleteBusId').value = busId;
      document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }

    // Auto-dismiss alert
    window.addEventListener('DOMContentLoaded', () => {
      const alertBox = document.getElementById('alertBox');
      if (alertBox) {
        setTimeout(() => {
          alertBox.style.opacity = '0';
          setTimeout(() => alertBox.remove(), 500);
        }, 4000);
      }
    });
  </script>

</body>
</html>
