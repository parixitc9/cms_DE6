<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Database connection fallback (in case it's not already included by a master router)
if (!isset($conn)) {
    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "cms";
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;

if (isset($conn) && !$conn->connect_error) {
    // Fetch Post
    $post_sql = "SELECT p.*, u.firstname, u.lastname, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = $post_id AND p.status = 'published'";
    $post_result = $conn->query($post_sql);
    if ($post_result) {
        $post = $post_result->fetch_assoc();
    }
}

if(!$post) { 
    die('
    <!DOCTYPE html><html lang="en"><head><title>Post Not Found - CMS</title>
    <style>body{font-family:sans-serif; text-align:center; padding-top:100px; background:#f8fafc; color:#1e293b;}</style>  <link rel="stylesheet" href="assets/beautify.css?v=3">
  <script src="assets/beautify.js" defer></script>
</head>
    <body><h2>Post not found or pending approval.</h2><a href="index.php?page=home" style="color:#2563eb;">Return to Home</a></body></html>
    '); 
}

// Track current URL for redirecting back after login (Smart Redirect)
$_SESSION['redirect_after_login'] = "index.php?page=single_post&id=" . $post_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($post['title']); ?> — CMS</title>
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
      --text: #1e293b;
      --text-light: #334155;
      --muted: #64748b;
      --border: #e2e8f0;
      --bg: #f8fafc;
      --white: #ffffff;
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }

    /* ── Navbar ── */
    .navbar { background: var(--dark); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 20px rgba(0,0,0,0.3); }
    .navbar-inner { max-width: 1240px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 68px; }
    .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--white); }
    .logo-icon { width: 36px; height: 36px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; }
    .logo-text { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
    .logo-text span { color: var(--accent); }
    .nav-links { display: flex; align-items: center; gap: 6px; }
    .nav-links a { color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; padding: 8px 14px; border-radius: 8px; transition: all .2s; }
    .nav-links a:hover { background: rgba(255,255,255,0.08); color: var(--white); }
    .nav-links a.register-btn { background: var(--primary); color: var(--white); padding: 8px 18px; font-weight: 600; margin-left: 8px; }
    .nav-links a.register-btn:hover { background: var(--primary-dark); }
    
    .user-profile { display: flex; align-items: center; gap: 10px; margin-left: 12px; padding-left: 16px; border-left: 1px solid rgba(255,255,255,0.1); }
    .avatar-sm { width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--white); }

    /* ── Article Container ── */
    .article-wrapper { flex: 1; padding: 40px 24px 80px; }
    .article-container { max-width: 800px; margin: 0 auto; background: var(--white); border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
    
    .article-header { padding: 40px 40px 0; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 24px; transition: color 0.2s; }
    .back-link:hover { color: var(--primary-dark); }
    
    .article-category { display: inline-block; background: var(--bg); border: 1px solid var(--border); color: var(--primary); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; padding: 4px 12px; border-radius: 20px; margin-bottom: 16px; }
    
    .article-title { font-family: 'Playfair Display', serif; font-size: clamp(32px, 5vw, 44px); font-weight: 800; line-height: 1.2; color: var(--text); margin-bottom: 24px; }
    
    .article-meta { display: flex; align-items: center; gap: 16px; padding-bottom: 32px; border-bottom: 1px solid var(--border); }
    .author-avatar { width: 44px; height: 44px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; }
    .meta-info { display: flex; flex-direction: column; gap: 2px; }
    .meta-info strong { font-size: 15px; color: var(--text); font-weight: 600; }
    .meta-info span { font-size: 13px; color: var(--muted); }

    .article-image-container { width: 100%; background: var(--bg); border-bottom: 1px solid var(--border); }
    .article-image-container img { width: 100%; max-height: 500px; object-fit: cover; display: block; }
    
    .article-content { padding: 40px; font-size: 17px; line-height: 1.85; color: var(--text-light); }
    .article-content p { margin-bottom: 20px; }
    .article-content a { color: var(--primary); text-decoration: none; }
    .article-content a:hover { text-decoration: underline; }

    /* ── Comments Section ── */
    .comments-section { background: var(--bg); padding: 40px; border-top: 1px solid var(--border); }
    .comments-section h3 { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; margin-bottom: 24px; color: var(--text); }
    
    .comment-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; }
    .comment-card { background: var(--white); padding: 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
    .comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .comment-author { font-weight: 600; font-size: 14px; color: var(--text); display: flex; align-items: center; gap: 8px; }
    .comment-author .badge { background: var(--primary-light); color: var(--primary); font-size: 11px; padding: 2px 8px; border-radius: 10px; }
    .comment-date { font-size: 12px; color: var(--muted); }
    .comment-body { font-size: 14px; line-height: 1.6; color: var(--text-light); }
    
    .no-comments { text-align: center; color: var(--muted); padding: 20px 0; font-style: italic; font-size: 14px; }

    /* Add Comment Form */
    .comment-form-box { background: var(--white); padding: 24px; border-radius: 12px; border: 1px solid var(--border); }
    .comment-form-box h4 { font-size: 15px; font-weight: 600; margin-bottom: 12px; }
    .comment-form-box textarea { width: 100%; padding: 14px 16px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 100px; margin-bottom: 12px; transition: border-color 0.2s; background: var(--bg); }
    .comment-form-box textarea:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .btn-submit { display: inline-flex; background: var(--primary); color: var(--white); padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.2s; font-family: inherit; }
    .btn-submit:hover { background: var(--primary-dark); }
    
    .login-prompt { background: var(--white); padding: 24px; border-radius: 12px; border: 1px solid var(--border); text-align: center; }
    .login-prompt p { font-size: 15px; color: var(--muted); margin-bottom: 16px; }
    .btn-login-outline { display: inline-block; padding: 8px 20px; border: 1.5px solid var(--primary); color: var(--primary); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s; }
    .btn-login-outline:hover { background: var(--primary); color: var(--white); }

    /* ── Footer (copyright only) ── */
    footer { background: var(--dark); color: #94a3b8; margin-top: auto; }
    .footer-bottom { padding: 20px 24px; text-align: center; max-width: 1240px; margin: 0 auto; font-size: 13px; }

    @media(max-width: 768px) {
      .article-wrapper { padding: 20px 16px 60px; }
      .article-header { padding: 32px 24px 0; }
      .article-content { padding: 32px 24px; font-size: 16px; }
      .comments-section { padding: 32px 24px; }
    }
    @media(max-width: 640px) {
      .nav-links a:not(.user-profile, .register-btn) { display: none; }
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
        <a href="index.php?page=about">About</a>

        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if(in_array(strtolower($_SESSION['role']), ['admin', 'superadmin'])): ?>
                <a href="index.php?page=admin" class="register-btn">Dashboard</a>
            <?php else: ?>
                <a href="index.php?page=detail" class="register-btn">My Profile</a>
            <?php endif; ?>

            <div class="user-profile">
              <span style="font-size: 13px; font-weight: 500; color: var(--white);">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
              </span>
              <a href="index.php?page=detail" style="text-decoration:none;">
                <div class="avatar-sm"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
              </a>
            </div>
        <?php else: ?>
            <a href="index.php?page=login">Login</a>
            <a href="index.php?page=register" class="register-btn">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <main class="article-wrapper">
    <article class="article-container">
      
      <header class="article-header">
        <a href="index.php?page=home" class="back-link">&#8592; Back to Trending</a>
        
        <?php if(!empty($post['category'])): ?>
            <div class="article-category"><?php echo htmlspecialchars($post['category']); ?></div>
        <?php endif; ?>
        
        <h1 class="article-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        
        <div class="article-meta">
          <div class="author-avatar"><?php echo strtoupper(substr($post['firstname'] ?? 'A', 0, 1)); ?></div>
          <div class="meta-info">
            <strong><?php echo htmlspecialchars(($post['firstname'] ?? 'Admin') . ' ' . ($post['lastname'] ?? '')); ?></strong>
            <span>Published on <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
          </div>
        </div>
      </header>
      
      <?php if(!empty($post['image_path'])): ?>
          <div class="article-image-container">
            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
          </div>
      <?php endif; ?>
      
      <div class="article-content">
        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
      </div>

      <section class="comments-section">
        <h3>Discussion</h3>
        
        <div class="comment-list">
          <?php
          if (isset($conn) && !$conn->connect_error) {
              $c_sql = "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = $post_id ORDER BY c.created_at DESC";
              $c_result = $conn->query($c_sql);
              
              if ($c_result && $c_result->num_rows > 0):
                  while($c = $c_result->fetch_assoc()):
          ?>
                    <div class="comment-card">
                      <div class="comment-header">
                        <div class="comment-author">
                          @<?php echo htmlspecialchars($c['username']); ?>
                          <?php if($c['username'] === $post['username']): ?>
                              <span class="badge">Author</span>
                          <?php endif; ?>
                        </div>
                        <div class="comment-date"><?php echo date('M j, Y • g:i a', strtotime($c['created_at'])); ?></div>
                      </div>
                      <div class="comment-body">
                        <?php echo nl2br(htmlspecialchars($c['comment_text'])); ?>
                      </div>
                    </div>
          <?php 
                  endwhile;
              else: 
          ?>
                  <div class="no-comments">No comments yet. Be the first to share your thoughts!</div>
          <?php 
              endif; 
          }
          ?>
        </div>

        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="comment-form-box">
              <h4>Leave a comment</h4>
              <form action="index.php?page=add_comment" method="POST">
                  <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                  <textarea name="comment" placeholder="What are your thoughts on this?" required></textarea>
                  <button type="submit" class="btn-submit">Post Comment</button>
              </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
              <p>You must be logged in to participate in the discussion.</p>
              <a href="index.php?page=login" class="btn-login-outline">Log in to Comment</a>
            </div>
        <?php endif; ?>
        
      </section>
      
    </article>
  </main>

  <footer>
    <div class="footer-bottom">
      <p>COPYRIGHT &copy; 2024 CMS &mdash; Built with passion for journalism. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>