<?php
// Start the global session for the entire CMS
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Global Database Connection
// This ensures $conn is available to all files loaded through this router
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "cms";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) { 
    die("Database Connection failed: " . $conn->connect_error); 
}

// Determine which page to load (default to home)
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Basic Routing Engine
switch ($page) {
    // ── Public Pages ──
    case 'home':
        require 'home.php';
        break;
    case 'about':
        require 'about.php';
        break;
    case 'single_post':
        require 'single_post.php';
        break;

    // ── Authentication ──
    case 'login':
        require 'login.php';
        break;
    case 'register':
        require 'register.php';
        break;
    case 'logout':
        session_destroy();
        header("Location: index.php?page=home");
        exit();

    // ── User Dashboard & Actions ──
    case 'detail':
        require 'detail.php';
        break;
    case 'update_profile':
        require 'update_profile.php'; // From your recent profile editing updates
        break;
    case 'create_post':
        require 'create_post.php';
        break;
    case 'my_posts':
        require 'my_posts.php';
        break;
    case 'my_comments':
        require 'my_comments.php';
        break;
    case 'user_content_action':
        require 'user_content_action.php';
        break;
    case 'add_comment':
        require 'add_comment.php';
        break;

    // ── Admin Panel ──
    case 'admin':
        require 'admin.php';
        break;
    case 'post':
        require 'post.php';
        break;
    case 'list':
        require 'list.php';
        break;
    case 'comments':
        require 'comments.php';
        break;
    case 'edit_user':
        require 'edit_user.php';
        break;
    case 'admin_action':
        require 'admin_action.php';
        break;

    // ── Fallback ──
    default:
        require 'home.php';
        break;
}

// Close the database connection after the page has finished rendering.
// Guard against pages that may have already closed it, so we never trigger an
// "mysqli object is already closed" error on PHP 8+.
if (isset($conn) && $conn instanceof mysqli) {
    try {
        $conn->close();
    } catch (\Throwable $e) {
        // Connection was already closed by the page — safe to ignore.
    }
}
?>