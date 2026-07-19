<?php
// We no longer need to connect to the database or start the session here, 
// because our Master Router (index.php) handles that before loading this file!

$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Using the $conn provided by index.php
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            
            // Login success! Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role through the Master Router
            if(in_array(strtolower($user['role']), ['admin', 'superadmin'])) {
                header("Location: index.php?page=admin");
            } else {
                header("Location: index.php?page=home");
            }
            exit();
            
        } else { $error_msg = "Invalid username or password!"; }
    } else { $error_msg = "Invalid username or password!"; }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMS — Your Daily News Hub</title>
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
      --red: #ef4444;
      --green: #10b981;
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0,0,0,0.08);
      --shadow-sm: 0 1px 6px rgba(0,0,0,0.06);
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* ── Navbar ── */
    .navbar { background: var(--dark); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 20px rgba(0,0,0,0.3); }
    .navbar-inner { max-width: 1240px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 68px; }
    .navbar .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--white); }
    .logo-icon { width: 36px; height: 36px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; }
    .logo-text { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
    .logo-text span { color: var(--accent); }
    .nav-links { display: flex; align-items: center; gap: 6px; }
    .nav-links a { color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; padding: 8px 14px; border-radius: 8px; transition: all .2s; }
    .nav-links a:hover { background: rgba(255,255,255,0.08); color: var(--white); }
    .nav-links a.register-btn { background: var(--primary); color: var(--white); padding: 8px 18px; font-weight: 600; }
    .nav-links a.register-btn:hover { background: var(--primary-dark); }
    
    .user-profile-nav { display: flex; align-items: center; gap: 10px; margin-left: 12px; padding-left: 16px; border-left: 1px solid rgba(255,255,255,0.1); }
    .avatar-sm { width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--white); text-decoration: none; }

    /* ── Hero Banner ── */
    .hero { background: linear-gradient(135deg, var(--dark) 0%, var(--dark2) 60%, #1e3a8a 100%); padding: 64px 24px 48px; text-align: center; color: var(--white); position: relative; overflow: hidden; }
    .hero::before { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
    .hero-content { position: relative; max-width: 680px; margin: 0 auto; }
    .hero-tag { display: inline-flex; align-items: center; gap: 6px; background: rgba(245,158,11,0.15); color: var(--accent); border: 1px solid rgba(245,158,11,0.3); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; padding: 5px 14px; border-radius: 20px; margin-bottom: 20px; }
    .hero h1 { font-family: 'Playfair Display', serif; font-size: clamp(32px, 5vw, 54px); font-weight: 800; line-height: 1.15; margin-bottom: 16px; }
    .hero h1 span { color: var(--accent); }
    .hero p { font-size: 16px; color: #94a3b8; line-height: 1.7; max-width: 500px; margin: 0 auto 28px; }
    .hero-stats { display: flex; justify-content: center; gap: 40px; }
    .hero-stat strong { display: block; font-size: 24px; font-weight: 700; color: var(--white); }
    .hero-stat span { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

    /* ── Category Pills ── */
    .categories { background: var(--white); border-bottom: 1px solid var(--border); padding: 14px 24px; }
    .categories-inner { max-width: 1240px; margin: 0 auto; display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; }
    .categories-inner::-webkit-scrollbar { display: none; }
    .cat-pill { white-space: nowrap; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid var(--border); color: var(--muted); background: var(--white); transition: all .2s; text-decoration: none; }
    .cat-pill:hover, .cat-pill.active { background: var(--primary); color: var(--white); border-color: var(--primary); }

    /* ── Main Layout ── */
    .main { max-width: 1240px; margin: 36px auto; padding: 0 24px; display: grid; grid-template-columns: 1fr 320px; gap: 32px; }

    /* ── Post Card ── */
    .post-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; border: 1px solid var(--border); transition: all .25s; margin-bottom: 24px; }
    .post-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
    .post-card-image { position: relative; height: 220px; overflow: hidden; background: #e2e8f0; }
    .post-card-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .post-card:hover .post-card-image img { transform: scale(1.03); }
    .post-cat-badge { position: absolute; top: 14px; left: 14px; background: var(--primary); color: var(--white); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 12px; border-radius: 20px; }
    .post-card-body { padding: 22px 24px 20px; }
    .post-meta { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
    .post-meta .author { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--muted); font-weight: 500; }
    .author-avatar { width: 24px; height: 24px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; color: var(--white); }
    .post-meta .dot { width: 3px; height: 3px; background: var(--border); border-radius: 50%; }
    .post-meta time { font-size: 12px; color: var(--muted); }
    .post-card h2 { font-size: 18px; font-weight: 700; line-height: 1.45; color: var(--text); margin-bottom: 10px; }
    .post-card h2 a { text-decoration: none; color: inherit; }
    .post-card h2 a:hover { color: var(--primary); }
    .post-card p { font-size: 14px; color: var(--muted); line-height: 1.7; margin-bottom: 18px; }
    .post-card-footer { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--border); }
    .read-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--primary); color: var(--white); text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 18px; border-radius: 8px; transition: background .2s; }
    .read-btn:hover { background: var(--primary-dark); }
    .read-time { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 4px; }

    /* ── Featured (first post big) ── */
    .post-card.featured .post-card-image { height: 300px; }
    .post-card.featured h2 { font-size: 22px; }

    /* ── Sidebar ── */
    .sidebar { display: flex; flex-direction: column; gap: 24px; }
    .sidebar-widget { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; }
    .widget-head { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
    .widget-head h3 { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text); }
    .widget-head .dot { width: 6px; height: 6px; background: var(--primary); border-radius: 50%; }

    /* Login Widget */
    .login-form { padding: 20px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .form-group input { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text); background: var(--bg); transition: border-color .2s, box-shadow .2s; }
    .form-group input:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .login-btn { width: 100%; padding: 11px; background: var(--primary); color: var(--white); border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background .2s; font-family: inherit; }
    .login-btn:hover { background: var(--primary-dark); }
    .login-links { display: flex; justify-content: center; gap: 16px; margin-top: 14px; }
    .login-links a { font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 500; }
    .login-links a:hover { text-decoration: underline; }
    .error-msg { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 14px; font-size: 13px; font-weight: 500; margin-bottom: 14px; display: flex; align-items: center; gap: 6px; }

    /* Trending Widget */
    .trending-list { padding: 8px 0; }
    .trending-item { display: flex; align-items: flex-start; gap: 14px; padding: 12px 20px; border-bottom: 1px solid var(--border); transition: background .15s; }
    .trending-item:last-child { border-bottom: none; }
    .trending-item:hover { background: var(--bg); }
    .trending-num { font-size: 20px; font-weight: 800; color: var(--border); min-width: 24px; line-height: 1; margin-top: 2px; }
    .trending-item h4 { font-size: 13px; font-weight: 600; line-height: 1.5; color: var(--text); margin-bottom: 3px; }
    .trending-item h4 a { text-decoration: none; color: inherit; }
    .trending-item h4 a:hover { color: var(--primary); }
    .trending-item span { font-size: 11px; color: var(--muted); }

    /* Newsletter Widget */
    .newsletter { padding: 20px; }
    .newsletter p { font-size: 13px; color: var(--muted); line-height: 1.6; margin-bottom: 14px; }
    .newsletter input[type="email"] { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; margin-bottom: 10px; }
    .newsletter input[type="email"]:focus { outline: none; border-color: var(--primary); }
    .newsletter-btn { width: 100%; padding: 10px; background: var(--accent); color: var(--dark); border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: opacity .2s; }
    .newsletter-btn:hover { opacity: .9; }

    /* ── Footer (copyright only) ── */
    footer { background: var(--dark); color: #94a3b8; margin-top: 60px; }
    .footer-bottom { padding: 20px 24px; text-align: center; max-width: 1240px; margin: 0 auto; font-size: 13px; }

    @media(max-width: 960px) { .main { grid-template-columns: 1fr; } }
    @media(max-width: 640px) { .hero-stats { gap: 24px; } .nav-links a:not(.register-btn):not(.user-profile-nav) { display: none; } }
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
        <a href="index.php?page=about">About</a>

        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if(in_array(strtolower($_SESSION['role']), ['admin', 'superadmin'])): ?>
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

  <section class="hero">
    <div class="hero-content">
      <div class="hero-tag">&#9889; Breaking News &amp; Trending Stories</div>
      <h1>Stay Ahead with<br><span>Today's News</span></h1>
      <p>Curated stories from technology, entertainment, business, and world affairs — all in one place.</p>
      <div class="hero-stats">
        <?php
        // Live counts pulled straight from the database (no hardcoded numbers)
        $stat_articles = 0; $stat_members = 0; $stat_categories = 0;
        if (isset($conn)) {
            $stat_articles   = (int) $conn->query("SELECT COUNT(*) c FROM posts WHERE status='published'")->fetch_assoc()['c'];
            $stat_members    = (int) $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
            $stat_categories = (int) $conn->query("SELECT COUNT(DISTINCT category) c FROM posts WHERE status='published' AND category <> ''")->fetch_assoc()['c'];
        }
        ?>
        <div class="hero-stat"><strong><?php echo $stat_articles; ?></strong><span>Articles</span></div>
        <div class="hero-stat"><strong><?php echo $stat_members; ?></strong><span>Members</span></div>
        <div class="hero-stat"><strong><?php echo $stat_categories; ?></strong><span>Categories</span></div>
      </div>
    </div>
  </section>

  <div class="categories">
    <div class="categories-inner">
      <?php
      // Which category is currently selected (from the URL ?category=...), if any
      $selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
      ?>
      <a href="index.php?page=home" class="cat-pill <?php echo $selected_category === '' ? 'active' : ''; ?>">All</a>
      <?php
      if (isset($conn)) {
          // Build the category pills from the categories that actually have published posts,
          // so the filter always matches real data (no empty or missing categories).
          $cat_res = $conn->query("SELECT category, COUNT(*) AS c FROM posts WHERE status = 'published' AND category <> '' GROUP BY category ORDER BY c DESC");
          if ($cat_res) {
              while ($cat = $cat_res->fetch_assoc()) {
                  $cat_name = $cat['category'];
                  $active = ($cat_name === $selected_category) ? 'active' : '';
                  echo '<a href="index.php?page=home&category=' . urlencode($cat_name) . '" class="cat-pill ' . $active . '">' . htmlspecialchars($cat_name) . '</a>';
              }
          }
      }
      ?>
    </div>
  </div>

  <div class="main">
    <div class="posts-feed">

      <?php
      if (isset($conn)) {
          // Fetch the latest 6 published posts, optionally filtered by the selected category.
          // A prepared statement keeps the URL-supplied category safe from SQL injection.
          if ($selected_category !== '') {
              $stmt = $conn->prepare("SELECT p.*, u.username, u.firstname FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = 'published' AND p.category = ? ORDER BY p.created_at DESC LIMIT 6");
              $stmt->bind_param("s", $selected_category);
              $stmt->execute();
              $post_res = $stmt->get_result();
          } else {
              $post_res = $conn->query("SELECT p.*, u.username, u.firstname FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = 'published' ORDER BY p.created_at DESC LIMIT 6");
          }

          if ($post_res && $post_res->num_rows > 0) {
              $is_first = true;
              while ($p = $post_res->fetch_assoc()) {
                  // Make the first post featured (larger)
                  $featured_class = $is_first ? 'featured' : '';
                  $is_first = false;
                  
                  // Use a placeholder image if none was uploaded
                  $img = !empty($p['image_path']) ? htmlspecialchars($p['image_path']) : 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=800&q=80';
                  ?>
                  <div class="post-card <?php echo $featured_class; ?>">
                    <div class="post-card-image">
                      <img src="<?php echo $img; ?>" alt="Cover">
                      <span class="post-cat-badge"><?php echo htmlspecialchars($p['category']); ?></span>
                    </div>
                    <div class="post-card-body">
                      <div class="post-meta">
                        <div class="author">
                          <div class="author-avatar"><?php echo strtoupper(substr($p['firstname'] ?? $p['username'], 0, 1)); ?></div>
                          by <?php echo htmlspecialchars($p['firstname'] ?? $p['username']); ?>
                        </div>
                        <div class="dot"></div>
                        <time><?php echo date('F j, Y', strtotime($p['created_at'])); ?></time>
                      </div>
                      <h2><a href="index.php?page=single_post&id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></a></h2>
                      <p><?php echo substr(strip_tags($p['content']), 0, 150) . '...'; ?></p>
                      <div class="post-card-footer">
                        <a href="index.php?page=single_post&id=<?php echo $p['id']; ?>" class="read-btn">Read Full Story &#8594;</a>
                      </div>
                    </div>
                  </div>
                  <?php
              }
          } else {
              if ($selected_category !== '') {
                  $empty_msg = 'No articles found in the "' . htmlspecialchars($selected_category) . '" category. '
                             . "<a href='index.php?page=home' style='color:var(--primary); font-weight:600;'>View all articles</a>";
              } else {
                  $empty_msg = "No published articles found. Be the first to write one!";
              }
              echo "<p style='padding: 40px; text-align:center; color: var(--muted); background: var(--white); border-radius: var(--radius); border: 1px solid var(--border);'>$empty_msg</p>";
          }
      }
      ?>

    </div>

    <aside class="sidebar">

      <div class="sidebar-widget">
        <div class="widget-head">
          <span class="dot"></span>
          <h3><?php echo isset($_SESSION['user_id']) ? 'Welcome Back' : 'Member Login'; ?></h3>
        </div>
        <div class="login-form">
          
          <?php if(isset($_SESSION['user_id'])): ?>
              <div style="text-align: center; padding: 10px 0;">
                <div style="width: 64px; height: 64px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 800; margin: 0 auto 12px;">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 4px; color: var(--text);">@<?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p style="font-size: 13px; color: var(--muted); text-transform: capitalize; margin-bottom: 20px;"><?php echo htmlspecialchars($_SESSION['role']); ?> Account</p>
                
                <?php if(in_array(strtolower($_SESSION['role']), ['admin', 'superadmin'])): ?>
                    <a href="index.php?page=admin" class="login-btn" style="display: block; text-align: center; text-decoration: none; margin-bottom: 12px;">Go to Admin Dashboard</a>
                <?php else: ?>
                    <a href="index.php?page=detail" class="login-btn" style="display: block; text-align: center; text-decoration: none; margin-bottom: 12px;">Go to My Profile</a>
                <?php endif; ?>
                
                <a href="index.php?page=logout" style="color: var(--red); font-size: 13px; font-weight: 600; text-decoration: none; transition: color 0.2s;">Logout</a>
              </div>
          <?php else: ?>
              <?php if (!empty($error_msg)): ?>
                <div class="error-msg">&#9888; <?php echo $error_msg; ?></div>
              <?php endif; ?>
              <form method="POST" action="index.php?page=home">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" name="username" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="login-btn">Sign In &#8594;</button>
              </form>
              <div class="login-links">
                <a href="index.php?page=register">Create Account</a>
                <span title="Please contact the site administrator to reset your password." style="color: var(--muted); cursor: default;">Forgot Password?</span>
              </div>
          <?php endif; ?>
          
        </div>
      </div>

      <div class="sidebar-widget">
        <div class="widget-head">
          <span class="dot"></span>
          <h3>Trending Now</h3>
        </div>
        <div class="trending-list">
          <?php
          if (isset($conn)) {
              // Fetch top 5 posts
              $trend_sql = "SELECT id, title, category FROM posts WHERE status = 'published' ORDER BY id DESC LIMIT 5";
              $trend_res = $conn->query($trend_sql);
              $count = 1;
              if ($trend_res && $trend_res->num_rows > 0) {
                  while ($t = $trend_res->fetch_assoc()) {
                      ?>
                      <div class="trending-item">
                        <span class="trending-num">0<?php echo $count; ?></span>
                        <div>
                          <h4><a href="index.php?page=single_post&id=<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?></a></h4>
                          <span><?php echo htmlspecialchars($t['category']); ?></span>
                        </div>
                      </div>
                      <?php
                      $count++;
                  }
              } else {
                  echo "<p style='padding: 15px; font-size:13px; color:var(--muted);'>No trending posts yet.</p>";
              }
          }
          ?>
        </div>
      </div>

      <div class="sidebar-widget" id="newsletter">
        <div class="widget-head">
          <span class="dot"></span>
          <h3>Newsletter</h3>
        </div>
        <div class="newsletter" id="newsletterBox">
          <?php
          // Show a one-time status banner after a sign-up attempt, then clear it.
          $ns = $_SESSION['newsletter_status'] ?? '';
          unset($_SESSION['newsletter_status']);
          $ne = htmlspecialchars($_SESSION['newsletter_email'] ?? '');
          unset($_SESSION['newsletter_email']);

          if ($ns === 'subscribed'): ?>
              <p style="color:var(--green); font-weight:600; margin:0;">&#10004; Thanks for subscribing! The latest news will arrive in your inbox.</p>
          <?php elseif ($ns === 'already'): ?>
              <p style="color:var(--accent-amber); font-weight:600; margin:0 0 6px;">You&rsquo;re already on the list &mdash; no need to sign up again.</p>
              <p style="font-size:12px; color:var(--muted); margin:0;">Thanks for reading CMS.</p>
          <?php else: ?>
              <p>Get the latest breaking news delivered directly to your inbox every morning.</p>
              <?php if ($ns === 'invalid'): ?>
                  <p style="color:var(--red); font-size:12px; font-weight:600; margin:-4px 0 10px;">&#9888; Please enter a valid email address.</p>
              <?php elseif ($ns === 'error'): ?>
                  <p style="color:var(--red); font-size:12px; font-weight:600; margin:-4px 0 10px;">&#9888; Something went wrong. Please try again.</p>
              <?php endif; ?>
              <form method="POST" action="index.php?page=subscribe">
                <input type="email" name="email" required placeholder="your@email.com" value="<?php echo $ne; ?>" autocomplete="email">
                <button type="submit" class="newsletter-btn">Subscribe Now &#8594;</button>
              </form>
          <?php endif; ?>
        </div>
      </div>

    </aside>
  </div>

  <footer>
    <div class="footer-bottom">
      <p>COPYRIGHT &copy; 2024 CMS &mdash; Built with passion for journalism. All rights reserved.</p>
    </div>
  </footer>


</body>
</html>