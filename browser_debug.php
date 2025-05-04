<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Browser Image Debug</h1>";

// Check browser path
function checkImageUrl($path) {
    $full_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . 
                'nepali_event_booking/' . $path;
    
    // Get headers
    $headers = get_headers($full_url, 1);
    $status = $headers[0];
    
    echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Testing: {$path}</h3>";
    echo "<p><strong>Full URL:</strong> <a href='{$full_url}' target='_blank'>{$full_url}</a></p>";
    echo "<p><strong>Status:</strong> {$status}</p>";
    
    if (strpos($status, '200') !== false) {
        echo "<p style='color:green;'>✓ Image is accessible</p>";
        echo "<div style='padding: 10px; border: 1px solid #ddd;'>";
        echo "<img src='{$path}' style='max-width: 200px; max-height: 150px;' alt='Test Image'>";
        echo "</div>";
    } else {
        echo "<p style='color:red;'>✗ Image is NOT accessible</p>";
    }
    
    // Check if file exists on server
    $server_path = __DIR__ . '/' . $path;
    if (file_exists($server_path)) {
        echo "<p style='color:green;'>✓ File exists on server at: {$server_path}</p>";
        echo "<p><strong>Size:</strong> " . filesize($server_path) . " bytes</p>";
        echo "<p><strong>Permissions:</strong> " . substr(sprintf('%o', fileperms($server_path)), -4) . "</p>";
    } else {
        echo "<p style='color:red;'>✗ File does NOT exist on server at: {$server_path}</p>";
    }
    
    echo "</div>";
}

// Display images table
echo "<h2>Image Path Tests</h2>";

// Test both paths for each image
$images = array(
    'bratabandh.jpg',
    'marriage.jpg',
    'mehendi.jpg',
    'birthday.jpg'
);

foreach ($images as $image) {
    checkImageUrl("assets/images/{$image}");
    checkImageUrl("assets/images/event_categories/{$image}");
}

// Now let's check the events.php file to see what paths it's using
echo "<h2>Current Path in events.php</h2>";
if (file_exists('events.php')) {
    $content = file_get_contents('events.php');
    preg_match('/<img src="([^"]+)"/', $content, $matches);
    
    if (!empty($matches[1])) {
        echo "<p><strong>Current image path in code:</strong> {$matches[1]}</p>";
        $path = str_replace('<?php echo $event[\'image\']; ?>', 'bratabandh.jpg', $matches[1]);
        echo "<p><strong>Example full path:</strong> {$path}</p>";
        
        // Check if this file exists
        $server_path = __DIR__ . '/' . dirname($path) . '/bratabandh.jpg';
        if (file_exists($server_path)) {
            echo "<p style='color:green;'>✓ Sample file exists at: {$server_path}</p>";
        } else {
            echo "<p style='color:red;'>✗ Sample file does NOT exist at: {$server_path}</p>";
        }
    } else {
        echo "<p style='color:red;'>Could not find image path in events.php</p>";
    }
} else {
    echo "<p style='color:red;'>events.php file not found</p>";
}

// Fix suggestions
echo "<h2>Direct Fix</h2>";
echo "<p>Let's try to fix the issue by directly modifying events.php:</p>";

echo "<form method='post'>";
echo "<input type='hidden' name='action' value='fix_paths'>";
echo "<button type='submit' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer;'>Fix Image Paths Now</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fix_paths') {
    // 1. Make sure all image files are in both locations
    echo "<h3>Step 1: Ensuring images exist in both locations</h3>";
    
    foreach ($images as $image) {
        $source = "assets/images/event_categories/{$image}";
        $dest = "assets/images/{$image}";
        
        // Copy from event_categories to root if needed
        if (file_exists($source) && !file_exists($dest)) {
            if (copy($source, $dest)) {
                echo "<p style='color:green;'>✓ Copied {$image} to root images folder</p>";
                chmod($dest, 0644);
            }
        } 
        // Copy from root to event_categories if needed
        else if (file_exists($dest) && !file_exists($source)) {
            if (copy($dest, $source)) {
                echo "<p style='color:green;'>✓ Copied {$image} to event_categories folder</p>";
                chmod($source, 0644);
            }
        }
        // Both exist
        else if (file_exists($source) && file_exists($dest)) {
            echo "<p style='color:blue;'>ℹ {$image} exists in both locations</p>";
        }
        // Neither exists
        else {
            echo "<p style='color:red;'>✗ {$image} doesn't exist in either location!</p>";
        }
    }
    
    // 2. Try both approaches for image paths
    echo "<h3>Step 2: Updating image paths in PHP files</h3>";
    
    // First, update to use root path
    $files = ['events.php', 'event_detail.php', 'bookings.php', 'booking_detail.php'];
    foreach ($files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Try replacing event_categories path with root path
            $updated = str_replace(
                'src="assets/images/event_categories/', 
                'src="assets/images/', 
                $content
            );
            
            if ($content !== $updated) {
                if (file_put_contents($file, $updated)) {
                    echo "<p style='color:green;'>✓ Updated {$file} to use root image path</p>";
                }
            } else {
                echo "<p style='color:blue;'>ℹ No changes needed for {$file}</p>";
            }
        }
    }
    
    echo "<p style='margin-top: 20px;'><a href='events.php' style='padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none;'>Go to Events Page</a></p>";
}
?> 