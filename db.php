<?php
// db.php — Central database connection for the CMS
// Included by pages/scripts that are run directly (e.g. seed_*.php, my_posts.php).
// It is written to be SAFE to include even when index.php has already created $conn:
// it only opens a new connection if one does not already exist.

if (!isset($conn) || !($conn instanceof mysqli)) {
    $servername  = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname      = "cms";

    // Use a try/catch so a friendly message is shown on PHP 8+ (where mysqli
    // throws exceptions by default) instead of a raw fatal error.
    try {
        $conn = new mysqli($servername, $db_username, $db_password, $dbname);
        if ($conn->connect_error) {
            die("Database Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e) {
        die("Database Connection failed. Please make sure MySQL is running in XAMPP. (" . $e->getMessage() . ")");
    }
}
