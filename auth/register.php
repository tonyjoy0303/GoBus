<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GoBus – Register</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>
<body>

  <div class="container">
    <img src="../assets/images/register-illustration.png" alt="Register Illustration" class="register-illustration" />

    <div class="register-box">
      <h2>Create your GoBus account</h2>
      <p>Book tickets, track buses, and manage your journeys easily.</p>

      <form action="register-handler.php" method="POST">
        <div class="form-group">
          <label for="name">Full name</label>
          <input type="text" id="name" name="name" placeholder="Your name" required />
        </div>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required />
        </div>
        <div class="form-group">
          <label for="phone">Phone number</label>
          <input type="tel" id="phone" name="phone" placeholder="+91-XXXXXXXXXX" required />
        </div>
        <div class="form-group">
          <label for="password">Create password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required />
        </div>
        <button type="submit" class="register-btn">Register</button>
      </form>

      <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
      </div>
    </div>
  </div>

</body>
</html>
