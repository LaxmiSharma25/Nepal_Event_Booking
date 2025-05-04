<?php
// Script to download sample images for services

// Make sure the services directory exists
$servicesDir = __DIR__ . '/assets/images/services';
if (!file_exists($servicesDir)) {
    mkdir($servicesDir, 0777, true);
}

// Sample image URLs for photography services
$imageUrls = [
    'basic_photo.jpg' => 'https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
    'premium_photo.jpg' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
    // Add more image URLs as needed
];

// Download and save images
foreach ($imageUrls as $filename => $url) {
    $filePath = $servicesDir . '/' . $filename;
    
    // Check if file already exists
    if (file_exists($filePath)) {
        echo "File already exists: {$filename}<br>";
        continue;
    }
    
    // Download image
    $imageData = @file_get_contents($url);
    if ($imageData === false) {
        echo "Failed to download image: {$url}<br>";
        continue;
    }
    
    // Save image
    if (file_put_contents($filePath, $imageData) !== false) {
        echo "Successfully downloaded: {$filename}<br>";
    } else {
        echo "Failed to save image: {$filename}<br>";
    }
}

echo "<p>Image download process completed.</p>";
echo "<p><a href='services.php'>Go to Services Page</a></p>";
?> 