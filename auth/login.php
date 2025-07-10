<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GoBus – Login</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    .form-group select {
      padding: 14px 16px;
      border: 1px solid #ddd;
      border-radius: 10px;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
      background-color: #fff;
      color: #333;
      transition: 0.2s border ease;
    }

    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(0, 124, 140, 0.1);
    }

    .error-box {
      background-color: #ffe6e6;
      color: #cc0000;
      border: 1px solid #ffb3b3;
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-weight: 500;
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>
<body>

  <div class="container">
    <img src="../assets/images/login-illustration.png" alt="Login Illustration" class="login-illustration" />

    <div class="login-box">
      <h2>Welcome to GoBus</h2>
      <p>Login to book your journey and track your trips in real-time.</p>

      <?php if (isset($_GET['error'])): ?>
        <div class="error-box">
          ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
      <?php endif; ?>

      <form action="login-handler.php" method="POST">
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required />
        </div>

        <div class="form-group">
          <label for="role">Login As</label>
          <select id="role" name="role" required>
            <option value="user" selected>User</option>
            <option value="admin">Admin</option>
            <option value="conductor">Conductor</option>
          </select>
        </div>

        <button type="submit" class="login-btn">Login</button>
      </form>

      <div class="register-link">
        New to GoBus? <a href="register.php">Create an account</a>
      </div>
    </div>
  </div>

</body>
</html>
