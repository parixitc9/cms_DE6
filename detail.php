<?php
// Ensure only logged in users see this
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['user_id'])) { die("Access Denied."); }

// Safe check in case db.php isn't loaded directly in this file but expected globally
if(isset($conn)) {
    $user_id = $_SESSION['user_id'];
    $u_sql = "SELECT * FROM users WHERE id = $user_id";
    $user_data = $conn->query($u_sql)->fetch_assoc();

    // Count their posts
    $p_count_sql = "SELECT COUNT(*) as total_posts FROM posts WHERE user_id = $user_id";
    $post_count = $conn->query($p_count_sql)->fetch_assoc()['total_posts'];

    // Count their comments
    $c_count_sql = "SELECT COUNT(*) as total_comments FROM comments WHERE user_id = $user_id";
    $comment_count = $conn->query($c_count_sql)->fetch_assoc()['total_comments'];
} else {
    // Fallback if db connection isn't present
    $user_data = ['username' => 'User', 'firstname' => 'First', 'lastname' => 'Last', 'role' => 'user', 'email' => 'user@cms.com'];
    $post_count = 0;
    $comment_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile — CMS</title>
  <!-- Fonts are self-hosted via assets/beautify.css (works offline) -->
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <style>
    /* Consistently using home.php and admin CSS variables */
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
      --green: #10b981;
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
    .sidebar .sidebar-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.05); font-size: 12px; color: #64748b; }

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
    .topbar .user-info strong { color: var(--text); display: block; font-size: 14px; font-weight: 600; }
    .avatar-btn { 
      width: 40px; height: 40px; 
      background: var(--primary-light); color: var(--primary); 
      border-radius: 50%; display: flex; align-items: center; justify-content: center; 
      font-size: 16px; font-weight: 700; cursor: pointer;
    }

    .content { padding: 32px; flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; }

    /* ── Profile Layout ── */
    .profile-grid { display: grid; grid-template-columns: 320px 1fr; gap: 32px; }

    /* Left Card */
    .profile-card { 
      background: var(--white); border-radius: var(--radius); 
      box-shadow: var(--shadow-sm); border: 1px solid var(--border);
      padding: 40px 32px; text-align: center; height: fit-content;
    }
    .big-avatar { 
      width: 100px; height: 100px; background: var(--primary-light); color: var(--primary); 
      border-radius: 50%; display: flex; align-items: center; justify-content: center; 
      font-size: 36px; font-weight: 800; margin: 0 auto 20px; 
      box-shadow: 0 0 0 6px var(--white), 0 0 0 8px var(--border); 
    }
    .profile-card h3 { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 800; color: var(--text); }
    .profile-card .role { 
      display: inline-block; background: var(--bg); border: 1px solid var(--border);
      color: var(--primary); font-size: 12px; font-weight: 700; 
      padding: 4px 14px; border-radius: 20px; margin: 8px 0 16px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .profile-card .email { font-size: 14px; color: var(--muted); margin-bottom: 24px; }

    .profile-meta { list-style: none; text-align: left; border-top: 1px solid var(--border); padding-top: 24px; }
    .profile-meta li { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px dashed var(--border); font-size: 14px; }
    .profile-meta li:last-child { border-bottom: none; padding-bottom: 0; }
    .profile-meta li span:first-child { color: var(--muted); font-weight: 500; }
    .profile-meta li span:last-child { font-weight: 700; color: var(--text); }

    /* Right Form */
    .form-panel { 
      background: var(--white); border-radius: var(--radius); 
      box-shadow: var(--shadow-sm); border: 1px solid var(--border);
    }
    .form-panel .form-head { 
      padding: 24px 32px; border-bottom: 1px solid var(--border); 
      font-size: 18px; font-weight: 700; color: var(--text); letter-spacing: -0.3px;
    }
    .form-body { padding: 32px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
    
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-group.full { grid-column: 1 / -1; }
    label { font-size: 13px; font-weight: 600; color: var(--text); }
    label .optional { color: var(--muted); font-weight: 400; font-size: 12px; font-style: italic; }
    
    input[type="text"], input[type="email"], input[type="password"] { 
      width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); 
      border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text); 
      background: var(--bg); transition: all 0.2s; 
    }
    input:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    input[disabled] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: var(--border); }
    
    .divider { border: none; border-top: 1px solid var(--border); margin: 12px 0 32px; }
    
    .btn-row { display: flex; gap: 16px; margin-top: 32px; }
    .btn { 
      display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; 
      border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; 
      border: none; transition: background .2s; font-family: inherit;
    }
    .btn-primary { background: var(--primary); color: var(--white); }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-secondary { background: var(--bg); color: var(--text); border: 1.5px solid var(--border); }
    .btn-secondary:hover { background: #e2e8f0; }
    
    .success-toast { display: none; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px 20px; font-size: 14px; font-weight: 600; margin-top: 24px; }
    .error-toast { display: none; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; padding: 14px 20px; font-size: 14px; font-weight: 600; margin-top: 24px; }

    @media(max-width: 900px) { 
      .sidebar { display: none; } 
      .page { margin-left: 0; width: 100%; } 
      .profile-grid { grid-template-columns: 1fr; } 
      .form-row { grid-template-columns: 1fr; } 
      .user-profile { border-left: none; padding-left: 0; }
    }
  </style>
  <link rel="stylesheet" href="assets/beautify.css?v=3">
  <script src="assets/beautify.js" defer></script>
</head>
<body>

  <aside class="sidebar">
    <a href="index.php?page=admin" class="brand">
      <div class="logo-icon">C</div>
      <div>
        <div class="logo-text">CM<span>S</span></div>
        <small>User Panel</small>
      </div>
    </a>
    <nav>
      <?php if(isset($user_data['role']) && in_array(strtolower($user_data['role']), ['admin', 'superadmin'])): ?>
          <a href="index.php?page=admin"><span class="icon">📊</span> Dashboard</a>
          <a href="index.php?page=post"><span class="icon">📝</span> Manage Posts</a>
          <a href="index.php?page=comments"><span class="icon">💬</span> Manage Comments</a>
          <a href="index.php?page=list"><span class="icon">👥</span> Manage Users</a>
          <a href="index.php?page=subscribers"><span class="icon">📧</span> Subscribers</a>
      <?php else: ?>
          <a href="index.php?page=create_post"><span class="icon">✍️</span> Write News</a>
          <a href="index.php?page=my_posts"><span class="icon">📝</span> My Posts</a>
          <a href="index.php?page=my_comments"><span class="icon">💬</span> My Comments</a>
      <?php endif; ?>
      <a href="index.php?page=detail" class="active"><span class="icon">👤</span> My Profile</a>
      <a href="index.php?page=logout" class="logout-btn"><span class="icon">🚪</span> Logout</a>
    </nav>
    <div class="sidebar-footer">v1.0.0 &copy; 2024 CMS</div>
  </aside>

  <div class="page">
    <div class="topbar">
      <span class="page-title">My Profile</span>
      <div class="right">
        <a href="index.php?page=home" class="visit-link">🌐 Visit Site</a>
        <div class="user-profile">
          <div class="user-info">
            <strong><?php echo htmlspecialchars($user_data['firstname'] ?? 'User'); ?></strong>
            <span><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></span>
          </div>
          <div class="avatar-btn"><?php echo strtoupper(substr($user_data['username'] ?? 'U', 0, 1)); ?></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="profile-grid">
        
        <div class="profile-card">
          <div class="big-avatar"><?php echo strtoupper(substr($user_data['username'] ?? 'U', 0, 1)); ?></div>
          <h3><?php echo htmlspecialchars(($user_data['firstname'] ?? '') . ' ' . ($user_data['lastname'] ?? '')); ?></h3>
          <div class="role"><?php echo ucfirst(htmlspecialchars($user_data['role'] ?? 'user')); ?></div>
          <p class="email"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
          
          <ul class="profile-meta">
            <li><span>Username</span><span><?php echo htmlspecialchars($user_data['username'] ?? ''); ?></span></li>
            <li><span>Articles Written</span><span><?php echo $post_count; ?></span></li>
            <li><span>Comments Made</span><span><?php echo $comment_count; ?></span></li>
            <li><span>Status</span><span style="color: var(--green);">● Active</span></li>
          </ul>
        </div>

        <div class="form-panel">
          <div class="form-head">Account Settings</div>
          <div class="form-body">
            
            <form id="profileForm" method="POST" action="index.php?page=update_profile" onsubmit="return validateProfile(event)">
              
              <div class="form-row">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" disabled>
                </div>
                <div class="form-group">
                  <label>Account Role</label>
                  <input type="text" value="<?php echo ucfirst(htmlspecialchars($user_data['role'] ?? 'user')); ?>" disabled>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" id="fname" name="firstname" value="<?php echo htmlspecialchars($user_data['firstname'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" id="lname" name="lastname" value="<?php echo htmlspecialchars($user_data['lastname'] ?? ''); ?>" required>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group full">
                  <label>Email Address</label>
                  <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                </div>
              </div>

              <hr class="divider">

              <div class="form-row">
                <div class="form-group">
                  <label>Current Password <span class="optional">(required to save changes)</span></label>
                  <input type="password" id="currentpass" name="currentpass" placeholder="Enter current password" required>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>New Password <span class="optional">(optional)</span></label>
                  <input type="password" id="newpass" name="newpass" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                  <label>Confirm New Password</label>
                  <input type="password" id="confirmpass" placeholder="Re-enter new password">
                </div>
              </div>

              <div class="btn-row">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="reset" class="btn btn-secondary" onclick="hideToasts()">Reset</button>
              </div>

              <div class="success-toast" id="toast-success"
                   style="<?php echo isset($_SESSION['profile_success']) ? 'display:block;' : ''; ?>">
                <?php
                  echo isset($_SESSION['profile_success'])
                      ? htmlspecialchars($_SESSION['profile_success'])
                      : 'Profile updated successfully!';
                  unset($_SESSION['profile_success']);
                ?>
              </div>
              <div class="error-toast" id="toast-error"
                   style="<?php echo isset($_SESSION['profile_error']) ? 'display:block;' : ''; ?>">
                <?php
                  echo isset($_SESSION['profile_error'])
                      ? htmlspecialchars($_SESSION['profile_error'])
                      : 'New passwords do not match!';
                  unset($_SESSION['profile_error']);
                ?>
              </div>
            </form>
            
          </div>
        </div>
        
      </div>
    </div>
  </div>

  <script>
    function hideToasts() {
        document.getElementById('toast-success').style.display = 'none';
        document.getElementById('toast-error').style.display = 'none';
    }

    function validateProfile(e) {
      hideToasts();

      const np = document.getElementById('newpass').value;
      const cp = document.getElementById('confirmpass').value;
      const errorToast = document.getElementById('toast-error');

      // If a new password was typed, it must match the confirmation before we submit
      if (np && np !== cp) {
        e.preventDefault();
        errorToast.textContent = 'New passwords do not match!';
        errorToast.style.display = 'block';
        return false;
      }

      // All good — let the form POST to update_profile.php for the real save
      return true;
    }
  </script>

</body>
</html>