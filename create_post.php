<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security check just in case it's accessed directly
if(!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$msg = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Note: ensure $conn is available here (e.g., via require_once 'db.php' if accessed directly)
    global $conn; 
    
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category = $conn->real_escape_string($_POST['category']);
    $user_id = $_SESSION['user_id'];
    
    $image_path = '';
    
    // Check if an offline file was uploaded
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_path = $target_dir . time() . "_" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
    } 
    // Otherwise, check if a link was provided
    else if (!empty($_POST['image_link'])) {
        $image_path = $conn->real_escape_string($_POST['image_link']);
    }

    // Insert as 'pending' for admin verification
    $sql = "INSERT INTO posts (user_id, title, content, image_path, category, status) 
            VALUES ('$user_id', '$title', '$content', '$image_path', '$category', 'pending')";
            
    if($conn->query($sql)) {
        echo "<script>alert('News article submitted! Waiting for Admin verification.'); window.location.href='index.php?page=home';</script>";
    } else {
        $msg = "Database Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Write News — CMS</title>
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
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0,0,0,0.08);
      --shadow-sm: 0 1px 6px rgba(0,0,0,0.06);
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }

    /* ── Navbar ── */
    .navbar {
      background: var(--dark);
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }
    .navbar-inner {
      max-width: 1240px; margin: 0 auto;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 24px; height: 68px;
    }
    .navbar .logo {
      display: flex; align-items: center; gap: 10px;
      text-decoration: none; color: var(--white);
    }
    .logo-icon {
      width: 36px; height: 36px; background: var(--primary);
      border-radius: 8px; display: flex; align-items: center;
      justify-content: center; font-size: 18px; font-weight: 800;
    }
    .logo-text { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
    .logo-text span { color: var(--accent); }
    .nav-links { display: flex; align-items: center; gap: 6px; }
    .nav-links a {
      color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500;
      padding: 8px 14px; border-radius: 8px; transition: all .2s;
    }
    .nav-links a:hover { background: rgba(255,255,255,0.08); color: var(--white); }
    
    .user-profile { display: flex; align-items: center; gap: 10px; margin-left: 12px; padding-left: 16px; border-left: 1px solid rgba(255,255,255,0.1); }
    .avatar { width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: var(--white); }

    /* ── Page Header ── */
    .page-header {
      background: linear-gradient(135deg, var(--dark) 0%, var(--dark2) 100%);
      padding: 60px 24px;
      text-align: center; color: var(--white);
    }
    .page-header h1 {
      font-family: 'Playfair Display', serif;
      font-size: 36px; font-weight: 800; margin-bottom: 12px;
    }
    .page-header p {
      color: #94a3b8; font-size: 16px; max-width: 600px; margin: 0 auto;
    }

    /* ── Form Container ── */
    .main-container {
      flex: 1;
      max-width: 800px;
      margin: -30px auto 60px;
      padding: 0 24px;
      position: relative;
      z-index: 10;
      width: 100%;
    }

    .form-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 40px;
    }
    
    .error-msg {
      background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; 
      border-radius: 8px; padding: 12px 16px; font-size: 14px; font-weight: 500; 
      margin-bottom: 24px; display: flex; align-items: center; gap: 8px;
    }

    .form-group { margin-bottom: 24px; }
    .form-group label { 
      display: block; font-size: 13px; font-weight: 600; color: var(--text); 
      margin-bottom: 8px; 
    }
    .form-group input[type="text"], 
    .form-group select, 
    .form-group textarea {
      width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); 
      border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text); 
      background: var(--bg); transition: all 0.2s;
    }
    .form-group input:focus, 
    .form-group select:focus, 
    .form-group textarea:focus { 
      outline: none; border-color: var(--primary); background: var(--white); 
      box-shadow: 0 0 0 3px rgba(37,99,235,0.1); 
    }
    
    /* File Upload Styling */
    .file-upload-wrapper {
      border: 2px dashed var(--border); border-radius: 8px; padding: 20px; 
      text-align: center; background: var(--bg); transition: all 0.2s;
    }
    .file-upload-wrapper:hover { border-color: var(--primary); background: var(--primary-light); }
    .file-upload-wrapper input[type="file"] { max-width: 100%; }
    
    .divider {
      display: flex; align-items: center; text-align: center; margin: 24px 0; color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase;
    }
    .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid var(--border); }
    .divider:not(:empty)::before { margin-right: 16px; }
    .divider:not(:empty)::after { margin-left: 16px; }

    .form-actions { display: flex; align-items: center; gap: 16px; margin-top: 32px; }
    .btn-submit { 
      padding: 12px 24px; background: var(--primary); color: var(--white); 
      border: none; border-radius: 8px; font-weight: 700; font-size: 14px; 
      cursor: pointer; transition: background 0.2s; font-family: inherit;
    }
    .btn-submit:hover { background: var(--primary-dark); }
    .btn-cancel { 
      color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 600; 
      transition: color 0.2s;
    }
    .btn-cancel:hover { color: var(--text); }

    /* ── Footer (copyright only) ── */
    footer { background: var(--dark); color: #94a3b8; margin-top: auto; }
    .footer-bottom { padding: 20px 24px; text-align: center; max-width: 1240px; margin: 0 auto; font-size: 13px; }

    @media(max-width: 768px) {
      .form-card { padding: 24px; }
    }
    @media(max-width: 640px) {
      .nav-links a:not(.user-profile) { display: none; }
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
        <div class="user-profile">
          <span style="font-size: 13px; font-weight: 500; color: var(--white);">
            <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
          </span>
          <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
        </div>
      </div>
    </div>
  </nav>

  <header class="page-header">
    <h1>Publish Your Story</h1>
    <p>Share your insights and breaking news with the world.</p>
  </header>

  <main class="main-container">
    <div class="form-card">
      
      <?php if(!empty($msg)): ?>
        <div class="error-msg">
          <span style="font-size: 18px;">⚠️</span> <?php echo $msg; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" enctype="multipart/form-data">
        
        <div class="form-group">
          <label for="title">Article Title</label>
          <input type="text" id="title" name="title" placeholder="Enter a captivating title..." required>
        </div>
        
        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category">
            <option value="Technology">Technology</option>
            <option value="Entertainment">Entertainment</option>
            <option value="World">World</option>
            <option value="Business">Business</option>
            <option value="Finance">Finance</option>
            <option value="Health">Health</option>
            <option value="Sports">Sports</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="content">Article Content</label>
          <textarea id="content" name="content" rows="8" placeholder="Write your full article here..." required></textarea>
        </div>
        
        <div class="form-group">
          <label>Upload Cover Image</label>
          <div class="file-upload-wrapper">
            <input type="file" name="image" accept="image/*">
            <p style="font-size: 12px; color: var(--muted); margin-top: 8px;">Recommended size: 800x450px (JPG, PNG)</p>
          </div>
        </div>
        
        <div class="divider">Or</div>
        
        <div class="form-group">
          <label for="image_link">Image URL (Online Link)</label>
          <input type="text" id="image_link" name="image_link" placeholder="https://example.com/image.jpg">
        </div>
        
        <div class="form-actions">
          <button type="submit" class="btn-submit">Submit for Review &#8594;</button>
          <a href="index.php?page=home" class="btn-cancel">Cancel</a>
        </div>
        
      </form>
    </div>
  </main>

  <footer>
    <div class="footer-bottom">
      <p>COPYRIGHT &copy; 2024 CMS &mdash; Built with passion for journalism. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>