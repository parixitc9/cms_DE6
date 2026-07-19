<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security check: Only allow admin or superadmin
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin', 'superadmin'])) {
    header("Location: index.php?page=home"); 
    exit();
}

// Database connection fallback (in case it's not already included by the master router)
if (!isset($conn)) {
    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "cms";
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
}

$error_msg = "";
$success_msg = "";

// 1. Handle Form Submission to Update User
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_user_id = (int)$_POST['user_id'];
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);

    // Prevent an admin from accidentally downgrading their own role from this page
    if ($target_user_id === $_SESSION['user_id'] && $role !== $_SESSION['role']) {
        $error_msg = "You cannot change your own role here. Please use the profile settings.";
    } else {
        $update_sql = "UPDATE users SET firstname='$firstname', lastname='$lastname', email='$email', role='$role' WHERE id=$target_user_id";
        if ($conn->query($update_sql)) {
            // Redirect back to the user list with a success alert
            echo "<script>alert('User updated successfully!'); window.location.href='index.php?page=list';</script>";
            exit();
        } else {
            $error_msg = "Error updating user: " . $conn->error;
        }
    }
}

// 2. Fetch Existing User Data to Populate the Form
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_user = null;

if ($edit_id > 0) {
    $fetch_sql = "SELECT * FROM users WHERE id = $edit_id";
    $result = $conn->query($fetch_sql);
    if ($result && $result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
    } else {
        die("<script>alert('User not found!'); window.location.href='index.php?page=list';</script>");
    }
} else {
    die("<script>alert('Invalid User ID!'); window.location.href='index.php?page=list';</script>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User — CMS Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <style>
    /* Consistently using CMS UI CSS variables */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
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
      --red: #ef4444;
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0,0,0,0.08);
      --shadow-sm: 0 1px 6px rgba(0,0,0,0.06);
      --sidebar-w: 260px;
    }
    
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
    
    /* ── Sidebar ── */
    .sidebar { 
      width: var(--sidebar-w); 
      background: var(--dark); 
      color: #94a3b8; 
      display: flex; 
      flex-direction: column; 
      flex-shrink: 0; 
      min-height: 100vh; 
      position: fixed; 
      top: 0; left: 0; bottom: 0; 
      z-index: 100;
      box-shadow: 2px 0 20px rgba(0,0,0,0.2);
    }
    .sidebar .brand { 
      padding: 20px 24px; 
      display: flex; 
      align-items: center; 
      gap: 10px; 
      border-bottom: 1px solid rgba(255,255,255,0.05);
      text-decoration: none;
      height: 68px;
    }
    .logo-icon { 
      width: 32px; height: 32px; 
      background: var(--primary); 
      border-radius: 8px; 
      display: flex; align-items: center; justify-content: center; 
      font-size: 16px; font-weight: 800; color: var(--white);
    }
    .logo-text { 
      font-family: 'Playfair Display', serif; 
      font-size: 20px; font-weight: 700; color: var(--white); 
      letter-spacing: -0.5px; 
    }
    .logo-text span { color: var(--accent); }
    .brand small {
      display: block; font-family: 'Inter', sans-serif; font-size: 10px; 
      text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-top: 2px;
    }
    
    .sidebar nav { padding: 24px 12px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
    .sidebar nav a { 
      display: flex; align-items: center; gap: 12px; 
      color: #94a3b8; text-decoration: none; 
      padding: 12px 16px; border-radius: 8px;
      font-size: 14px; font-weight: 500; transition: all .2s; 
    }
    .sidebar nav a:hover { background: rgba(255,255,255,0.05); color: var(--white); }
    .sidebar nav a.active { background: var(--primary); color: var(--white); font-weight: 600; box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
    .sidebar nav a.logout-btn { color: var(--red); margin-top: auto; }
    .sidebar nav a.logout-btn:hover { background: #fef2f2; color: #dc2626; }
    
    /* ── Page Content ── */
    .page { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; width: calc(100% - var(--sidebar-w)); }
    
    /* ── Topbar ── */
    .topbar { 
      background: var(--white); 
      border-bottom: 1px solid var(--border); 
      padding: 0 32px; height: 68px; 
      display: flex; align-items: center; justify-content: space-between; 
      position: sticky; top: 0; z-index: 50; 
    }
    .topbar .page-title { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: var(--text); }
    .topbar .right { display: flex; align-items: center; gap: 24px; }
    .topbar .visit-link { color: var(--primary); text-decoration: none; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
    .topbar .visit-link:hover { color: var(--primary-dark); }
    
    .user-profile { display: flex; align-items: center; gap: 12px; padding-left: 24px; border-left: 1px solid var(--border); }
    .topbar .user-info { font-size: 12px; color: var(--muted); text-align: right; }
    .topbar .user-info strong { color: var(--text); display: block; font-size: 14px; font-weight: 600; text-transform: capitalize; }
    .avatar { 
      width: 40px; height: 40px; 
      background: var(--primary-light); color: var(--primary); 
      border-radius: 50%; display: flex; align-items: center; justify-content: center; 
      font-size: 16px; font-weight: 700; 
    }
    
    /* ── Form Area ── */
    .content { padding: 32px; flex: 1; max-width: 900px; margin: 0 auto; width: 100%; }
    
    .page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
    .back-btn { color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; transition: color 0.2s; }
    .back-btn:hover { color: var(--primary); }
    .page-header h2 { font-size: 24px; font-weight: 800; color: var(--text); }
    
    .form-panel { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
    .form-head { padding: 24px 32px; border-bottom: 1px solid var(--border); font-size: 18px; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 12px; }
    .target-user-badge { background: var(--primary-light); color: var(--primary); font-size: 13px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
    
    .form-body { padding: 32px; }
    .error-msg { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; font-size: 14px; font-weight: 500; margin-bottom: 24px; display: flex; align-items: center; gap: 8px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-group.full { grid-column: 1 / -1; }
    
    label { font-size: 13px; font-weight: 600; color: var(--text); }
    input[type="text"], input[type="email"], select { 
      width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); 
      border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text); 
      background: var(--bg); transition: all 0.2s; 
    }
    input:focus, select:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    input[disabled] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: var(--border); }
    
    .divider { border: none; border-top: 1px dashed var(--border); margin: 12px 0 32px; }
    
    .btn-row { display: flex; gap: 16px; margin-top: 32px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: background .2s; font-family: inherit; }
    .btn-primary { background: var(--primary); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-secondary { background: var(--bg); color: var(--text); text-decoration: none; border: 1.5px solid var(--border); }
    .btn-secondary:hover { background: #e2e8f0; }
    
    @media(max-width: 1024px) { 
      .sidebar { display: none; } 
      .page { margin-left: 0; width: 100%; } 
      .user-profile { border-left: none; padding-left: 0; }
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <aside class="sidebar">
    <a href="index.php?page=admin" class="brand">
      <div class="logo-icon">C</div>
      <div>
        <div class="logo-text">CM<span>S</span></div>
        <small>Admin Panel</small>
      </div>
    </a>
    <nav>
      <a href="index.php?page=admin"><span class="icon">📊</span> Dashboard</a>
      <a href="index.php?page=post"><span class="icon">📝</span> Manage Posts</a>
      <a href="index.php?page=comments"><span class="icon">💬</span> Manage Comments</a>
      <a href="index.php?page=list" class="active"><span class="icon">👥</span> Manage Users</a>
      <a href="index.php?page=detail"><span class="icon">👤</span> My Profile</a>
      <a href="index.php?page=logout" class="logout-btn"><span class="icon">🚪</span> Logout</a>
    </nav>
  </aside>

  <div class="page">
    <div class="topbar">
      <span class="page-title">Users Directory</span>
      <div class="right">
        <a href="index.php?page=home" class="visit-link">🌐 Visit Site</a>
        <div class="user-profile">
          <div class="user-info">
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
            <span style="text-transform: capitalize;"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Role'); ?></span>
          </div>
          <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
        </div>
      </div>
    </div>
    
    <div class="content">
      <div class="page-header">
        <a href="index.php?page=list" class="back-btn">&#8592; Back to Users List</a>
      </div>

      <div class="form-panel">
        <div class="form-head">
          Edit Account Data
          <span class="target-user-badge">@<?php echo htmlspecialchars($edit_user['username']); ?></span>
        </div>
        <div class="form-body">
          
          <?php if (!empty($error_msg)): ?>
            <div class="error-msg">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
          <?php endif; ?>

          <form method="POST" action="">
            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
            
            <div class="form-row">
              <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($edit_user['username']); ?>" disabled>
              </div>
              <div class="form-group">
                <label>System Role</label>
                <select name="role" required>
                  <option value="user" <?php echo ($edit_user['role'] == 'user') ? 'selected' : ''; ?>>Standard User</option>
                  <option value="subadmin" <?php echo ($edit_user['role'] == 'subadmin') ? 'selected' : ''; ?>>Sub Admin</option>
                  <option value="admin" <?php echo ($edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Full Admin</option>
                  <option value="superadmin" <?php echo ($edit_user['role'] == 'superadmin') ? 'selected' : ''; ?>>Super Admin</option>
                </select>
              </div>
            </div>

            <hr class="divider">

            <div class="form-row">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstname" value="<?php echo htmlspecialchars($edit_user['firstname']); ?>" required>
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastname" value="<?php echo htmlspecialchars($edit_user['lastname']); ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
              </div>
            </div>

            <div class="btn-row">
              <button type="submit" class="btn btn-primary">Update User Settings</button>
              <a href="index.php?page=list" class="btn btn-secondary">Cancel</a>
            </div>
            
          </form>
          
        </div>
      </div>
    </div>
  </div>

</body>
</html>