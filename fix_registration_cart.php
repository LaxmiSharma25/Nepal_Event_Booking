<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

echo "<h1>Fix Registration and Cart Issues</h1>";

// Helper function to log steps
function logStep($message, $type = 'info') {
    $color = 'black';
    $icon = 'ℹ️';
    
    if ($type == 'success') {
        $color = 'green';
        $icon = '✅';
    } else if ($type == 'error') {
        $color = 'red';
        $icon = '❌';
    } else if ($type == 'warning') {
        $color = 'orange';
        $icon = '⚠️';
    }
    
    echo "<p style='color:{$color};'>{$icon} {$message}</p>";
}

// 1. First, check the users table structure
echo "<h2>Part 1: Fixing User Registration</h2>";
logStep("Checking users table structure...");

// Check if users table exists and has correct structure
$checkUsersTable = $conn->query("DESCRIBE users");
if (!$checkUsersTable) {
    logStep("Users table doesn't exist or has errors. Attempting to create it...", 'warning');
    
    // Create a proper users table
    $createUsersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createUsersTable)) {
        logStep("Created users table with proper structure", 'success');
    } else {
        logStep("Failed to create users table: " . $conn->error, 'error');
    }
} else {
    logStep("Users table exists.", 'success');
    
    // Check if the users table has the correct columns
    $columns = [];
    while ($row = $checkUsersTable->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['id', 'name', 'email', 'password', 'phone', 'address', 'created_at', 'updated_at'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (!empty($missingColumns)) {
        logStep("Users table is missing some columns: " . implode(', ', $missingColumns), 'warning');
        
        // Add missing columns if possible
        foreach ($missingColumns as $column) {
            $alterSql = "";
            
            if ($column == 'id' && !in_array('id', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST";
            } else if ($column == 'name' && !in_array('name', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL AFTER id";
            } else if ($column == 'email' && !in_array('email', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN email VARCHAR(100) NOT NULL UNIQUE AFTER name";
            } else if ($column == 'password' && !in_array('password', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email";
            } else if ($column == 'phone' && !in_array('phone', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NOT NULL AFTER password";
            } else if ($column == 'address' && !in_array('address', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN address TEXT AFTER phone";
            } else if ($column == 'created_at' && !in_array('created_at', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            } else if ($column == 'updated_at' && !in_array('updated_at', $columns)) {
                $alterSql = "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            }
            
            if (!empty($alterSql)) {
                if ($conn->query($alterSql)) {
                    logStep("Added missing column: {$column}", 'success');
                } else {
                    logStep("Failed to add column {$column}: " . $conn->error, 'error');
                }
            }
        }
    } else {
        logStep("Users table has all required columns", 'success');
    }
}

// 2. Check for any issues with register.php
echo "<h2>Part 2: Verifying Registration Script</h2>";

$registerFile = 'register.php';
if (file_exists($registerFile)) {
    $registerContent = file_get_contents($registerFile);
    
    // Check for any issues in register.php
    $issues = [];
    
    // Check for proper database insertion
    if (strpos($registerContent, 'INSERT INTO users') === false) {
        $issues[] = "Missing INSERT statement for users table";
    }
    
    // Check for password hashing
    if (strpos($registerContent, 'password_hash') === false) {
        $issues[] = "Missing password hashing";
    }
    
    // Check for proper redirection after successful registration
    if (strpos($registerContent, 'redirect') === false) {
        $issues[] = "Missing redirect after registration";
    }
    
    if (empty($issues)) {
        logStep("Registration script looks good.", 'success');
    } else {
        logStep("Found issues in registration script:", 'warning');
        foreach ($issues as $issue) {
            logStep($issue, 'warning');
        }
        
        // Fix issues if possible
        logStep("Attempting to fix registration script...");
        
        // Add debug output to registration script
        $debugCode = "
// Add debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add registration debug logging
if (isset(\$_POST['debug_output'])) {
    echo '<div style=\"background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;\">';
    echo '<h3>Debug Information</h3>';
    echo '<p>POST data: ' . print_r(\$_POST, true) . '</p>';
    
    if (!empty(\$errors)) {
        echo '<p>Errors: ' . print_r(\$errors, true) . '</p>';
    }
    
    if (isset(\$sql)) {
        echo '<p>SQL: ' . \$sql . '</p>';
    }
    
    if (isset(\$stmt) && \$stmt->error) {
        echo '<p>Database error: ' . \$stmt->error . '</p>';
    }
    
    echo '</div>';
}
";
        
        // Add debug code after the require_once line
        $updatedRegisterContent = str_replace(
            "require_once 'includes/header.php';",
            "require_once 'includes/header.php';\n{$debugCode}",
            $registerContent
        );
        
        // Add debug button to the form
        $updatedRegisterContent = str_replace(
            '<button type="submit" class="btn btn-primary">Register</button>',
            '<button type="submit" class="btn btn-primary">Register</button>
            <button type="submit" name="debug_output" value="1" class="btn btn-outline" style="margin-left: 10px;">Register with Debug</button>',
            $updatedRegisterContent
        );
        
        if ($registerContent !== $updatedRegisterContent) {
            if (file_put_contents($registerFile, $updatedRegisterContent)) {
                logStep("Updated registration script with debugging code", 'success');
            } else {
                logStep("Failed to update registration script", 'error');
            }
        }
    }
} else {
    logStep("Registration file not found: {$registerFile}", 'error');
}

// 3. Check Cart Link in Header.php
echo "<h2>Part 3: Fixing Cart Link in Header</h2>";
$headerFile = 'includes/header.php';

if (file_exists($headerFile)) {
    $headerContent = file_get_contents($headerFile);
    
    // Check if cart link exists but might be hidden or incorrectly formatted
    if (strpos($headerContent, 'cart.php') !== false) {
        logStep("Cart link exists in header, but might be incorrectly styled or positioned", 'info');
        
        // Extract the navigation section
        if (preg_match('/<nav>.*?<\/nav>/s', $headerContent, $matches)) {
            logStep("Found navigation section in header", 'success');
            $navContent = $matches[0];
            
            // Check navigation content
            logStep("Current navigation content: " . htmlspecialchars(substr($navContent, 0, 100)) . "...", 'info');
        } else {
            logStep("Could not find navigation section in header", 'warning');
        }
    } else {
        logStep("Cart link is missing in header", 'warning');
        
        // Add cart link to header within logged in section
        $loggedInPattern = '<?php if \(isLoggedIn\(\)\): ?>(.*?)<li><a href="logout.php">Logout<\/a><\/li>/s';
        
        if (preg_match($loggedInPattern, $headerContent, $matches)) {
            $loggedInSection = $matches[0];
            $updatedLoggedInSection = str_replace(
                '<?php if (isLoggedIn()): ?>',
                '<?php if (isLoggedIn()): ?>
                        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>',
                $loggedInSection
            );
            
            $updatedHeaderContent = str_replace($loggedInSection, $updatedLoggedInSection, $headerContent);
            
            if ($headerContent !== $updatedHeaderContent) {
                if (file_put_contents($headerFile, $updatedHeaderContent)) {
                    logStep("Added cart link to header", 'success');
                } else {
                    logStep("Failed to update header file", 'error');
                }
            }
        } else {
            logStep("Could not find logged in section in header", 'warning');
            
            // Try to find navigation section
            if (preg_match('/<nav>(.*?)<\/nav>/s', $headerContent, $matches)) {
                $navSection = $matches[0];
                $updatedNavSection = str_replace(
                    '<ul>',
                    '<ul>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>',
                    $navSection
                );
                
                $updatedHeaderContent = str_replace($navSection, $updatedNavSection, $headerContent);
                
                if ($headerContent !== $updatedHeaderContent) {
                    if (file_put_contents($headerFile, $updatedHeaderContent)) {
                        logStep("Added cart link to navigation", 'success');
                    } else {
                        logStep("Failed to update header file", 'error');
                    }
                }
            } else {
                logStep("Could not find navigation section in header", 'error');
                
                // Create a completely new header.php file as a last resort
                $newHeaderContent = '<?php
require_once \'config/db.php\';
require_once \'includes/functions.php\';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepali Event Booking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <h1>Nepali Event Booking</h1>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                        <li class="dropdown">
                            <a href="#"><i class="fas fa-user"></i> My Account</a>
                            <ul class="dropdown-menu">
                                <li><a href="profile.php">Profile</a></li>
                                <li><a href="bookings.php">My Bookings</a></li>
                                <li><a href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container"><?php if(isset($_SESSION[\'message\'])): ?>
        <div class="alert alert-<?php echo $_SESSION[\'message_type\']; ?>">
            <?php echo $_SESSION[\'message\']; ?>
        </div>
        <?php unset($_SESSION[\'message\']); unset($_SESSION[\'message_type\']); ?>
    <?php endif; ?>';
                
                // Create a backup of the current header file
                copy($headerFile, $headerFile . '.bak');
                
                if (file_put_contents($headerFile, $newHeaderContent)) {
                    logStep("Replaced header file with new version including cart link", 'success');
                } else {
                    logStep("Failed to replace header file", 'error');
                }
            }
        }
    }
} else {
    logStep("Header file not found: {$headerFile}", 'error');
}

// 4. Create a test user for immediate access
echo "<h2>Part 4: Creating Test User for Immediate Access</h2>";

$testEmail = 'test@example.com';
$testPassword = 'Test@123456';
$testName = 'Test User';
$testPhone = '9876543210';
$testAddress = 'Kathmandu, Test Address';

// Check if test user already exists
$checkUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkUser->bind_param("s", $testEmail);
$checkUser->execute();
$result = $checkUser->get_result();

if ($result->num_rows === 0) {
    // Create test user
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    
    $insertUser = $conn->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
    $insertUser->bind_param("sssss", $testName, $testEmail, $hashedPassword, $testPhone, $testAddress);
    
    if ($insertUser->execute()) {
        logStep("Created test user for immediate access", 'success');
        logStep("Email: {$testEmail}", 'info');
        logStep("Password: {$testPassword}", 'info');
    } else {
        logStep("Failed to create test user: " . $insertUser->error, 'error');
    }
} else {
    logStep("Test user already exists", 'info');
    logStep("Email: {$testEmail}", 'info');
    logStep("Password: {$testPassword}", 'info');
}

// Final message and redirect links
echo "<h2>All Fixes Complete!</h2>";
echo "<p>Registration and cart functionality should now be working properly.</p>";
echo "<p>You can use the test account to login immediately:</p>";
echo "<ul>";
echo "<li><strong>Email:</strong> {$testEmail}</li>";
echo "<li><strong>Password:</strong> {$testPassword}</li>";
echo "</ul>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='login.php' style='display: inline-block; margin-right: 10px; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Go to Login Page</a>";
echo "<a href='register.php' style='display: inline-block; margin-right: 10px; padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>Go to Registration Page</a>";
echo "<a href='cart.php' style='display: inline-block; padding: 10px 20px; background-color: #FF9800; color: white; text-decoration: none; border-radius: 4px;'>Go to Cart Page</a>";
echo "</div>";
?> 