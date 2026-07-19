<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: index.php?page=home"); exit(); }
require_once 'db.php'; 

// Safe check in case db.php failed to connect during dev
if(isset($conn)) {
    $user_id = $_SESSION['user_id'];
    $tot_comments = $conn->query("SELECT COUNT(*) as c FROM comments WHERE user_id = $user_id")->fetch_assoc()['c'];
} else {
    $tot_comments = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Comments — CMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <style>
    /* Consistently using home.php and dashboard CSS variables */
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
    
    /* ── Main Dashboard Area ── */
    .content { padding: 32px; flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; }
    
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
    .page-header h2 { font-size: 24px; font-weight: 800; color: var(--text); }
    
    /* ── Tables Panel ── */
    .panel { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; }
    
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { padding: 16px 24px; text-align: left; font-size: 14px; border-bottom: 1px solid var(--border); }
    th { background: #f8fafc; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: var(--bg); }
    
    /* Column widths */
    th:nth-child(1) { width: 45%; }
    th:nth-child(2) { width: 25%; }
    th:nth-child(3) { width: 15%; }
    th:nth-child(4) { width: 15%; text-align: center; }

    .comment-text {
      color: var(--text); font-size: 14px; line-height: 1.5;
    }
    
    .post-link { color: var(--primary); text-decoration: none; font-weight: 500; font-size: 13px; }
    .post-link:hover { text-decoration: underline; }

    .delete-link { display: inline-flex; align-items: center; justify-content: center; gap: 4px; color: var(--red); text-decoration: none; font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 6px; transition: background .2s; }
    .delete-link:hover { background: #fef2f2; }
    
    @media(max-width: 1024px) { 
      .sidebar { display: none; } 
      .page { margin-left: 0; width: 100%; } 
      .user-profile { border-left: none; padding-left: 0; }
      table { display: block; overflow-x: auto; white-space: nowrap; }
    }
  </style>
</head>
<body>

  <aside class="sidebar">
    <a href="index.php?page=home" class="brand">
      <div class="logo-icon">C</div>
      <div>
        <div class="logo-text">CM<span>S</span></div>
        <small>User Panel</small>
      </div>
    </a>
    <nav>
      <a href="index.php?page=create_post"><span class="icon">✍️</span> Write News</a>
      <a href="index.php?page=my_posts"><span class="icon">📝</span> My Posts</a>
      <a href="index.php?page=my_comments" class="active"><span class="icon">💬</span> My Comments</a>
      <a href="index.php?page=detail"><span class="icon">👤</span> My Profile</a>
      <a href="index.php?page=logout" class="logout-btn"><span class="icon">🚪</span> Logout</a>
    </nav>
  </aside>

  <div class="page">
    <div class="topbar">
      <span class="page-title">My Comments</span>
      <div class="right">
        <a href="index.php?page=home" class="visit-link">🌐 View Live Site</a>
        <div class="user-profile">
          <div class="user-info">
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong>
            <span><?php echo htmlspecialchars($_SESSION['role'] ?? 'Role'); ?></span>
          </div>
          <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="page-header">
        <h2>My Comment History (<?php echo $tot_comments; ?>)</h2>
      </div>

      <div class="panel">
        <table>
          <thead>
            <tr>
              <th>Comment</th>
              <th>Posted On Article</th>
              <th>Date</th>
              <th style="text-align:center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if(isset($conn)) {
                // Security: Fetch comments ONLY by this user, join with posts to get the article title
                $c_sql = "SELECT c.id, c.comment_text, c.created_at, p.title, p.id as post_id 
                          FROM comments c JOIN posts p ON c.post_id = p.id 
                          WHERE c.user_id = $user_id ORDER BY c.created_at DESC";
                $c_res = $conn->query($c_sql);
                if($c_res && $c_res->num_rows > 0):
                    while($c = $c_res->fetch_assoc()):
            ?>
            <tr>
              <td>
                <div class="comment-text"><?php echo nl2br(htmlspecialchars($c['comment_text'])); ?></div>
              </td>
              <td>
                <a href="index.php?page=single_post&id=<?php echo $c['post_id']; ?>" target="_blank" class="post-link">
                  <?php echo substr(htmlspecialchars($c['title']), 0, 40) . (strlen($c['title']) > 40 ? '...' : ''); ?>
                </a>
              </td>
              <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
              <td style="text-align:center">
                <a href="index.php?page=user_content_action&action=delete_comment&id=<?php echo $c['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to permanently delete this comment?');">
                  🗑 Delete
                </a>
              </td>
            </tr>
            <?php 
                    endwhile; 
                else: 
            ?>
                <tr><td colspan="4" style="text-align:center; padding: 40px; color: var(--muted);">You haven't made any comments yet.</td></tr>
            <?php 
                endif; 
            } else {
                echo "<tr><td colspan='4' style='text-align:center; padding: 40px; color: var(--muted);'>Database connection pending.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>