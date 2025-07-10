<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../auth/login.php");
  exit();
}

$alert = "";

// Add new conductor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_conductor'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $stmt = $conn->prepare("INSERT INTO conductors (name, email, password) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $name, $email, $password);
  $stmt->execute();
  header("Location: manage-conductors.php");
  exit();
}

// Update conductor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_conductor'])) {
  $id = intval($_POST['conductor_id']);
  $name = $_POST['edit_name'];
  $email = $_POST['edit_email'];
  $password = $_POST['edit_password'];

  if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE conductors SET name=?, email=?, password=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $hashed, $id);
  } else {
    $stmt = $conn->prepare("UPDATE conductors SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $id);
  }
  $stmt->execute();
  header("Location: manage-conductors.php");
  exit();
}

// Prevent delete if assigned to any route
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);

  $check = $conn->prepare("SELECT id FROM routes WHERE conductor_id = ?");
  $check->bind_param("i", $id);
  $check->execute();
  $res = $check->get_result();

  if ($res->num_rows === 0) {
    $stmt = $conn->prepare("DELETE FROM conductors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage-conductors.php");
  } else {
    $alert = "Cannot delete: Conductor is assigned to a route.";
  }
}

// Fetch all conductors
$conductors = $conn->query("SELECT * FROM conductors ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Conductors – GoBus</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../assets/css/admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    .conductor-section { margin-top: 2rem; }
    .conductor-form {
      background: var(--white);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
    }
    .conductor-form h3 { margin-bottom: 1rem; font-size: 1.3rem; }
    .conductor-form input {
      width: 100%;
      padding: 12px;
      margin-bottom: 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }
    .conductor-form button {
      background: var(--accent);
      border: none;
      color: white;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
    }

    .conductor-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .conductor-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.2s ease;
    }

    .conductor-card:hover {
      transform: translateY(-3px);
    }

    .conductor-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .conductor-index {
      font-size: 1rem;
      font-weight: 600;
      color: var(--primary);
    }

    .conductor-body p {
      margin: 6px 0;
      font-size: 0.95rem;
      color: #333;
    }

    .conductor-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .action-btn {
      background: #007C8C;
      color: white;
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      font-size: 0.9rem;
      cursor: pointer;
    }
    .delete-btn {
      background: crimson;
    }

    .modal, .alert-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.4);
      align-items: center;
      justify-content: center;
      z-index: 999;
    }
    .modal-content, .alert-content {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      max-width: 400px;
      width: 90%;
      position: relative;
      text-align: left;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .modal-content input {
      width: 100%;
      margin-bottom: 1rem;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    .modal-content .btn {
      background: var(--accent);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
    }
    .close {
      position: absolute;
      right: 16px;
      top: 16px;
      cursor: pointer;
      font-size: 1.2rem;
      color: #aaa;
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
    <h1>Manage Conductors</h1>
    <p>Add, edit, or remove conductors for assigned routes.</p>
  </header>

  <section class="conductor-section">
    <form method="POST" class="conductor-form">
      <h3>Add New Conductor</h3>
      <input type="text" name="name" placeholder="Conductor Name" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit" name="add_conductor">Add Conductor</button>
    </form>

    <div class="conductor-cards">
      <?php $i = 1; while ($row = $conductors->fetch_assoc()): ?>
        <div class="conductor-card">
          <div class="conductor-header">
            <span class="conductor-index">#<?= $i++ ?></span>
            <div class="conductor-actions">
              <button class="action-btn" onclick="editConductor(<?= $row['id'] ?>, '<?= $row['name'] ?>', '<?= $row['email'] ?>')">Edit</button>
              <a class="action-btn delete-btn" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this conductor?');">Delete</a>
            </div>
          </div>
          <div class="conductor-body">
            <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </section>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">×</span>
    <form method="POST">
      <input type="hidden" name="conductor_id" id="edit_id">
      <input type="text" name="edit_name" id="edit_name" placeholder="Name" required>
      <input type="email" name="edit_email" id="edit_email" placeholder="Email" required>
      <input type="password" name="edit_password" placeholder="New Password (leave blank to keep unchanged)">
      <button type="submit" name="update_conductor" class="btn">Update</button>
    </form>
  </div>
</div>

<!-- Alert Modal -->
<?php if (!empty($alert)): ?>
<div class="alert-modal" id="alertModal" style="display: flex;">
  <div class="alert-content">
    <span class="close" onclick="closeAlert()">×</span>
    <p><?= htmlspecialchars($alert) ?></p>
    <button class="btn" onclick="closeAlert()">Okay</button>
  </div>
</div>
<?php endif; ?>

<script>
function editConductor(id, name, email) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_email').value = email;
  document.getElementById('editModal').style.display = 'flex';
}
function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}
function closeAlert() {
  document.getElementById('alertModal').style.display = 'none';
}
</script>

</body>
</html>
