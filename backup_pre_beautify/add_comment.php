<?php
// add_comment.php — Handles a logged-in user posting a comment on an article.
// Reached via: index.php?page=add_comment  (POST from single_post.php)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Must be logged in to comment
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit();
}

// Make sure we have a database connection (index.php normally provides $conn)
if (!isset($conn)) { require_once 'db.php'; }

// Only accept POST submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?page=home");
    exit();
}

$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$comment = trim($_POST['comment'] ?? '');
$user_id = (int) $_SESSION['user_id'];

// Basic validation: need a real post and a non-empty comment
if ($post_id <= 0 || $comment === '') {
    header("Location: index.php?page=single_post&id=" . $post_id);
    exit();
}

// Make sure the post actually exists and is published before allowing a comment
$check = $conn->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published'");
$check->bind_param("i", $post_id);
$check->execute();
$check->store_result();
$post_exists = $check->num_rows > 0;
$check->close();

if ($post_exists) {
    // Insert the comment using a prepared statement (prevents SQL injection)
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    $stmt->execute();
    $stmt->close();
}

// Send the reader back to the article (jump to the comments section)
header("Location: index.php?page=single_post&id=" . $post_id . "#comments");
exit();
