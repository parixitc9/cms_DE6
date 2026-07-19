<?php
// subscribers.php — Admin: view / export / delete newsletter subscribers.
// Reached via: index.php?page=subscribers
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin', 'superadmin'])) {
    header("Location: index.php?page=home"); exit();
}
if (!isset($conn)) { require_once 'db.php'; }

// Make sure the table exists so this page never fatals if setup wasn't run.
$has_table = false;
$t = $conn->query("SHOW TABLES LIKE 'subscribers'");
$has_table = ($t && $t->num_rows > 0);

/* ---- CSV export (must run BEFORE any HTML is sent) ---- */
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $has_table) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Email', 'Status', 'Subscribed On']);
    $res = $conn->query("SELECT id, email, status, created_at FROM subscribers ORDER BY created_at DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [$row['id'], $row['email'], $row['status'], $row['created_at']]);
        }
    }
    fclose($out);
    exit();
}

// Counts for the header
$total = 0;
if ($has_table) {
    $r = $conn->query("SELECT COUNT(*) c FROM subscribers");
    $total = $r ? (int) $r->fetch_assoc()['c'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Subscribers — CMS Admin</title>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --primary:#2563eb; --primary-dark:#1d4ed8; --primary-light:#eff6ff; --accent:#f59e0b;
      --dark:#0f172a; --dark2:#1e293b; --text:#1e293b; --muted:#64748b; --border:#e2e8f0;
      --bg:#f8fafc; --white:#fff; --green:#10b981; --red:#ef4444; --radius:12px;
      --shadow:0 4px 24px rgba(0,0,0,0.08); --shadow-sm:0 1px 6px rgba(0,0,0,0.06); --sidebar-w:260px;
    }
    body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

    /* ── Sidebar ── */
    .sidebar { width:var(--sidebar-w); background:var(--dark); color:#94a3b8; display:flex; flex-direction:column; flex-shrink:0; min-height:100vh; position:fixed; top:0; left:0; bottom:0; z-index:100; box-shadow:2px 0 20px rgba(0,0,0,0.2); }
    .sidebar .brand { padding:20px 24px; display:flex; align-items:center; gap:10px; border-bottom:1px solid rgba(255,255,255,0.05); text-decoration:none; height:68px; }
    .logo-icon { width:32px; height:32px; background:var(--primary); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; color:var(--white); }
    .logo-text { font-family:'Playfair Display',serif; font-size:20px; font-weight:700; color:var(--white); letter-spacing:-0.5px; }
    .logo-text span { color:var(--accent); }
    .brand small { display:block; font-family:'Inter',sans-serif; font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--muted); margin-top:2px; }
    .sidebar nav { padding:24px 12px; flex:1; display:flex; flex-direction:column; gap:4px; }
    .sidebar nav a { display:flex; align-items:center; gap:12px; color:#94a3b8; text-decoration:none; padding:12px 16px; border-radius:8px; font-size:14px; font-weight:500; transition:all .2s; }
    .sidebar nav a:hover { background:rgba(255,255,255,0.05); color:var(--white); }
    .sidebar nav a.active { background:var(--primary); color:var(--white); font-weight:600; box-shadow:0 4px 12px rgba(37,99,235,0.3); }
    .sidebar nav a.logout-btn { color:var(--red); margin-top:auto; }
    .sidebar nav a.logout-btn:hover { background:#fef2f2; color:#dc2626; }

    /* ── Page ── */
    .page { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; width:calc(100% - var(--sidebar-w)); }
    .topbar { background:var(--white); border-bottom:1px solid var(--border); padding:0 32px; height:68px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; }
    .topbar .page-title { font-family:'Playfair Display',serif; font-size:20px; font-weight:700; color:var(--text); }
    .topbar .right { display:flex; align-items:center; gap:24px; }
    .topbar .visit-link { color:var(--primary); text-decoration:none; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
    .user-profile { display:flex; align-items:center; gap:12px; padding-left:24px; border-left:1px solid var(--border); }
    .topbar .user-info { font-size:12px; color:var(--muted); text-align:right; }
    .topbar .user-info strong { color:var(--text); display:block; font-size:14px; font-weight:600; }
    .avatar { width:40px; height:40px; background:var(--primary-light); color:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; }

    .content { padding:32px; flex:1; max-width:1400px; margin:0 auto; width:100%; }
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; gap:16px; flex-wrap:wrap; }
    .page-header h2 { font-size:24px; font-weight:800; color:var(--text); }
    .header-actions { display:flex; gap:12px; }
    .btn-export { display:inline-flex; align-items:center; gap:8px; background:var(--white); color:var(--text); border:1px solid var(--border); text-decoration:none; font-size:13px; font-weight:600; padding:9px 16px; border-radius:8px; transition:all .2s; }
    .btn-export:hover { border-color:var(--primary); color:var(--primary); }

    /* Stat strip */
    .stat-strip { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
    .mini-stat { background:var(--white); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); padding:18px 24px; min-width:180px; }
    .mini-stat .num { font-family:'Playfair Display',serif; font-size:28px; font-weight:800; color:var(--text); line-height:1; }
    .mini-stat .lbl { font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; margin-top:6px; }

    .panel { background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow-sm); border:1px solid var(--border); overflow:hidden; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:16px 24px; text-align:left; font-size:14px; border-bottom:1px solid var(--border); }
    th { background:#f8fafc; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:var(--muted); }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:var(--bg); }
    .email-cell { font-weight:600; color:var(--text); }
    .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; text-transform:capitalize; }
    .badge.subscribed { background:#ecfdf5; color:#059669; }
    .badge.unsubscribed { background:#fef2f2; color:#dc2626; }
    .delete-link { display:inline-flex; align-items:center; gap:4px; color:var(--red); text-decoration:none; font-size:13px; font-weight:600; padding:6px 12px; border-radius:6px; transition:background .2s; }
    .delete-link:hover { background:#fef2f2; }
    .empty { text-align:center; padding:56px 24px; color:var(--muted); }
    .empty strong { display:block; font-size:16px; color:var(--text); margin-bottom:6px; font-weight:700; }

    @media(max-width:1024px) {
      .sidebar { display:none; }
      .page { margin-left:0; width:100%; }
      .user-profile { border-left:none; padding-left:0; }
      table { display:block; overflow-x:auto; white-space:nowrap; }
    }
  </style>
  <link rel="stylesheet" href="assets/beautify.css?v=3">
  <script src="assets/beautify.js" defer></script>
</head>
<body>

  <aside class="sidebar">
    <a href="index.php?page=admin" class="brand">
      <div class="logo-icon">C</div>
      <div><div class="logo-text">CM<span>S</span></div><small>Admin Panel</small></div>
    </a>
    <nav>
      <a href="index.php?page=admin"><span class="icon">📊</span> Dashboard</a>
      <a href="index.php?page=post"><span class="icon">📝</span> Manage Posts</a>
      <a href="index.php?page=comments"><span class="icon">💬</span> Manage Comments</a>
      <a href="index.php?page=list"><span class="icon">👥</span> Manage Users</a>
      <a href="index.php?page=subscribers" class="active"><span class="icon">📧</span> Subscribers</a>
      <a href="index.php?page=detail"><span class="icon">👤</span> My Profile</a>
      <a href="index.php?page=logout" class="logout-btn"><span class="icon">🚪</span> Logout</a>
    </nav>
  </aside>

  <div class="page">
    <div class="topbar">
      <span class="page-title">Subscribers</span>
      <div class="right">
        <a href="index.php?page=home" class="visit-link">🌐 Visit Site</a>
        <div class="user-profile">
          <div class="user-info">
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
            <span style="text-transform:capitalize;"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Role'); ?></span>
          </div>
          <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="page-header">
        <h2>Newsletter Subscribers</h2>
        <div class="header-actions">
          <?php if ($has_table && $total > 0): ?>
            <a href="index.php?page=subscribers&export=csv" class="btn-export"><span class="icon">📥</span> Export CSV</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!$has_table): ?>
        <div class="panel"><div class="empty">
          <strong>The subscribers table doesn&rsquo;t exist yet.</strong>
          Run <code>setup_subscribers.php</code> once to create it, then subscribers will appear here.
        </div></div>
      <?php else: ?>

        <div class="stat-strip">
          <div class="mini-stat"><div class="num"><?php echo $total; ?></div><div class="lbl">Total Subscribers</div></div>
          <?php
            $today = 0; $week = 0;
            $rt = $conn->query("SELECT COUNT(*) c FROM subscribers WHERE DATE(created_at) = CURDATE()");
            if ($rt) { $today = (int) $rt->fetch_assoc()['c']; }
            $rw = $conn->query("SELECT COUNT(*) c FROM subscribers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            if ($rw) { $week = (int) $rw->fetch_assoc()['c']; }
          ?>
          <div class="mini-stat"><div class="num"><?php echo $today; ?></div><div class="lbl">Joined Today</div></div>
          <div class="mini-stat"><div class="num"><?php echo $week; ?></div><div class="lbl">Last 7 Days</div></div>
        </div>

        <div class="panel">
          <table>
            <thead>
              <tr><th style="width:8%;">ID</th><th style="width:48%;">Email</th><th style="width:16%;">Status</th><th style="width:18%;">Subscribed On</th><th style="width:10%;">Action</th></tr>
            </thead>
            <tbody>
              <?php
              $res = $conn->query("SELECT id, email, status, created_at FROM subscribers ORDER BY created_at DESC");
              if ($res && $res->num_rows > 0):
                  while ($s = $res->fetch_assoc()):
              ?>
                <tr>
                  <td>#<?php echo (int) $s['id']; ?></td>
                  <td class="email-cell"><?php echo htmlspecialchars($s['email']); ?></td>
                  <td><span class="badge <?php echo $s['status'] === 'unsubscribed' ? 'unsubscribed' : 'subscribed'; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                  <td><?php echo date('M d, Y • g:i a', strtotime($s['created_at'])); ?></td>
                  <td>
                    <a href="index.php?page=admin_action&action=delete_subscriber&id=<?php echo (int) $s['id']; ?>"
                       class="delete-link"
                       onclick="return confirm('Remove this subscriber? This cannot be undone.');">🗑 Delete</a>
                  </td>
                </tr>
              <?php
                  endwhile;
              else:
              ?>
                <tr><td colspan="5"><div class="empty"><strong>No subscribers yet.</strong>When people sign up through the newsletter box on the home page, they&rsquo;ll show up here.</div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      <?php endif; ?>
    </div>
  </div>
</body>
</html>
