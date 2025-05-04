<?php
echo "<h1>Direct Image Fix</h1>";

// 1. We'll manually copy the images without any complex code
$images = array(
    'bratabandh.jpg',
    'marriage.jpg',
    'mehendi.jpg',
    'birthday.jpg'
);

echo "<h2>Step 1: Copying main event images</h2>";
foreach ($images as $image) {
    $source = "assets/images/event_categories/{$image}";
    $dest = "assets/images/{$image}";
    
    if (file_exists($source)) {
        if (copy($source, $dest)) {
            echo "<p style='color:green'>✓ Successfully copied {$image}</p>";
            // Make sure file is readable
            chmod($dest, 0644);
        } else {
            echo "<p style='color:red'>✗ Failed to copy {$image}</p>";
        }
    } else {
        echo "<p style='color:orange'>! Source file not found: {$source}</p>";
    }
}

// 2. Display the files in the destination directory
echo "<h2>Step 2: Verifying files in root images directory</h2>";
if (is_dir("assets/images")) {
    $files = scandir("assets/images");
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != "." && $file != ".." && !is_dir("assets/images/{$file}")) {
            echo "<li>{$file} - " . filesize("assets/images/{$file}") . " bytes</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>Root images directory does not exist!</p>";
}

// 3. Now let's test if the images are accessible
echo "<h2>Step 3: Testing image accessibility</h2>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
foreach ($images as $image) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; text-align: center;'>";
    echo "<p>{$image}</p>";
    echo "<img src='assets/images/{$image}' style='max-width: 200px; max-height: 150px;' alt='{$image}'>";
    echo "</div>";
}
echo "</div>";

// 4. Update your PHP files back to use the root directory
echo "<h2>Step 4: Updating PHP files</h2>";

function updateFile($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $pattern = 'src="assets/images/event_categories/';
        $replacement = 'src="assets/images/';
        
        if (strpos($content, $pattern) !== false) {
            $updatedContent = str_replace($pattern, $replacement, $content);
            if (file_put_contents($file, $updatedContent)) {
                echo "<p style='color:green'>✓ Updated {$file}</p>";
                return true;
            } else {
                echo "<p style='color:red'>✗ Failed to update {$file}</p>";
            }
        } else {
            echo "<p style='color:blue'>ℹ No changes needed in {$file}</p>";
        }
    } else {
        echo "<p style='color:red'>✗ File not found: {$file}</p>";
    }
    return false;
}

updateFile('events.php');
updateFile('event_detail.php');
updateFile('bookings.php');
updateFile('booking_detail.php');

echo "<h2>All Done!</h2>";
echo "<p>The images should now display correctly.</p>";
echo "<p><a href='events.php' style='padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Go to Events Page</a></p>";
?> 