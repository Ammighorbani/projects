<?php
// Database connection settings
$db_host = "ammighorbani-mysql"; // Use 'localhost' if MySQL is running on the same server
$db_username = "ammighorbani-messenger";
$db_password = "Ammighorbani12";
$db_database = "messenger";

// Optional: Path to backups (if needed)
$backupPath = __DIR__ . "/admin/backups/";

// Create a connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_database);

// Check the connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: Uncomment this for testing connection
// echo "Connected successfully";
?>
