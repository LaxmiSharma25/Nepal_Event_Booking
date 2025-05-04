<?php
require_once 'config/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Cart Table Fix Utility</h2>";

// Check if cart table exists
$checkCartTable = $conn->query("SHOW TABLES LIKE 'cart'");
if ($checkCartTable->num_rows == 0) {
    echo "<p>Cart table does not exist. Creating it now...</p>";
    
    $createCartTable = "CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        booking_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($createCartTable)) {
        echo "<p>Cart table created successfully.</p>";
    } else {
        echo "<p>Error creating cart table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Cart table exists. Checking structure...</p>";
    
    // Check if quantity column exists
    $checkQuantityColumn = $conn->query("SHOW COLUMNS FROM cart LIKE 'quantity'");
    
    if ($checkQuantityColumn->num_rows > 0) {
        echo "<p>Found 'quantity' column. Removing it...</p>";
        
        // Try to drop the quantity column
        if ($conn->query("ALTER TABLE cart DROP COLUMN quantity")) {
            echo "<p>Successfully removed 'quantity' column.</p>";
        } else {
            echo "<p>Error removing 'quantity' column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Column 'quantity' not found. Table structure is correct.</p>";
    }
}

// Verify final table structure
echo "<h3>Current Cart Table Structure:</h3>";
$describeTable = $conn->query("DESCRIBE cart");

if ($describeTable) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $describeTable->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Error describing cart table: " . $conn->error . "</p>";
}

echo "<p>Cart table fixing completed.</p>";
echo "<p><a href='services.php'>Go to Services Page</a> | <a href='cart.php'>Go to Cart Page</a></p>";
?> 