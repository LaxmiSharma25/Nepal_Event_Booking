<?php
require_once 'config/db.php';

// Get event categories
$sql = "SELECT id, name, image FROM event_categories";
$result = $conn->query($sql);

echo "<h2>Database Values for Event Categories</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f2f2f2;'><th>ID</th><th>Name</th><th>Image Path in DB</th></tr>";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['image'] . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Create a stronger fix
echo "<h2>Ultimate Fix</h2>";
echo "<p>This will fix the issue by creating the proper directory structure and placing images correctly:</p>";

echo "<form method='post'>";
echo "<input type='hidden' name='action' value='ultimate_fix'>";
echo "<button type='submit' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer;'>Apply Ultimate Fix</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ultimate_fix') {
    echo "<h3>Step 1: Creating consistent directory structure</h3>";
    
    // Check or create directories
    $directories = ['assets/images', 'assets/images/event_categories'];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color:green;'>✓ Created directory: {$dir}</p>";
            } else {
                echo "<p style='color:red;'>✗ Failed to create directory: {$dir}</p>";
            }
        } else {
            echo "<p style='color:blue;'>ℹ Directory already exists: {$dir}</p>";
        }
    }
    
    echo "<h3>Step 2: Ensuring images exist</h3>";
    
    $images = [
        'bratabandh.jpg' => 'Bratabandh',
        'marriage.jpg' => 'Marriage',
        'mehendi.jpg' => 'Mehendi',
        'birthday.jpg' => 'Birthday'
    ];
    
    // Update database to use simple paths
    $sql = "SELECT id, name, image FROM event_categories";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $simpleImage = basename($row['image']);
            
            // Copy image files to both locations
            $sourcePath = null;
            $destPath = null;
            
            // Check if file exists in event_categories
            if (file_exists("assets/images/event_categories/{$simpleImage}")) {
                $sourcePath = "assets/images/event_categories/{$simpleImage}";
                $destPath = "assets/images/{$simpleImage}";
            } 
            // Check if file exists in root images
            else if (file_exists("assets/images/{$simpleImage}")) {
                $sourcePath = "assets/images/{$simpleImage}";
                $destPath = "assets/images/event_categories/{$simpleImage}";
            }
            
            // If found in either location, copy to the other
            if ($sourcePath && !file_exists($destPath)) {
                if (copy($sourcePath, $destPath)) {
                    echo "<p style='color:green;'>✓ Copied {$simpleImage} to {$destPath}</p>";
                    chmod($destPath, 0644);
                } else {
                    echo "<p style='color:red;'>✗ Failed to copy {$simpleImage}</p>";
                }
            }
            
            // Update database to use simple image name
            if ($row['image'] != $simpleImage) {
                $updateSql = "UPDATE event_categories SET image = ? WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("si", $simpleImage, $row['id']);
                
                if ($stmt->execute()) {
                    echo "<p style='color:green;'>✓ Updated database path for {$row['name']} to {$simpleImage}</p>";
                } else {
                    echo "<p style='color:red;'>✗ Failed to update database for {$row['name']}</p>";
                }
            }
        }
    }
    
    echo "<h3>Step 3: Creating placeholder images if needed</h3>";
    
    // Create placeholder images if needed
    foreach ($images as $filename => $title) {
        $imagePath = "assets/images/{$filename}";
        $categoryPath = "assets/images/event_categories/{$filename}";
        
        // If image doesn't exist in either location, create a placeholder
        if (!file_exists($imagePath) && !file_exists($categoryPath)) {
            // Create a simple colored image
            $img = imagecreatetruecolor(800, 600);
            $bgColor = imagecolorallocate($img, 29, 53, 87); // dark blue
            $textColor = imagecolorallocate($img, 255, 255, 255); // white
            
            imagefill($img, 0, 0, $bgColor);
            $text = $title . " Event";
            
            // Center the text
            $font = 5; // Font size
            $textWidth = imagefontwidth($font) * strlen($text);
            $textX = (int)((imagesx($img) - $textWidth) / 2);
            
            imagestring($img, $font, $textX, 280, $text, $textColor);
            
            // Save to both locations
            imagejpeg($img, $imagePath);
            copy($imagePath, $categoryPath);
            
            imagedestroy($img);
            
            echo "<p style='color:green;'>✓ Created placeholder image for {$title}</p>";
        }
    }
    
    echo "<h3>Step 4: Updating image paths in PHP files</h3>";
    
    // Update events.php to use simple paths
    $events_file = 'events.php';
    if (file_exists($events_file)) {
        $content = file_get_contents($events_file);
        
        // Replace with simple path
        $updated = str_replace(
            'src="assets/images/event_categories/<?php echo $event[\'image\']; ?>"', 
            'src="assets/images/<?php echo $event[\'image\']; ?>"', 
            $content
        );
        
        if (file_put_contents($events_file, $updated)) {
            echo "<p style='color:green;'>✓ Updated {$events_file} to use simple paths</p>";
        } else {
            echo "<p style='color:red;'>✗ Failed to update {$events_file}</p>";
        }
    }
    
    // Do the same for other files
    $other_files = ['event_detail.php', 'bookings.php', 'booking_detail.php'];
    foreach ($other_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Replace with simple path
            $updated = str_replace(
                'src="assets/images/event_categories/', 
                'src="assets/images/', 
                $content
            );
            
            if (file_put_contents($file, $updated)) {
                echo "<p style='color:green;'>✓ Updated {$file} to use simple paths</p>";
            } else {
                echo "<p style='color:red;'>✗ Failed to update {$file}</p>";
            }
        }
    }
    
    echo "<h3>All Done!</h3>";
    echo "<p style='margin-top: 20px;'><a href='events.php' style='padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none;'>Go to Events Page</a></p>";
}
?> 