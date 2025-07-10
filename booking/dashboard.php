<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
  header("Location: ../auth/login.php");
  exit();
}
$name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'traveler';

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>GoBus – Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #007C8C;
      --accent: #FFA534;
      --gray: #AAB8C2;
      --bg: #F4F4F4;
      --white: #ffffff;
      --dark: #012F34;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--dark);
    }

    nav {
      background-color: var(--dark);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      position: relative;
    }

    nav .logo img {
      height: 42px;
    }

    nav .nav-links {
      display: flex;
      gap: 1.2rem;
      flex-wrap: wrap;
    }

    nav .nav-links a {
      color: var(--white);
      text-decoration: none;
      font-weight: 500;
      padding: 0.4rem 0.8rem;
      border-radius: 5px;
      transition: background 0.3s;
    }

    nav .nav-links a:hover {
      background-color: rgba(255,255,255,0.1);
    }

    .mobile-toggle {
      display: none;
      font-size: 1.5rem;
      background: none;
      border: none;
      color: var(--white);
      cursor: pointer;
    }

    .hero-section {
      padding: 3rem 1rem;
      text-align: center;
      background: linear-gradient(to bottom right, #F9FCFC, #E8F4F4);
    }

    .hero-section h1 {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }

    .hero-section p {
      font-size: 1rem;
      color: var(--gray);
      margin-bottom: 2rem;
    }

    .search-box {
      max-width: 800px;
      margin: 0 auto;
      background: var(--white);
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
    }

    .search-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .search-grid label {
      font-weight: 500;
      margin-bottom: 0.3rem;
      display: block;
    }

    .search-grid input {
      padding: 0.9rem 1rem;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      width: 100%;
    }

    .btn-search {
      width: 100%;
      padding: 1rem;
      font-size: 1rem;
      font-weight: 600;
      background: var(--accent);
      border: none;
      border-radius: 12px;
      color: var(--white);
      cursor: pointer;
      transition: background 0.3s;
    }

    .btn-search:hover {
      background: #e18e2c;
    }

    .swap-btn {
      background: none;
      border: 1px dashed var(--primary);
      color: var(--primary);
      padding: 0.4rem 0.8rem;
      font-size: 0.9rem;
      border-radius: 10px;
      cursor: pointer;
      margin: 0 auto;
      display: block;
    }

    @media (max-width: 768px) {
      nav .mobile-toggle {
        display: block;
      }

      nav .nav-links {
        display: none;
        flex-direction: column;
        width: 100%;
        background: var(--dark);
        margin-top: 1rem;
      }

      nav .nav-links.show {
        display: flex;
      }

      .hero-section h1 {
        font-size: 1.5rem;
      }

      .search-box {
        padding: 1.2rem;
      }
    }
  </style>
</head>
<body>

  <nav>
    <div class="logo">
      <a href="dashboard.php">
        <img src="../assets/images/Logo.png" alt="GoBus Logo">
      </a>
    </div>
    <button class="mobile-toggle" onclick="toggleMenu()">☰</button>
    <div class="nav-links" id="navLinks">
      <a href="dashboard.php">Book Trip</a>
      <a href="history.php">My Bookings</a>
      <a href="../auth/logout.php">Logout</a>
    </div>
  </nav>

  <div class="hero-section">
    <h1>Welcome, <?= $name ?>!</h1>
    <p>Book your next journey with ease using GoBus.</p>

    <div class="search-box">
      <form action="available-trips.php" method="GET">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="search-grid">
          <div>
            <label for="origin">From (Origin)</label>
            <input type="text" name="origin" id="origin" placeholder="Enter origin" list="originList" autocomplete="off" required>
            <datalist id="originList"></datalist>
          </div>
          <div>
            <label for="destination">To (Destination)</label>
            <input type="text" name="destination" id="destination" placeholder="Enter destination" list="destinationList" autocomplete="off" required>
            <datalist id="destinationList"></datalist>
          </div>
          <div>
            <label for="date">Travel Date</label>
            <input type="date" name="date" id="date" required>
          </div>
        </div>
        <button type="button" class="swap-btn" onclick="swapLocations()">Swap Origin & Destination</button>
        <br><br>
        <button type="submit" class="btn-search">Search Trips</button>
      </form>
    </div>
  </div>

  <script>
    function toggleMenu() {
      document.getElementById("navLinks").classList.toggle("show");
    }

    function swapLocations() {
      const origin = document.getElementById("origin");
      const destination = document.getElementById("destination");
      const temp = origin.value;
      origin.value = destination.value;
      destination.value = temp;
    }

    function setupLiveSearch(inputId, datalistId, type) {
      const input = document.getElementById(inputId);
      const datalist = document.getElementById(datalistId);
      let debounceTimer;

      input.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();
        if (query.length < 1) return;

        debounceTimer = setTimeout(() => {
          fetch(`search-location.php?q=${encodeURIComponent(query)}&type=${type}`)
            .then(res => res.json())
            .then(data => {
              datalist.innerHTML = "";
              data.forEach(item => {
                const option = document.createElement("option");
                option.value = item;
                datalist.appendChild(option);
              });
            })
            .catch(err => console.error("Fetch error:", err));
        }, 300);
      });
    }

    setupLiveSearch("origin", "originList", "origin");
    setupLiveSearch("destination", "destinationList", "destination");

    // Restrict past dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById("date").setAttribute("min", today);
  </script>

</body>
</html>
