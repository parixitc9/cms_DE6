<?php
// user_content_action.php — Lets a logged-in user delete THEIR OWN posts and comments.
// Reached via links like: index.php?page=user_content_action&action=delete_post&id=5
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security: must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit();
}

if (!isset($conn)) { require_once 'db.php'; }

$action  = $_GET['action'] ?? '';
$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = (int) $_SESSION['user_id'];

$redirect = "index.php?page=detail";

if ($id > 0) {
    switch ($action) {

        case 'delete_post':
            // First confirm this post really belongs to the current user
            $check = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
            $check->bind_param("ii", $id, $user_id);
            $check->execute();
            $check->store_result();
            $owns_post = $check->num_rows > 0;
            $check->close();

            if ($owns_post) {
                // Delete the post's comments first, then the post itself
                $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $id, $user_id);
                $stmt->execute();
                $stmt->close();
            }
            $redirect = "index.php?page=my_posts";
            break;

        case 'delete_comment':
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            $stmt->close();
            $redirect = "index.php?page=my_comments";
            break;

        default:
            $redirect = "index.php?page=detail";
            break;
    }
}

header("Location: " . $redirect);
exit();
