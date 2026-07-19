<?php
// Include your database connection
require 'db.php';

// The password we want to use for everyone
$plain_password = "123";

// Encrypt the password so your login.php file can read it securely
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// List of dummy users to add
$dummy_users = [
    ['username' => 'mustufa', 'firstname' => 'Mustufa', 'lastname' => 'User', 'email' => 'mustufa@example.com'],
    ['username' => 'meet', 'firstname' => 'Meet', 'lastname' => 'User', 'email' => 'meet@example.com'],
    ['username' => 'yusuf', 'firstname' => 'Yusuf', 'lastname' => 'User', 'email' => 'yusuf@example.com'],
    ['username' => 'alice', 'firstname' => 'Alice', 'lastname' => 'Smith', 'email' => 'alice@example.com'],
    ['username' => 'bob', 'firstname' => 'Bob', 'lastname' => 'Johnson', 'email' => 'bob@example.com'],
    ['username' => 'charlie', 'firstname' => 'Charlie', 'lastname' => 'Brown', 'email' => 'charlie@example.com'],
    ['username' => 'diana', 'firstname' => 'Diana', 'lastname' => 'Prince', 'email' => 'diana@example.com'],
    ['username' => 'ethan', 'firstname' => 'Ethan', 'lastname' => 'Hunt', 'email' => 'ethan@example.com']
];

echo "<h2>Seeding Dummy Users...</h2>";

// Loop through each user and insert them into the database
foreach ($dummy_users as $user) {
    $u = $conn->real_escape_string($user['username']);
    $f = $conn->real_escape_string($user['firstname']);
    $l = $conn->real_escape_string($user['lastname']);
    $e = $conn->real_escape_string($user['email']);

    // We use INSERT IGNORE so if you run this twice, it won't crash or make duplicates.
    // NOTE: the users.role column is an ENUM('reader','content_creator','admin','superadmin');
    // 'reader' is the correct default role for a normal member ('user' is NOT a valid value).
    $sql = "INSERT IGNORE INTO users (username, firstname, lastname, email, password, role)
            VALUES ('$u', '$f', '$l', '$e', '$hashed_password', 'reader')";
            
    if ($conn->query($sql)) {
        // mysqli_affected_rows checks if a new row was actually added
        if ($conn->affected_rows > 0) {
            echo "<p style='color:green;'>✅ Added user: <strong>$u</strong></p>";
        } else {
            echo "<p style='color:orange;'>⚠️ User <strong>$u</strong> already exists. Skipped.</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Error adding <strong>$u</strong>: " . $conn->error . "</p>";
    }
}

echo "<h3>🎉 All done! You can now log in with the password: <strong>123</strong></h3>";
echo "<p style='color:red; font-weight:bold;'>IMPORTANT: Delete this file (seed_users.php) from your folder now so no one else can run it!</p>";
?>