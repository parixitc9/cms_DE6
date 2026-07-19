<?php
// login.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Database connection fallback if not routed through a master index
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
}

$error_msg = "";

// Check for errors passed via session (e.g., from a sidebar login attempt)
if (isset($_SESSION['login_error'])) {
    $error_msg = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear after displaying
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, role FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            // Login success! Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if(in_array(strtolower($user['role']), ['admin', 'superadmin'])) {
                header("Location: index.php?page=admin");
            } else {
                // If they have a redirect URL set (smart redirects)
                if(isset($_SESSION['redirect_after_login'])) {
                    $redirect_url = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirect_url);
                } else {
                    header("Location: index.php?page=home");
                }
            }
            exit();
        } else {
             $error_msg = "Invalid username or password!";
        }
    } else {
         $error_msg = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — CMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <style>
    /* Consistently using home.php CSS variables */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --primary: #2563eb;
      --primary-dark: #1d4ed8;
      --primary-light: #eff6ff;
      --accent: #f59e0b;
      --dark: #0f172a;
      --text: #1e293b;
      --muted: #64748b;
      --border: #e2e8f0;
      --bg: #f8fafc;
      --white: #ffffff;
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }
    
    /* ── Navbar styling from home.php ── */
    .navbar { background: var(--dark); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 20px rgba(0,0,0,0.3); }
    .navbar-inner { max-width: 1240px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 68px; }
    .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--white); }
    .logo-icon { width: 36px; height: 36px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; }
    .logo-text { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
    .logo-text span { color: var(--accent); }
    .nav-links a { color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; padding: 8px 14px; border-radius: 8px; transition: all .2s; }
    .nav-links a:hover { background: rgba(255,255,255,0.08); color: var(--white); }

    /* ── Login Form Container ── */
    .main-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 24px; }
    .auth-container { width: 100%; max-width: 450px; }
    .auth-card { background: var(--white); padding: 48px 40px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); }
    
    .auth-header { text-align: center; margin-bottom: 32px; }
    .auth-header .logo-icon { margin: 0 auto 16px; width: 48px; height: 48px; font-size: 24px; }
    .auth-header h1 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .auth-header p { color: var(--muted); font-size: 14px; }

    .error-msg { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; font-size: 13px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 8px; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .form-group input { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 14px; background: var(--bg); transition: all 0.2s; font-family: inherit; }
    .form-group input:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    
    .btn-submit { width: 100%; padding: 14px; background: var(--primary); color: var(--white); border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; transition: background 0.2s; font-family: inherit; margin-top: 8px; }
    .btn-submit:hover { background: var(--primary-dark); }
    
    .auth-footer { margin-top: 24px; text-align: center; font-size: 14px; color: var(--muted); }
    .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
    .auth-footer a:hover { text-decoration: underline; }

    /* ── Footer ── */
    footer { background: var(--dark); padding: 24px; text-align: center; font-size: 13px; color: #64748b; margin-top: auto; border-top: 1px solid #1e293b; }
  </style>
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
        <a href="index.php?page=about">About</a>
      </div>
    </div>
  </nav>

  <main class="main-container">
    <div class="auth-container">
      <div class="auth-card">
        
        <div class="auth-header">
          <div class="logo-icon">C</div>
          <h1>Welcome Back</h1>
          <p>Sign in to your account to continue.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
          <div class="error-msg">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter your username" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
          </div>
          
          <button type="submit" class="btn-submit">Sign In &#8594;</button>
        </form>

        <div class="auth-footer">
          Don't have an account? <a href="index.php?page=register">Create one</a><br><br>
          <span title="Please contact the site administrator to reset your password." style="font-size: 13px; color: var(--muted); cursor: default;">Forgot your password?</span>
        </div>

      </div>
    </div>
  </main>

  <footer>
    <p>COPYRIGHT &copy; 2024 CMS &mdash; All rights reserved.</p>
  </footer>

</body>
</html>