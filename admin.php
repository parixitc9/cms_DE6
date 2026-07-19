<?php
// Security check
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin', 'superadmin'])) {
    die("Access Denied.");
}

// Fetch live statistics
$tot_posts = $conn->query("SELECT COUNT(*) as c FROM posts")->fetch_assoc()['c'];
$tot_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$pub_posts = $conn->query("SELECT COUNT(*) as c FROM posts WHERE status='published'")->fetch_assoc()['c'];
$pen_posts = $conn->query("SELECT COUNT(*) as c FROM posts WHERE status='pending'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — CMS Admin</title>
  <!-- Fonts are self-hosted via assets/beautify.css (works offline) -->
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <style>
    /* Consistently using home.php CSS variables */
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
    
    /* ── Sidebar (Matching home.php dark theme) ── */
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
    .avatar { 
      width: 40px; height: 40px; 
      background: var(--primary-light); color: var(--primary); 
      border-radius: 50%; display: flex; align-items: center; justify-content: center; 
      font-size: 16px; font-weight: 700; 
    }
    
    /* ── Main Dashboard Area ── */
    .content { padding: 32px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; }
    
    .welcome-banner { 
      background: linear-gradient(135deg, var(--dark) 0%, var(--dark2) 60%, #1e3a8a 100%);
      border-radius: var(--radius); padding: 32px 40px; color: var(--white); 
      margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between;
      box-shadow: var(--shadow); position: relative; overflow: hidden;
    }
    .welcome-banner::before {
      content: ''; position: absolute; inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .welcome-content { position: relative; z-index: 1; }
    .welcome-banner h2 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
    .welcome-banner p { font-size: 15px; color: #94a3b8; }
    .welcome-banner .emoji { font-size: 54px; position: relative; z-index: 1; }
    
    /* ── Stat Cards ── */
    .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px; }
    .stat-card { 
      background: var(--white); border-radius: var(--radius); 
      box-shadow: var(--shadow-sm); border: 1px solid var(--border);
      padding: 24px; display: flex; align-items: center; gap: 20px;
      transition: transform .2s, box-shadow .2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
    .stat-icon { 
      width: 56px; height: 56px; border-radius: 12px; 
      display: flex; align-items: center; justify-content: center; 
      font-size: 24px; flex-shrink: 0; 
    }
    .stat-icon.red { background: #fef2f2; color: var(--red); } 
    .stat-icon.blue { background: var(--primary-light); color: var(--primary); } 
    .stat-icon.green { background: #ecfdf5; color: var(--green); } 
    .stat-icon.orange { background: #fffbeb; color: var(--accent); }
    
    .stat-body h3 { font-size: 26px; font-weight: 800; color: var(--text); line-height: 1; margin-bottom: 4px; }
    .stat-body p { font-size: 13px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-body a { font-size: 13px; font-weight: 500; color: var(--primary); text-decoration: none; margin-top: 10px; display: inline-block; }
    .stat-body a:hover { text-decoration: underline; }
    
    /* ── Tables Panel ── */
    .panel { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; }
    .panel-head { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .panel-head h4 { font-size: 16px; font-weight: 700; color: var(--text); }
    .panel-head a { font-size: 13px; font-weight: 600; color: var(--primary); text-decoration: none; background: var(--primary-light); padding: 6px 12px; border-radius: 6px; transition: background .2s; }
    .panel-head a:hover { background: #e0e7ff; }
    
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 16px 24px; text-align: left; font-size: 14px; border-bottom: 1px solid var(--border); }
    th { background: #f8fafc; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: var(--bg); }
    
    .post-title-link { color: var(--text); text-decoration: none; font-weight: 500; }
    .post-title-link:hover { color: var(--primary); }
    
    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
    .badge.published { background: #ecfdf5; color: #059669; }
    .badge.pending { background: #fffbeb; color: #d97706; }
    
    @media(max-width: 1024px) { 
      .sidebar { display: none; } 
      .page { margin-left: 0; width: 100%; } 
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
        <small>Admin Panel</small>
      </div>
    </a>
    <nav>
      <a href="index.php?page=admin" class="active"><span class="icon">📊</span> Dashboard</a>
      <a href="index.php?page=post"><span class="icon">📝</span> Manage Posts</a>
      <a href="index.php?page=comments"><span class="icon">💬</span> Manage Comments</a>
      <a href="index.php?page=list"><span class="icon">👥</span> Manage Users</a>
      <a href="index.php?page=subscribers"><span class="icon">📧</span> Subscribers</a>
      <a href="index.php?page=detail"><span class="icon">👤</span> My Profile</a>
      <a href="index.php?page=logout" class="logout-btn"><span class="icon">🚪</span> Logout</a>
    </nav>
    <div class="sidebar-footer">v1.0.0 &copy; 2024 CMS</div>
  </aside>

  <div class="page">
    <div class="topbar">
      <span class="page-title">Dashboard</span>
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
      <div class="welcome-banner">
        <div class="welcome-content">
          <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h2>
          <p>Here's what's happening with your CMS site today.</p>
        </div>
        <div class="emoji">🚀</div>
      </div>

      <div class="cards">
        <div class="stat-card">
          <div class="stat-icon red">📝</div>
          <div class="stat-body">
            <h3><?php echo $tot_posts; ?></h3>
            <p>Total Posts</p>
            <a href="index.php?page=post">View All Posts →</a>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">👥</div>
          <div class="stat-body">
            <h3><?php echo $tot_users; ?></h3>
            <p>Registered Users</p>
            <a href="index.php?page=list">View All Users →</a>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">✅</div>
          <div class="stat-body">
            <h3><?php echo $pub_posts; ?></h3>
            <p>Published Posts</p>
            <a href="index.php?page=post">Manage Posts →</a>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">🕐</div>
          <div class="stat-body">
            <h3><?php echo $pen_posts; ?></h3>
            <p>Pending Drafts</p>
            <a href="index.php?page=post">Review Drafts →</a>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h4>Recent Posts</h4>
          <a href="index.php?page=post">View All →</a>
        </div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Author</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Adjusted query to handle potential missing variables gracefully if accessed directly during dev
            if(isset($conn)){
                $recent = $conn->query("SELECT p.id, p.title, p.status, p.created_at, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
                if($recent && $recent->num_rows > 0) {
                    while($r = $recent->fetch_assoc()):
            ?>
            <tr>
              <td>#<?php echo $r['id']; ?></td>
              <td><a href="index.php?page=single_post&id=<?php echo $r['id']; ?>" class="post-title-link"><?php echo htmlspecialchars($r['title']); ?></a></td>
              <td><?php echo htmlspecialchars($r['username']); ?></td>
              <td>
                <span class="badge <?php echo $r['status'] == 'published' ? 'published' : 'pending'; ?>">
                    <?php echo htmlspecialchars($r['status']); ?>
                </span>
              </td>
              <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
            </tr>
            <?php 
                    endwhile; 
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding: 30px;'>No posts found.</td></tr>";
                }
            } else {
                 echo "<tr><td colspan='5' style='text-align:center; padding: 30px;'>Database connection pending.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</body>
</html>