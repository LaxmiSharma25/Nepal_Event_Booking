<?php
require_once 'config/db.php';

// Get event categories
$sql = "SELECT id, name, image FROM event_categories";
$result = $conn->query($sql);

echo "<h2>Event Categories Images</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Image Path</th><th>Full Path</th><th>File Exists?</th></tr>";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $imagePath = "assets/images/" . $row['image'];
        $fullImagePath = "assets/images/event_categories/" . $row['image'];
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['image'] . "</td>";
        echo "<td>" . $fullImagePath . "</td>";
        echo "<td>" . (file_exists($fullImagePath) ? "Yes" : "No") . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Now check if simple path exists
echo "<h2>Checking Short Paths</h2>";
echo "<table border='1'>";
echo "<tr><th>Image</th><th>Direct Path Exists?</th><th>Subfolder Path Exists?</th></tr>";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $directPath = "assets/images/" . $row['image'];
        $subfolderPath = "assets/images/event_categories/" . $row['image'];
        
        echo "<tr>";
        echo "<td>" . $row['image'] . "</td>";
        echo "<td>" . (file_exists($directPath) ? "Yes" : "No") . "</td>";
        echo "<td>" . (file_exists($subfolderPath) ? "Yes" : "No") . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Update all image paths in the database to include the subfolder
echo "<h2>Fix Options</h2>";
echo "<p>You can click one of the buttons below to fix image paths:</p>";

echo "<form method='post'>";
echo "<button type='submit' name='action' value='copy_to_root'>Copy Images to Root Folder</button> ";
echo "<button type='submit' name='action' value='update_db'>Update Database to Use Subfolder</button>";
echo "</form>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'copy_to_root') {
        // Copy images from event_categories to the root images folder
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo "<h3>Copying Images to Root:</h3>";
            echo "<ul>";
            
            while($row = $result->fetch_assoc()) {
                $source = "assets/images/event_categories/" . $row['image'];
                $dest = "assets/images/" . $row['image'];
                
                if (file_exists($source)) {
                    if (copy($source, $dest)) {
                        echo "<li>Copied " . $row['image'] . " to root images folder</li>";
                    } else {
                        echo "<li>Failed to copy " . $row['image'] . "</li>";
                    }
                } else {
                    echo "<li>Source file not found: " . $source . "</li>";
                }
            }
            
            echo "</ul>";
            echo "<p>Done copying files. <a href='events.php'>Go to Events</a></p>";
        }
    } elseif ($action === 'update_db') {
        // Update database to include event_categories in image paths
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo "<h3>Updating Database:</h3>";
            echo "<ul>";
            
            while($row = $result->fetch_assoc()) {
                $newPath = "event_categories/" . $row['image'];
                $updateSql = "UPDATE event_categories SET image = ? WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("si", $newPath, $row['id']);
                
                if ($stmt->execute()) {
                    echo "<li>Updated " . $row['name'] . " image path to " . $newPath . "</li>";
                } else {
                    echo "<li>Failed to update " . $row['name'] . ": " . $conn->error . "</li>";
                }
            }
            
            echo "</ul>";
            echo "<p>Done updating database. <a href='events.php'>Go to Events</a></p>";
        }
    }
}
?> 