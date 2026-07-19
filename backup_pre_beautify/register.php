<?php
// Database configuration
// If this is routed through index.php, $conn might already be set. If not, establish it:
if (!isset($conn)) {
    $servername = "localhost"; 
    $db_username = "root"; 
    $db_password = ""; 
    $dbname = "cms"; 

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Initialize error and success messages
$error_msg = "";
$success_msg = "";

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];

    // Validation
    if (empty($username) || empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirmpassword)) {
        $error_msg = "All fields are required.";
    } elseif ($password !== $confirmpassword) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error_msg = "Username or email already exists.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, email, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $firstname, $lastname, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success_msg = "Registration successful! You can now log in.";
                // Clear POST variables so the form empties out upon success
                $_POST = array(); 
            } else {
                $error_msg = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — CMS</title>
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
    .nav-links a.active { background: var(--primary); color: var(--white); font-weight: 600; }

    /* ── Registration Form Container ── */
    .main-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 24px; }
    .auth-container { width: 100%; max-width: 550px; }
    .auth-card { background: var(--white); padding: 48px 40px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); }
    
    .auth-header { text-align: center; margin-bottom: 32px; }
    .auth-header .logo-icon { margin: 0 auto 16px; width: 48px; height: 48px; font-size: 24px; }
    .auth-header h1 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .auth-header p { color: var(--muted); font-size: 14px; }

    .error-msg { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; font-size: 13px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 8px; }
    .success-msg { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 16px; font-size: 13px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 8px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-row .form-group { margin-bottom: 0; }
    
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .form-group input { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 14px; background: var(--bg); transition: all 0.2s; font-family: inherit; color: var(--text); }
    .form-group input:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    
    .btn-submit { width: 100%; padding: 14px; background: var(--primary); color: var(--white); border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; transition: background 0.2s; font-family: inherit; margin-top: 8px; }
    .btn-submit:hover { background: var(--primary-dark); }
    
    .auth-footer { margin-top: 24px; text-align: center; font-size: 14px; color: var(--muted); }
    .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
    .auth-footer a:hover { text-decoration: underline; }

    /* ── Footer ── */
    footer { background: var(--dark); padding: 24px; text-align: center; font-size: 13px; color: #64748b; margin-top: auto; border-top: 1px solid #1e293b; }

    @media (max-width: 600px) {
      .auth-card { padding: 32px 24px; }
      .form-row { grid-template-columns: 1fr; gap: 20px; }
      .form-row .form-group { margin-bottom: 0; }
    }
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
        <a href="index.php?page=home">Trending News</a>
        <a href="index.php?page=about">About Us</a>
        <a href="index.php?page=login">Login</a>
      </div>
    </div>
  </nav>

  <main class="main-container">
    <div class="auth-container">
      <div class="auth-card">
        
        <div class="auth-header">
          <div class="logo-icon">C</div>
          <h1>Create an Account</h1>
          <p>Join the community to start publishing and commenting.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
          <div class="error-msg">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
          <div class="success-msg">✅ <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Choose a unique username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="firstname">First Name</label>
              <input type="text" name="firstname" id="firstname" placeholder="John" value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" required>
            </div>
            <div class="form-group">
              <label for="lastname">Last Name</label>
              <input type="text" name="lastname" id="lastname" placeholder="Doe" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" name="password" id="password" placeholder="Min. 6 characters" required>
            </div>
            <div class="form-group">
              <label for="confirmpassword">Confirm Password</label>
              <input type="password" name="confirmpassword" id="confirmpassword" placeholder="Re-enter password" required>
            </div>
          </div>

          <button type="submit" class="btn-submit">Create Account &#8594;</button>
        </form>

        <div class="auth-footer">
          Already have an account? <a href="index.php?page=login">Sign in here</a>
        </div>

      </div>
    </div>
  </main>

  <footer>
    <p>COPYRIGHT &copy; 2024 CMS &mdash; All rights reserved.</p>
  </footer>

</body>
</html>