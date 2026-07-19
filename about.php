<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us — CMS</title>
  <!-- Fonts are self-hosted via assets/beautify.css (works offline) -->
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <style>
    /* Global Styles matching home.php */
    *,
    *::before,
    *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #2563eb;
      --primary-dark: #1d4ed8;
      --primary-light: #eff6ff;
      --accent: #f59e0b;
      --dark: #0f172a;
      --dark2: #1e293b;
      --text: #1e293b;
      --muted: #64748b;
      --border: #e2e8f0;
      --bg: #f8fafc;
      --white: #ffffff;
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
      --shadow-sm: 0 1px 6px rgba(0, 0, 0, 0.06);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Navbar ── */
    .navbar {
      background: var(--dark);
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
    }

    .navbar-inner {
      max-width: 1240px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      height: 68px;
    }

    .navbar .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--white);
    }

    .logo-icon {
      width: 36px;
      height: 36px;
      background: var(--primary);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      font-weight: 800;
    }

    .logo-text {
      font-family: 'Playfair Display', serif;
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .logo-text span {
      color: var(--accent);
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .nav-links a {
      color: #94a3b8;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      padding: 8px 14px;
      border-radius: 8px;
      transition: all .2s;
    }

    .nav-links a:hover,
    .nav-links a.active {
      background: rgba(255, 255, 255, 0.08);
      color: var(--white);
    }

    .nav-links a.register-btn {
      background: var(--primary);
      color: var(--white);
      padding: 8px 18px;
      font-weight: 600;
      margin-left: 8px;
    }

    .nav-links a.register-btn:hover {
      background: var(--primary-dark);
    }

    .user-profile-nav {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 12px;
      padding-left: 16px;
      border-left: 1px solid rgba(255, 255, 255, 0.1);
    }

    .avatar-sm {
      width: 32px;
      height: 32px;
      background: var(--primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      color: var(--white);
      text-decoration: none;
    }

    /* ── Page Header ── */
    .page-header {
      background: linear-gradient(135deg, var(--dark) 0%, var(--dark2) 100%);
      padding: 60px 24px;
      text-align: center;
      color: var(--white);
    }

    .page-header h1 {
      font-family: 'Playfair Display', serif;
      font-size: 36px;
      font-weight: 800;
      margin-bottom: 12px;
    }

    .page-header p {
      color: #94a3b8;
      font-size: 16px;
      max-width: 600px;
      margin: 0 auto;
    }

    /* ── Main Content Area ── */
    .main-container {
      flex: 1;
      max-width: 900px;
      margin: -30px auto 60px;
      /* Overlaps the header slightly for a modern look */
      padding: 0 24px;
      position: relative;
      z-index: 10;
      width: 100%;
    }

    .about-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 48px;
    }

    .about-card h2 {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      color: var(--text);
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--border);
    }

    .content-text p {
      font-size: 16px;
      color: var(--muted);
      line-height: 1.8;
      margin-bottom: 20px;
    }

    .content-text p:last-child {
      margin-bottom: 0;
    }

    /* ── Team Section ── */
    .team-section {
      margin-top: 48px;
    }

    .team-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
    }

    .team-header h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -0.3px;
    }

    .team-header .dot {
      width: 8px;
      height: 8px;
      background: var(--primary);
      border-radius: 50%;
    }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 24px;
    }

    .team-member {
      background: var(--bg);
      border: 1px solid var(--border);
      padding: 24px;
      border-radius: 8px;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .team-member:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-sm);
    }

    .team-icon {
      font-size: 28px;
      margin-bottom: 16px;
      display: inline-block;
    }

    .team-role {
      font-size: 12px;
      font-weight: 700;
      color: var(--primary);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 6px;
    }

    .team-detail {
      font-size: 15px;
      font-weight: 600;
      color: var(--text);
      line-height: 1.5;
    }

    /* ── Footer (copyright only) ── */
    footer {
      background: var(--dark);
      color: #94a3b8;
      margin-top: auto;
    }

    .footer-bottom {
      padding: 20px 24px;
      text-align: center;
      max-width: 1240px;
      margin: 0 auto;
      font-size: 13px;
    }

    @media(max-width: 768px) {
      .about-card {
        padding: 32px 24px;
      }
    }

    @media(max-width: 640px) {
      .nav-links a:not(.register-btn) {
        display: none;
      }
    }
  </style>
  <link rel="stylesheet" href="assets/beautify.css?v=3">
  <script src="assets/beautify.js" defer></script>
</head>

<body>

  <nav class="navbar">
    <div class="navbar-inner">
      <a href="index.php?page=home" class="logo">
        <div class="logo-icon">C</div>
        <span class="logo-text">CM<span>S</span></span>
      </a>
      <div class="nav-links">
        <a href="index.php?page=home">Home</a>
        <a href="index.php?page=about" class="active">About</a>

        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if (in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'superadmin'])): ?>
            <a href="index.php?page=admin" class="register-btn">Dashboard</a>
          <?php else: ?>
            <a href="index.php?page=detail" class="register-btn">My Profile</a>
          <?php endif; ?>

          <div class="user-profile-nav">
            <span style="font-size: 13px; font-weight: 500; color: var(--white);">
              <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="index.php?page=detail" class="avatar-sm">
              <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </a>
          </div>
        <?php else: ?>
          <a href="index.php?page=login">Login</a>
          <a href="index.php?page=register" class="register-btn">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <header class="page-header">
    <h1>Our Story</h1>
    <p>Discover the mission and technology driving our content platform.</p>
  </header>

  <main class="main-container">
    <div class="about-card">

      <h2>About Us</h2>

      <div class="content-text">
        <p>
          Welcome to our Content Management System (CMS), a modern platform designed to share news, ideas, and stories
          across multiple domains like technology, health, finance, and more.
        </p>
        <p>
          Our mission is simple — provide a clean, fast, and user-friendly environment where users can read, write, and
          explore content without distractions.
        </p>
        <p>
          This platform is built using modern web standards, ensuring reliability and scalability while maintaining a
          seamless, highly responsive user experience across all devices.
        </p>
        <p>
          Whether you're a reader looking for the latest updates or a writer wanting to share your thoughts, this CMS is
          built specifically for you.
        </p>
      </div>

      <div class="team-section">
        <div class="team-header">
          <div class="dot"></div>
          <h3>Meet the Team</h3>
        </div>

        <div class="team-grid">
          <div class="team-member">
            <span class="team-icon">👨‍💻</span>
            <div class="team-role">Team Member</div>
            <div class="team-detail">Mustufa</div>
          </div>

          <div class="team-member">
            <span class="team-icon">👨‍💻</span>
            <div class="team-role">Team Member</div>
            <div class="team-detail">Meet</div>
          </div>

          <div class="team-member">
            <span class="team-icon">👨‍💻</span>
            <div class="team-role">Team Member</div>
            <div class="team-detail">Yusuf</div>
          </div>
          <div class="team-member">
            <span class="team-icon">👨‍💻</span>
            <div class="team-role">Team Member</div>
            <div class="team-detail">Parixit</div>
          </div>
        </div>
      </div>

      <div class="team-section">
        <div class="team-header">
          <div class="dot"></div>
          <h3>Project Details</h3>
        </div>

        <div class="team-grid">
          <div class="team-member">
            <span class="team-icon">🛠️</span>
            <div class="team-role">Core Technology</div>
            <div class="team-detail">PHP, MySQL, HTML, CSS</div>
          </div>

          <div class="team-member">
            <span class="team-icon">🚀</span>
            <div class="team-role">Platform Goal</div>
            <div class="team-detail">Build a powerful, intuitive news distribution hub</div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <footer>
    <div class="footer-bottom">
      <p>COPYRIGHT &copy; 2024 CMS &mdash; Built with passion for journalism. All rights reserved.</p>
    </div>
  </footer>

</body>

</html>