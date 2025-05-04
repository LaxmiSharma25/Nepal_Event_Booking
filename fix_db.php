<?php
// Connect directly to MySQL without using the db.php file
$servername = "localhost";
$username = "root";
$password = "";
$socket = "/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock";

echo "<h1>Database Fix Script</h1>";

try {
    // Connect without database first using socket
    $conn = new mysqli($servername, $username, $password, "", null, $socket);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>Successfully connected to MySQL server.</p>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS nepali_event_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Database 'nepali_event_booking' created or already exists.</p>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db("nepali_event_booking");
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'users' created or already exists.</p>";
    } else {
        throw new Exception("Error creating users table: " . $conn->error);
    }
    
    // Create events table
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        event_date DATE,
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'events' created or already exists.</p>";
    } else {
        throw new Exception("Error creating events table: " . $conn->error);
    }
    
    // Create cart table
    $sql = "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        event_id INT NOT NULL,
        booking_id INT,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'cart' created or already exists.</p>";
    } else {
        throw new Exception("Error creating cart table: " . $conn->error);
    }
    
    // Create bookings table
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        booking_date DATETIME NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        payment_method VARCHAR(50),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'bookings' created or already exists.</p>";
    } else {
        throw new Exception("Error creating bookings table: " . $conn->error);
    }
    
    // Create booking_items table
    $sql = "CREATE TABLE IF NOT EXISTS booking_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        event_id INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Table 'booking_items' created or already exists.</p>";
    } else {
        throw new Exception("Error creating booking_items table: " . $conn->error);
    }
    
    // Check if we have any events, if not insert some sample data
    $result = $conn->query("SELECT COUNT(*) as count FROM events");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "<p>Adding sample events...</p>";
        
        $events = [
            [
                'name' => 'Wedding Ceremony',
                'description' => 'Traditional Nepali wedding ceremony with all arrangements',
                'price' => 35000,
                'image' => 'assets/images/marriage.jpg',
                'event_date' => '2023-12-15',
                'location' => 'Kathmandu'
            ],
            [
                'name' => 'Birthday Party',
                'description' => 'Birthday celebration with decorations, cake and entertainment',
                'price' => 15000,
                'image' => 'assets/images/birthday.jpg',
                'event_date' => '2023-11-20',
                'location' => 'Pokhara'
            ],
            [
                'name' => 'Bratabandha Ceremony',
                'description' => 'Traditional Bratabandha ceremony with all arrangements',
                'price' => 25000,
                'image' => 'assets/images/bratabandh.jpg',
                'event_date' => '2023-12-10',
                'location' => 'Bhaktapur'
            ],
            [
                'name' => 'Mehendi Ceremony',
                'description' => 'Traditional Mehendi ceremony with all arrangements',
                'price' => 12000,
                'image' => 'assets/images/mehendi.jpg',
                'event_date' => '2023-11-25',
                'location' => 'Lalitpur'
            ]
        ];
        
        foreach ($events as $event) {
            $sql = "INSERT INTO events (name, description, price, image, event_date, location) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdsss", 
                $event['name'], 
                $event['description'], 
                $event['price'], 
                $event['image'], 
                $event['event_date'], 
                $event['location']
            );
            
            if ($stmt->execute()) {
                echo "<p>Added event: " . $event['name'] . "</p>";
            } else {
                echo "<p>Error adding event: " . $stmt->error . "</p>";
            }
        }
    }
    
    // Create a test user if it doesn't exist
    $email = 'test@example.com';
    $password = 'Test@123456';
    $name = 'Test User';
    $phone = '9876543210';
    $address = 'Kathmandu, Nepal';
    
    $result = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($result->num_rows == 0) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $phone, $address);
        
        if ($stmt->execute()) {
            echo "<p>Created test user account:<br>Email: $email<br>Password: $password</p>";
        } else {
            echo "<p>Error creating test user: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>Test user already exists (Email: $email, Password: $password)</p>";
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='login.php' style='display: inline-block; margin-right: 10px; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Go to Login Page</a>";
    echo "<a href='register.php' style='display: inline-block; margin-right: 10px; padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>Go to Registration Page</a>";
    echo "<a href='cart.php' style='display: inline-block; padding: 10px 20px; background-color: #FF9800; color: white; text-decoration: none; border-radius: 4px;'>Go to Cart Page</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 