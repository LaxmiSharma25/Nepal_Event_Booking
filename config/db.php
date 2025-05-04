<?php
// Check if we're in an error state from a previous attempt
if (isset($_GET['db_error'])) {
    $errorMessage = "Database connection error. Please ensure MySQL is running and properly configured.";
    echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "<h3>Database Connection Error</h3>";
    echo "<p>{$errorMessage}</p>";
    echo "<p>Please check the following:</p>";
    echo "<ul>";
    echo "<li>Ensure MySQL server is running on your system</li>";
    echo "<li>Verify database credentials in config/db.php are correct</li>";
    echo "<li>Confirm the database 'nepali_event_booking' exists</li>";
    echo "</ul>";
    echo "<p>For development setup:</p>";
    echo "<ol>";
    echo "<li>Start your MySQL server</li>";
    echo "<li>Run the database setup script: <code>mysql -u root < database.sql</code></li>";
    echo "</ol>";
    echo "<p><a href='db_status.php' style='color: #721c24; text-decoration: underline;'>Check Database Status</a> | <a href='create_database.php' style='color: #721c24; text-decoration: underline;'>Create Database</a> | <a href='javascript:history.back()' style='color: #721c24; text-decoration: underline;'>Go Back</a></p>";
    echo "</div>";
    exit;
}

// Database configuration for XAMPP
$servername = "localhost";      // For XAMPP, use localhost
$username = "root";             // Default XAMPP username
$password = "";                 // Default XAMPP password (empty)
$dbname = "nepali_event_booking";
// $port = 3303;                // Port is not used with socket connection
$socket = "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock"; // XAMPP socket location from ps output

// Create connection with error handling
try {
    // Disable strict mode temporarily
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // Connect using socket instead of port
    $conn = new mysqli($servername, $username, $password, "", null, $socket);
    
    // Check if the database exists
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $dbExists = ($result && $result->num_rows > 0);
    
    // Create the database if it doesn't exist
    if (!$dbExists) {
        if ($conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            // Database created successfully
            error_log("Database $dbname created successfully");
        } else {
            throw new Exception("Failed to create database: " . $conn->error);
        }
    }
    
    // Close the initial connection
    $conn->close();
    
    // Now connect to the specific database using socket
    $conn = new mysqli($servername, $username, $password, $dbname, null, $socket);
    
    // Set character set to utf8mb4
    $conn->set_charset("utf8mb4");
    
    // Test the connection with a simple query
    $testQuery = $conn->query("SELECT 1");
    if (!$testQuery) {
        throw new Exception("Connection test failed");
    }
} catch (Exception $e) {
    // Debug information (output to error log)
    error_log("MySQL connection error: " . $e->getMessage());
    
    // For a cleaner user experience, redirect to the same page with an error parameter
    $currentPage = basename($_SERVER['PHP_SELF']);
    header("Location: $currentPage?db_error=1");
    exit;
}
?> 