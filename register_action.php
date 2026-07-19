<?php
// register_action.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Ensure database connection exists (fallback if not routed through index.php)
if (!isset($conn)) {
    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "cms";
    
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    if ($conn->connect_error) { 
        die("<script>alert('Database Connection Failed.'); window.history.back();</script>"); 
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Trim whitespace from inputs
    $username = trim($_POST['username']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirmpassword'];

    // 1. Basic Validation
    if (empty($username) || empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        die("<script>alert('All fields are required!'); window.history.back();</script>");
    }

    if ($password !== $confirm) {
        die("<script>alert('Passwords do not match!'); window.history.back();</script>");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('Invalid email format!'); window.history.back();</script>");
    }

    // 2. Check if username or email already exists (using Prepared Statements)
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        die("<script>alert('Error: Username or Email already exists. Please choose another.'); window.history.back();</script>");
    }
    $check_stmt->close();

    // 3. Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user'; // Default role for new registrations

    // 4. Insert new user securely
    $insert_stmt = $conn->prepare("INSERT INTO users (username, firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("ssssss", $username, $firstname, $lastname, $email, $hashed_password, $role);

    if ($insert_stmt->execute()) {
        // Success: Redirect to login or home
        echo "<script>alert('Registration successful! You can now log in.'); window.location.href='index.php?page=login';</script>";
    } else {
        // Failure
        echo "<script>alert('An unexpected error occurred during registration. Please try again later.'); window.history.back();</script>";
    }
    
    $insert_stmt->close();
} else {
    // If accessed directly without POST data
    header("Location: index.php?page=register");
    exit();
}
?>