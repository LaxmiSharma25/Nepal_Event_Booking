<?php
// Simple image fix script
echo "<h2>Fixing Images</h2>";

// Create root images directory if it doesn't exist
if (!is_dir('assets/images')) {
    mkdir('assets/images', 0755, true);
    echo "<p>Created assets/images directory</p>";
}

// Copy all images from event_categories to root images folder
$sourceDir = 'assets/images/event_categories';
$destDir = 'assets/images';

if (is_dir($sourceDir)) {
    $files = scandir($sourceDir);
    $count = 0;
    
    echo "<h3>Copying images:</h3>";
    echo "<ul>";
    
    foreach ($files as $file) {
        // Skip . and .. directories and non-image files
        if ($file === '.' || $file === '..' || !preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            continue;
        }
        
        $sourcePath = $sourceDir . '/' . $file;
        $destPath = $destDir . '/' . $file;
        
        if (copy($sourcePath, $destPath)) {
            echo "<li>Copied: $file</li>";
            $count++;
        } else {
            echo "<li style='color:red;'>Failed to copy: $file</li>";
        }
    }
    
    echo "</ul>";
    echo "<p>Copied $count images</p>";
} else {
    echo "<p style='color:red;'>Source directory does not exist: $sourceDir</p>";
    
    // Try to create the directory
    if (mkdir($sourceDir, 0755, true)) {
        echo "<p>Created source directory: $sourceDir</p>";
    }
}

// Now let's update events.php to point to the correct location
$eventsFile = 'events.php';
if (file_exists($eventsFile)) {
    $content = file_get_contents($eventsFile);
    $updatedContent = str_replace(
        'src="assets/images/event_categories/', 
        'src="assets/images/', 
        $content
    );
    
    if (file_put_contents($eventsFile, $updatedContent)) {
        echo "<p>Updated events.php file</p>";
    } else {
        echo "<p style='color:red;'>Failed to update events.php file</p>";
    }
} else {
    echo "<p style='color:red;'>Events file not found: $eventsFile</p>";
}

// Now let's update event_detail.php
$eventDetailFile = 'event_detail.php';
if (file_exists($eventDetailFile)) {
    $content = file_get_contents($eventDetailFile);
    $updatedContent = str_replace(
        'src="assets/images/event_categories/', 
        'src="assets/images/', 
        $content
    );
    
    if (file_put_contents($eventDetailFile, $updatedContent)) {
        echo "<p>Updated event_detail.php file</p>";
    } else {
        echo "<p style='color:red;'>Failed to update event_detail.php file</p>";
    }
} else {
    echo "<p style='color:red;'>Event detail file not found: $eventDetailFile</p>";
}

// Now let's update bookings.php
$bookingsFile = 'bookings.php';
if (file_exists($bookingsFile)) {
    $content = file_get_contents($bookingsFile);
    $updatedContent = str_replace(
        'src="assets/images/event_categories/', 
        'src="assets/images/', 
        $content
    );
    
    if (file_put_contents($bookingsFile, $updatedContent)) {
        echo "<p>Updated bookings.php file</p>";
    } else {
        echo "<p style='color:red;'>Failed to update bookings.php file</p>";
    }
} else {
    echo "<p style='color:red;'>Bookings file not found: $bookingsFile</p>";
}

// Now let's update booking_detail.php
$bookingDetailFile = 'booking_detail.php';
if (file_exists($bookingDetailFile)) {
    $content = file_get_contents($bookingDetailFile);
    $updatedContent = str_replace(
        'src="assets/images/event_categories/', 
        'src="assets/images/', 
        $content
    );
    
    if (file_put_contents($bookingDetailFile, $updatedContent)) {
        echo "<p>Updated booking_detail.php file</p>";
    } else {
        echo "<p style='color:red;'>Failed to update booking_detail.php file</p>";
    }
} else {
    echo "<p style='color:red;'>Booking detail file not found: $bookingDetailFile</p>";
}

echo "<h3>All Done!</h3>";
echo "<p>The images should now display correctly. <a href='events.php'>Click here to go to Events page</a> to check if it's working.</p>";
?> 