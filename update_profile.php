<?php
// update_profile.php — Lets a logged-in user update their own name, email and (optionally) password.
// Reached via: index.php?page=update_profile  (POST from detail.php profile form)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit();
}

if (!isset($conn)) { require_once 'db.php'; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?page=detail");
    exit();
}

$user_id     = (int) $_SESSION['user_id'];
$firstname   = trim($_POST['firstname'] ?? '');
$lastname    = trim($_POST['lastname'] ?? '');
$email       = trim($_POST['email'] ?? '');
$currentpass = $_POST['currentpass'] ?? '';
$newpass     = $_POST['newpass'] ?? '';

// Helper to bounce back to the profile with a status message (shown via session)
function back_with($type, $msg) {
    $_SESSION['profile_' . $type] = $msg;
    header("Location: index.php?page=detail");
    exit();
}

// 1. Basic validation
if ($firstname === '' || $lastname === '' || $email === '') {
    back_with('error', 'First name, last name and email are all required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_with('error', 'Please enter a valid email address.');
}

// 2. Load the current user and verify the current password (required to save changes)
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    back_with('error', 'User account not found.');
}
if (!password_verify($currentpass, $user['password'])) {
    back_with('error', 'Your current password is incorrect. Changes were not saved.');
}

// 3. Make sure the new email is not already used by a DIFFERENT user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$stmt->store_result();
$email_taken = $stmt->num_rows > 0;
$stmt->close();

if ($email_taken) {
    back_with('error', 'That email address is already used by another account.');
}

// 4. Update — with or without a new password
if ($newpass !== '') {
    if (strlen($newpass) < 6) {
        back_with('error', 'New password must be at least 6 characters long.');
    }
    $hashed = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, password = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $firstname, $lastname, $email, $hashed, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE id = ?");
    $stmt->bind_param("sssi", $firstname, $lastname, $email, $user_id);
}

if ($stmt->execute()) {
    $stmt->close();
    back_with('success', 'Profile updated successfully!');
} else {
    $err = $stmt->error;
    $stmt->close();
    back_with('error', 'Could not update profile: ' . $err);
}
