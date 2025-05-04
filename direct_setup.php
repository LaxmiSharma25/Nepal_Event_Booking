<?php
// Direct Database Setup Script
// This script attempts to create and set up the database directly

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nepali_event_booking";
$port = 3303;
$socket = "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock";

echo "<h1>Direct Database Setup</h1>";
echo "<pre>Starting database setup process...</pre>";

// Step 1: Try connecting to MySQL server
echo "<h2>Step 1: Connecting to MySQL server</h2>";
try {
    echo "Attempting to connect to MySQL server at $servername:$port...<br>";
    $conn = new mysqli($servername, $username, $password, "", $port);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<span style='color:green'>Connected successfully to MySQL server!</span><br>";
    
    // Get MySQL version
    $result = $conn->query("SELECT VERSION() as version");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "MySQL Version: " . $row['version'] . "<br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Failed to connect to MySQL: " . $e->getMessage() . "</span><br>";
    echo "Trying alternative socket connection method...<br>";
    
    try {
        $conn = new mysqli($servername, $username, $password, "", $port, $socket);
        if ($conn->connect_error) {
            throw new Exception("Socket connection failed: " . $conn->connect_error);
        }
        echo "<span style='color:green'>Connected successfully using socket!</span><br>";
    } catch (Exception $e2) {
        echo "<span style='color:red'>Both connection methods failed. MySQL might not be running or credentials are incorrect.</span><br>";
        echo "Error details: " . $e2->getMessage() . "<br>";
        die("Cannot proceed without MySQL connection. Please check XAMPP and ensure MySQL is running on port $port.");
    }
}

// Step 2: Create database if not exists
echo "<h2>Step 2: Creating database</h2>";
try {
    echo "Checking if database '$dbname' exists...<br>";
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    
    if ($result && $result->num_rows > 0) {
        echo "Database '$dbname' already exists.<br>";
    } else {
        echo "Database doesn't exist. Creating now...<br>";
        if ($conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            echo "<span style='color:green'>Database created successfully!</span><br>";
        } else {
            throw new Exception("Failed to create database: " . $conn->error);
        }
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Error creating database: " . $e->getMessage() . "</span><br>";
    die("Cannot proceed without creating the database.");
}

// Step 3: Select the database and create tables
echo "<h2>Step 3: Creating tables</h2>";
try {
    // Select the database
    echo "Selecting database '$dbname'...<br>";
    $conn->select_db($dbname);
    echo "Database selected successfully.<br>";
    
    // Check if tables exist
    $result = $conn->query("SHOW TABLES");
    $tableCount = $result ? $result->num_rows : 0;
    
    if ($tableCount > 0) {
        echo "Found $tableCount existing tables in the database.<br>";
        echo "<ul>";
        while ($row = $result->fetch_row()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        echo "Tables already exist. Skipping table creation.<br>";
    } else {
        echo "No tables found. Creating tables from SQL file...<br>";
        
        // Check if database.sql file exists
        if (file_exists('database.sql')) {
            echo "Found database.sql file. Reading file content...<br>";
            $sql = file_get_contents('database.sql');
            if (!$sql) {
                throw new Exception("Could not read database.sql file");
            }
            
            echo "Executing SQL commands...<br>";
            
            // Extract and execute CREATE TABLE statements
            preg_match_all('/CREATE TABLE.*?;/s', $sql, $tableMatches);
            
            if (empty($tableMatches[0])) {
                echo "No CREATE TABLE statements found in SQL file.<br>";
            } else {
                echo "Found " . count($tableMatches[0]) . " CREATE TABLE statements.<br>";
                
                foreach ($tableMatches[0] as $index => $statement) {
                    echo "Executing statement " . ($index + 1) . "...<br>";
                    
                    if ($conn->query($statement)) {
                        echo "<span style='color:green'>Table created successfully!</span><br>";
                    } else {
                        echo "<span style='color:red'>Error creating table: " . $conn->error . "</span><br>";
                    }
                }
            }
            
            // Extract and execute INSERT statements 
            preg_match_all('/INSERT INTO.*?;/s', $sql, $insertMatches);
            
            if (empty($insertMatches[0])) {
                echo "No INSERT statements found in SQL file.<br>";
            } else {
                echo "Found " . count($insertMatches[0]) . " INSERT statements.<br>";
                
                foreach ($insertMatches[0] as $index => $statement) {
                    echo "Executing insert statement " . ($index + 1) . "...<br>";
                    
                    if ($conn->query($statement)) {
                        echo "<span style='color:green'>Data inserted successfully!</span><br>";
                    } else {
                        echo "<span style='color:red'>Error inserting data: " . $conn->error . "</span><br>";
                    }
                }
            }
        } else {
            echo "<span style='color:red'>database.sql file not found!</span><br>";
            
            // Create minimal tables
            echo "Creating minimal tables instead...<br>";
            
            $createUserTable = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            if ($conn->query($createUserTable)) {
                echo "<span style='color:green'>Users table created successfully!</span><br>";
            } else {
                echo "<span style='color:red'>Error creating users table: " . $conn->error . "</span><br>";
            }
            
            $createAdminTable = "CREATE TABLE IF NOT EXISTS admin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if ($conn->query($createAdminTable)) {
                echo "<span style='color:green'>Admin table created successfully!</span><br>";
                
                // Insert default admin user (username: admin, password: admin123)
                $adminInsert = "INSERT INTO admin (username, password, email) VALUES
                ('admin', '$2y$10$Gg8uZtQljgxcGtw80EJeZuA2k.j3HPPZx1w7QlQAvWXRUWUjCR6fq', 'admin@example.com')";
                
                if ($conn->query($adminInsert)) {
                    echo "<span style='color:green'>Default admin user created!</span><br>";
                } else {
                    echo "<span style='color:red'>Error creating admin user: " . $conn->error . "</span><br>";
                }
            } else {
                echo "<span style='color:red'>Error creating admin table: " . $conn->error . "</span><br>";
            }
        }
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Error setting up tables: " . $e->getMessage() . "</span><br>";
}

// Step 4: Verify database setup
echo "<h2>Step 4: Verifying database setup</h2>";
try {
    $result = $conn->query("SHOW TABLES");
    $tableCount = $result ? $result->num_rows : 0;
    
    if ($tableCount > 0) {
        echo "<span style='color:green'>Database setup completed successfully!</span><br>";
        echo "Found $tableCount tables in the database:<br>";
        echo "<ul>";
        while ($row = $result->fetch_row()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        
        echo "<h3>Setup Summary</h3>";
        echo "✅ Connected to MySQL<br>";
        echo "✅ Database '$dbname' created/verified<br>";
        echo "✅ Tables created/verified<br>";
        
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='index.php' style='padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Go to Homepage</a>";
        echo "<a href='admin/index.php' style='padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Go to Admin Panel</a>";
        echo "</div>";
    } else {
        echo "<span style='color:red'>Database setup seems incomplete. No tables found.</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Error verifying database: " . $e->getMessage() . "</span><br>";
}

// Close connection
$conn->close();
echo "<p>Connection closed.</p>";
?> 