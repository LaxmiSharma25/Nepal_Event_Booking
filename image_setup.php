<?php
// Image Fix Script
// This script creates placeholder images for missing event images

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

// Create directories if they don't exist
$directories = [
    'assets/images',
    'assets/images/event_categories',
    'assets/images/services'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Check if MySQL connection failed
if (!$conn || isset($conn->connect_error)) {
    // Create a fallback file of known event category images
    $knownCategories = [
        ['id' => 1, 'name' => 'Bratabandh', 'image' => 'bratabandh.jpg'],
        ['id' => 2, 'name' => 'Marriage', 'image' => 'marriage.jpg'],
        ['id' => 3, 'name' => 'Mehendi', 'image' => 'mehendi.jpg'],
        ['id' => 4, 'name' => 'Birthday', 'image' => 'birthday.jpg']
    ];
    
    // Use the fallback list
    $categories = $knownCategories;
    
    echo "<p>Using hardcoded event categories due to database connection issue.</p>";
} else {
    // Get event categories from database
    $sql = "SELECT * FROM event_categories";
    $result = $conn->query($sql);
    $categories = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}

// Check if marriage.jpg exists and has content
$defaultImageSource = 'assets/images/event_categories/marriage.jpg';
if (!file_exists($defaultImageSource) || filesize($defaultImageSource) < 1000) {
    // Create a simple colored image
    $img = imagecreatetruecolor(800, 600);
    $bgColor = imagecolorallocate($img, 29, 53, 87); // dark blue
    $textColor = imagecolorallocate($img, 255, 255, 255); // white
    
    imagefill($img, 0, 0, $bgColor);
    imagestring($img, 5, 300, 280, 'Event Image', $textColor);
    
    // Save the image
    imagejpeg($img, $defaultImageSource);
    imagedestroy($img);
    
    echo "<p>Created default image: $defaultImageSource</p>";
}

// Now create or fix all category images
foreach ($categories as $category) {
    $imageName = $category['image'];
    $imagePath = 'assets/images/event_categories/' . $imageName;
    
    // Skip if image already exists and has content
    if (file_exists($imagePath) && filesize($imagePath) > 1000) {
        echo "<p>Image exists and looks valid: $imagePath</p>";
        continue;
    }
    
    // Create a copy of the default image with the category name
    if (file_exists($defaultImageSource)) {
        copy($defaultImageSource, $imagePath);
        echo "<p>Created image for {$category['name']}: $imagePath</p>";
    } else {
        echo "<p>Error: Default image source not found: $defaultImageSource</p>";
    }
}

// Create default-event.jpg if it doesn't exist
$defaultImage = 'assets/images/event_categories/default-event.jpg';
if (!file_exists($defaultImage) || filesize($defaultImage) < 1000) {
    if (file_exists($defaultImageSource)) {
        copy($defaultImageSource, $defaultImage);
        echo "<p>Created default event image: $defaultImage</p>";
    }
}

// Create payment method images
$paymentImages = [
    'khalti.png',
    'khalti-large.png',
    'khalti-button.png',
    'bank.png',
    'cash.png',
    'payment-default.png'
];

// Create payment directory if it doesn't exist
if (!file_exists('assets/images/payment')) {
    mkdir('assets/images/payment', 0755, true);
}

foreach ($paymentImages as $paymentImage) {
    $imagePath = 'assets/images/payment/' . $paymentImage;
    
    // Skip if image already exists and has content
    if (file_exists($imagePath) && filesize($imagePath) > 1000) {
        echo "<p>Payment image exists: $imagePath</p>";
        continue;
    }
    
    // Create a simple colored image with text
    $img = imagecreatetruecolor(200, 80);
    
    // Choose color based on payment type
    if (strpos($paymentImage, 'khalti') !== false) {
        $bgColor = imagecolorallocate($img, 98, 9, 181); // Khalti purple
    } elseif (strpos($paymentImage, 'bank') !== false) {
        $bgColor = imagecolorallocate($img, 66, 133, 244); // blue
    } elseif (strpos($paymentImage, 'cash') !== false) {
        $bgColor = imagecolorallocate($img, 244, 180, 0); // yellow
    } else {
        $bgColor = imagecolorallocate($img, 100, 100, 100); // gray
    }
    
    $textColor = imagecolorallocate($img, 255, 255, 255); // white
    
    imagefill($img, 0, 0, $bgColor);
    
    // Text to display
    $text = str_replace(array('.png', '-large', '-button'), '', $paymentImage);
    $text = ucfirst($text);
    
    // Center the text
    $textWidth = imagefontwidth(5) * strlen($text);
    $textX = (int)((imagesx($img) - $textWidth) / 2);
    
    imagestring($img, 5, $textX, 30, $text, $textColor);
    
    // Save the image
    imagejpeg($img, $imagePath);
    imagedestroy($img);
    
    echo "<p>Created payment image: $imagePath</p>";
}

// Create service images
$serviceImages = [
    'basic_photo.jpg',
    'premium_photo.jpg',
    'small_hall.jpg',
    'large_hall.jpg',
    'basic_catering.jpg',
    'premium_catering.jpg',
    'basic_decor.jpg',
    'premium_decor.jpg'
];

foreach ($serviceImages as $serviceImage) {
    $imagePath = 'assets/images/services/' . $serviceImage;
    
    // Skip if image already exists and has content
    if (file_exists($imagePath) && filesize($imagePath) > 1000) {
        echo "<p>Service image exists: $imagePath</p>";
        continue;
    }
    
    // Create a simple colored image
    $img = imagecreatetruecolor(800, 600);
    
    // Different colors for different services
    if (strpos($serviceImage, 'photo') !== false) {
        $bgColor = imagecolorallocate($img, 41, 128, 185); // blue
    } elseif (strpos($serviceImage, 'hall') !== false) {
        $bgColor = imagecolorallocate($img, 142, 68, 173); // purple
    } elseif (strpos($serviceImage, 'catering') !== false) {
        $bgColor = imagecolorallocate($img, 230, 126, 34); // orange
    } else {
        $bgColor = imagecolorallocate($img, 39, 174, 96); // green
    }
    
    $textColor = imagecolorallocate($img, 255, 255, 255); // white
    
    imagefill($img, 0, 0, $bgColor);
    
    // Format the text nicely
    $text = str_replace('_', ' ', $serviceImage);
    $text = str_replace('.jpg', '', $text);
    $text = ucwords($text);
    
    // Center the text
    $font = 5; // Font size
    $textWidth = imagefontwidth($font) * strlen($text);
    $textX = (int)((imagesx($img) - $textWidth) / 2);
    
    imagestring($img, $font, $textX, 280, $text, $textColor);
    
    // Save the image
    imagejpeg($img, $imagePath);
    imagedestroy($img);
    
    echo "<p>Created service image: $imagePath</p>";
}

echo "<p>Image fix process complete. <a href='events.php'>Go to Events</a></p>";
?> 