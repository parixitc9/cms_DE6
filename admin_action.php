<?php
// admin_action.php — Handles all admin moderation actions (publish / unpublish / delete).
// Reached via links like: index.php?page=admin_action&action=publish_post&id=5
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security: only admins / superadmins may perform these actions
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin', 'superadmin'])) {
    header("Location: index.php?page=home");
    exit();
}

if (!isset($conn)) { require_once 'db.php'; }

$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Where to send the admin back to after each kind of action
$redirect = "index.php?page=admin";

if ($id > 0) {
    switch ($action) {

        // ── Post moderation ──
        case 'publish_post':
            $stmt = $conn->prepare("UPDATE posts SET status = 'published' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $redirect = "index.php?page=post";
            break;

        case 'unpublish_post':
            $stmt = $conn->prepare("UPDATE posts SET status = 'pending' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $redirect = "index.php?page=post";
            break;

        case 'delete_post':
            // Remove the post's comments first to avoid orphan rows, then the post
            $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $redirect = "index.php?page=post";
            break;

        // ── Comment moderation ──
        case 'delete_comment':
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $redirect = "index.php?page=comments";
            break;

        // ── Newsletter subscribers ──
        case 'delete_subscriber':
            $stmt = $conn->prepare("DELETE FROM subscribers WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $redirect = "index.php?page=subscribers";
            break;

        // ── User management ──
        case 'delete_user':
            // Safety: an admin cannot delete their own account here
            if ($id === (int) $_SESSION['user_id']) {
                $redirect = "index.php?page=list";
                break;
            }
            // Clean up the user's comments and posts (and comments on those posts)
            $conn->query("DELETE FROM comments WHERE user_id = $id");
            $conn->query("DELETE c FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = $id");
            $stmt = $conn->prepare("DELETE FROM posts WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $redirect = "index.php?page=list";
            break;

        default:
            // Unknown action — just go back to the dashboard
            $redirect = "index.php?page=admin";
            break;
    }
}

header("Location: " . $redirect);
exit();
