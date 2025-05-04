<?php
// Database status checker - independent of other files
$pageTitle = "Database Status Check";

// Define database parameters - match these with config/db.php
$servername = "localhost";     // For XAMPP, use localhost instead of 127.0.0.1
$username = "root";            // Default XAMPP username
$password = "";                // Default XAMPP password (empty)
$dbname = "nepali_event_booking";
$port = 3303;                  // Updated port number based on user feedback

// Function to check if MySQL is running
function isMySQLRunning($host, $user, $pass, $port = 3303) {
    try {
        // Explicitly suppress connection warnings
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

// Function to check if we can connect to a specific database
function canConnectToDatabase($host, $user, $pass, $dbname, $port = 3303) {
    try {
        $mysqli = @new mysqli($host, $user, $pass, $dbname, $port);
        if ($mysqli->connect_error) {
            return false;
        }
        $mysqli->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to check if database exists
function databaseExists($host, $user, $pass, $dbname, $port = 3303) {
    if (!isMySQLRunning($host, $user, $pass, $port)) {
        return false;
    }
    
    try {
        $mysqli = new mysqli($host, $user, $pass, "", $port);
        $result = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
        $exists = $result && $result->num_rows > 0;
        $mysqli->close();
        return $exists;
    } catch (Exception $e) {
        return false;
    }
}

// Function to try creating the database
function createDatabase($host, $user, $pass, $dbname, $port = 3303) {
    if (!isMySQLRunning($host, $user, $pass, $port)) {
        return false;
    }
    
    try {
        $mysqli = new mysqli($host, $user, $pass, "", $port);
        $success = $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $mysqli->close();
        return $success;
    } catch (Exception $e) {
        return false;
    }
}

// Check server status
$mysqlRunning = isMySQLRunning($servername, $username, $password, $port);
$dbExists = $mysqlRunning ? databaseExists($servername, $username, $password, $dbname, $port) : false;
$canConnect = $dbExists ? canConnectToDatabase($servername, $username, $password, $dbname, $port) : false;

// Try to create database if it doesn't exist
$dbCreated = false;
if ($mysqlRunning && !$dbExists) {
    $dbCreated = createDatabase($servername, $username, $password, $dbname, $port);
    if ($dbCreated) {
        $dbExists = true;
        $canConnect = canConnectToDatabase($servername, $username, $password, $dbname, $port);
    }
}

// Get server information
$serverInfo = [
    'PHP Version' => phpversion(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Operating System' => PHP_OS,
    'MySQL Connection' => $mysqlRunning ? 'Working' : 'Not Working',
    'MySQL Port' => $port,
    'Database Exists' => $dbExists ? 'Yes' : 'No',
    'Can Connect to Database' => $canConnect ? 'Yes' : 'No',
];

// Try to get MySQL version if possible
if ($mysqlRunning) {
    try {
        $mysqli = new mysqli($servername, $username, $password, "", $port);
        $result = $mysqli->query("SELECT VERSION() as version");
        if ($result) {
            $row = $result->fetch_assoc();
            $serverInfo['MySQL Version'] = $row['version'];
        }
        $mysqli->close();
    } catch (Exception $e) {
        // Ignore any errors
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Nepali Event Booking System</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
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
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        ol, ul {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Status Check</h1>
        
        <?php if (!$mysqlRunning): ?>
            <div class="status-card status-error">
                <h2>MySQL Server Not Running</h2>
                <p>The system could not connect to your MySQL server. Please ensure it is running.</p>
                <h3>Troubleshooting Steps:</h3>
                <ol>
                    <li>Make sure MySQL service is started on your computer</li>
                    <li>For XAMPP: Open XAMPP Control Panel and ensure MySQL is running (green light)</li>
                    <li>For MAMP: Open MAMP Control Panel and ensure MySQL is running</li>
                    <li>Check if the MySQL port (default: 3306) is not blocked or used by another application</li>
                </ol>
                <p><strong>XAMPP users:</strong> Try opening the XAMPP Control Panel, then:</p>
                <ol>
                    <li>Stop MySQL service if it's running</li>
                    <li>Click on "Config" for MySQL</li>
                    <li>Select "my.ini" to edit the configuration file</li>
                    <li>Check the socket and port configuration</li>
                    <li>Restart the MySQL service</li>
                </ol>
            </div>
        <?php elseif ($dbCreated): ?>
            <div class="status-card status-success">
                <h2>Database Created Successfully</h2>
                <p>The database <strong><?php echo $dbname; ?></strong> has been created! Now you need to import the schema:</p>
                <ol>
                    <li>Run the SQL setup script using the command: <code>mysql -u root <?php echo $dbname; ?> < database.sql</code></li>
                    <li>Or import via phpMyAdmin: Select the new database and import the database.sql file</li>
                </ol>
                <div style="margin-top: 15px;">
                    <a href="index.php" class="btn">Go to Homepage</a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Refresh Status</a>
                </div>
            </div>
        <?php elseif (!$dbExists): ?>
            <div class="status-card status-warning">
                <h2>Database Not Found</h2>
                <p>MySQL is running, but the database <strong><?php echo $dbname; ?></strong> does not exist.</p>
                <form method="post" action="create_database.php" style="margin-bottom: 15px;">
                    <button type="submit" name="create_db" class="btn">Create Database Now</button>
                </form>
                <h3>Or manually create the database:</h3>
                <ol>
                    <li>Run the SQL setup script using the command: <code>mysql -u root < database.sql</code></li>
                    <li>Alternatively, import via phpMyAdmin:</li>
                    <ul>
                        <li>Open phpMyAdmin in your browser (usually http://localhost/phpmyadmin)</li>
                        <li>Click "New" to create a database named "<?php echo $dbname; ?>"</li>
                        <li>Select the database and click "Import"</li>
                        <li>Browse for the database.sql file and click "Go"</li>
                    </ul>
                </ol>
            </div>
        <?php elseif (!$canConnect): ?>
            <div class="status-card status-warning">
                <h2>Database Exists But Cannot Connect</h2>
                <p>The database <strong><?php echo $dbname; ?></strong> exists, but the application cannot connect to it.</p>
                <h3>Possible issues:</h3>
                <ul>
                    <li>Insufficient permissions for the user '<?php echo $username; ?>'</li>
                    <li>Database is empty or corrupted</li>
                    <li>Connection settings mismatch between application and MySQL</li>
                </ul>
                <p>Try importing the database schema:</p>
                <code>mysql -u root <?php echo $dbname; ?> < database.sql</code>
            </div>
        <?php else: ?>
            <div class="status-card status-success">
                <h2>Database Connection Successful</h2>
                <p>The system successfully connected to the MySQL server and the database exists.</p>
                <div style="margin-top: 15px;">
                    <a href="index.php" class="btn">Go to Homepage</a>
                    <a href="admin/index.php" class="btn">Go to Admin Login</a>
                </div>
            </div>
        <?php endif; ?>
        
        <h2>Server Information</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <?php foreach ($serverInfo as $key => $value): ?>
                <tr>
                    <td><?php echo $key; ?></td>
                    <td><?php echo $value; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Database Configuration</h2>
        <p>Current database connection parameters in <code>config/db.php</code>:</p>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Server</td>
                <td><?php echo $servername; ?></td>
            </tr>
            <tr>
                <td>Port</td>
                <td><?php echo $port; ?></td>
            </tr>
            <tr>
                <td>Username</td>
                <td><?php echo $username; ?></td>
            </tr>
            <tr>
                <td>Password</td>
                <td><em>Hidden</em></td>
            </tr>
            <tr>
                <td>Database</td>
                <td><?php echo $dbname; ?></td>
            </tr>
        </table>
    </div>
</body>
</html> 