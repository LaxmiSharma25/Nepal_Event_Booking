<?php
// Database creation script
header('Content-Type: text/html; charset=utf-8');

// Database parameters - match with config/db.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nepali_event_booking";
$port = 3303;  // Updated port number based on user feedback

// Initialize status variables
$mysqlRunning = false;
$dbCreated = false;
$schemasImported = false;
$errorMessage = '';

// Function to check if MySQL is running
function isMySQLRunning($host, $user, $pass, $port) {
    try {
        $mysqli = @new mysqli($host, $user, $pass, "", $port);
        if ($mysqli->connect_error) {
            return false;
        }
        $mysqli->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to create database
function createDatabase($host, $user, $pass, $dbname, $port) {
    try {
        $mysqli = new mysqli($host, $user, $pass, "", $port);
        // Create database with proper encoding
        $result = $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $mysqli->close();
        
        if ($result) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
}

// Function to import SQL file
function importSQL($host, $user, $pass, $dbname, $sqlFile, $port) {
    // Check if file exists
    if (!file_exists($sqlFile)) {
        return "SQL file not found: $sqlFile";
    }
    
    // Read the SQL file
    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        return "Could not read SQL file: $sqlFile";
    }
    
    try {
        // Connect to the database
        $mysqli = new mysqli($host, $user, $pass, $dbname, $port);
        
        // Set encoding
        $mysqli->set_charset("utf8mb4");
        
        // Execute multi query
        if ($mysqli->multi_query($sql)) {
            // Process all results to clear them
            do {
                if ($result = $mysqli->store_result()) {
                    $result->free();
                }
            } while ($mysqli->more_results() && $mysqli->next_result());
            
            $mysqli->close();
            return true;
        } else {
            $error = $mysqli->error;
            $mysqli->close();
            return "Error importing SQL: $error";
        }
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
}

// Start the process
try {
    // Check if MySQL is running
    $mysqlRunning = isMySQLRunning($servername, $username, $password, $port);
    if (!$mysqlRunning) {
        $errorMessage = "MySQL server is not running. Please start your MySQL server or check if it's running on port $port.";
    } else {
        // Create database
        $dbCreated = createDatabase($servername, $username, $password, $dbname, $port);
        
        if (!$dbCreated) {
            $errorMessage = "Failed to create database. Make sure you have sufficient privileges.";
        } else {
            // Import schemas
            $importResult = importSQL($servername, $username, $password, $dbname, 'database.sql', $port);
            
            if ($importResult === true) {
                $schemasImported = true;
            } else {
                $errorMessage = $importResult;
                // Attempt to extract table creation statements from database.sql
                $sqlContent = file_get_contents('database.sql');
                if ($sqlContent) {
                    // Extract table creation statements for debugging
                    preg_match_all('/CREATE TABLE.*?;/s', $sqlContent, $matches);
                    $tableStatements = $matches[0];
                    
                    if (!empty($tableStatements)) {
                        // Try to execute each table creation statement separately
                        $mysqli = new mysqli($servername, $username, $password, $dbname, $port);
                        $mysqli->set_charset("utf8mb4");
                        
                        foreach ($tableStatements as $statement) {
                            $mysqli->query($statement);
                        }
                        
                        $mysqli->close();
                        
                        // Check if any tables were created
                        $mysqli = new mysqli($servername, $username, $password, $dbname, $port);
                        $result = $mysqli->query("SHOW TABLES");
                        if ($result && $result->num_rows > 0) {
                            $schemasImported = true;
                            $errorMessage = "Some tables were created, but there might be issues with data import.";
                        }
                        $mysqli->close();
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $errorMessage = "Unexpected error: " . $e->getMessage();
}

// Show results page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Nepali Event Booking System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #1d3557;
            margin-top: 0;
        }
        .status-card {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .status-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .status-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1d3557;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #457b9d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup</h1>
        
        <?php if ($schemasImported): ?>
            <div class="status-card status-success">
                <h2>Database Setup Completed Successfully</h2>
                <p>The database has been created and initialized with all required tables and data.</p>
                <div style="margin-top: 20px;">
                    <a href="index.php" class="btn">Go to Homepage</a>
                    <a href="admin/index.php" class="btn">Go to Admin Panel</a>
                    <a href="db_status.php" class="btn">Check Database Status</a>
                </div>
            </div>
        <?php elseif ($dbCreated): ?>
            <div class="status-card status-warning">
                <h2>Database Created But Schema Import Failed</h2>
                <p>The database was created successfully, but there was an issue importing the schema:</p>
                <p><strong><?php echo $errorMessage; ?></strong></p>
                <p>Please try to import the database schema manually:</p>
                <ol>
                    <li>Open phpMyAdmin (usually at http://localhost/phpmyadmin)</li>
                    <li>Select the database "<?php echo $dbname; ?>"</li>
                    <li>Click on the "Import" tab</li>
                    <li>Select the file "database.sql" and click "Go"</li>
                </ol>
                <div style="margin-top: 20px;">
                    <a href="db_status.php" class="btn">Check Database Status</a>
                </div>
            </div>
        <?php else: ?>
            <div class="status-card status-error">
                <h2>Database Setup Failed</h2>
                <p><strong>Error: <?php echo $errorMessage; ?></strong></p>
                <p>Please ensure:</p>
                <ul>
                    <li>MySQL server is running (check XAMPP Control Panel)</li>
                    <li>MySQL is listening on port <?php echo $port; ?></li>
                    <li>The user '<?php echo $username; ?>' has privileges to create databases</li>
                    <li>The database '<?php echo $dbname; ?>' doesn't already exist or isn't locked</li>
                </ul>
                <div style="margin-top: 20px;">
                    <a href="db_status.php" class="btn">Check Database Status</a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Try Again</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 