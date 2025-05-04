<?php
// Set PHP error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Service Images Setup</h2>";

// Make sure the services directory exists
$servicesDir = __DIR__ . '/assets/images/services';
if (!file_exists($servicesDir)) {
    if (mkdir($servicesDir, 0777, true)) {
        echo "<p>Created services directory at: {$servicesDir}</p>";
    } else {
        echo "<p>Failed to create services directory!</p>";
    }
} else {
    echo "<p>Services directory exists at: {$servicesDir}</p>";
}

// Sample image URLs for all services
$imageUrls = [
    'basic_photo.jpg' => 'https://images.pexels.com/photos/3373207/pexels-photo-3373207.jpeg?auto=compress&cs=tinysrgb&w=600',
    'premium_photo.jpg' => 'https://images.pexels.com/photos/3379907/pexels-photo-3379907.jpeg?auto=compress&cs=tinysrgb&w=600',
    'small_hall.jpg' => 'https://images.pexels.com/photos/169193/pexels-photo-169193.jpeg?auto=compress&cs=tinysrgb&w=600',
    'large_hall.jpg' => 'https://images.pexels.com/photos/2306278/pexels-photo-2306278.jpeg?auto=compress&cs=tinysrgb&w=600',
    'basic_catering.jpg' => 'https://images.pexels.com/photos/5908226/pexels-photo-5908226.jpeg?auto=compress&cs=tinysrgb&w=600',
    'premium_catering.jpg' => 'https://images.pexels.com/photos/5409661/pexels-photo-5409661.jpeg?auto=compress&cs=tinysrgb&w=600',
    'basic_decor.jpg' => 'https://images.pexels.com/photos/1729783/pexels-photo-1729783.jpeg?auto=compress&cs=tinysrgb&w=600',
    'premium_decor.jpg' => 'https://images.pexels.com/photos/3243027/pexels-photo-3243027.jpeg?auto=compress&cs=tinysrgb&w=600'
];

// Attempt to use cURL instead of file_get_contents
function downloadImage($url, $filePath) {
    $ch = curl_init($url);
    $fp = fopen($filePath, 'wb');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $success = curl_exec($ch);
    
    if (!$success) {
        echo "<p>cURL Error: " . curl_error($ch) . "</p>";
    }
    
    curl_close($ch);
    fclose($fp);
    
    return $success && filesize($filePath) > 0;
}

// First try copying using cURL, then fall back to file_get_contents
foreach ($imageUrls as $filename => $url) {
    $filePath = $servicesDir . '/' . $filename;
    
    echo "<p>Processing {$filename} from {$url}...</p>";
    
    if (file_exists($filePath) && filesize($filePath) > 0) {
        echo "<p>File already exists with size: " . filesize($filePath) . " bytes</p>";
        continue;
    }
    
    // First try using cURL
    if (function_exists('curl_init')) {
        echo "<p>Attempting download using cURL...</p>";
        $success = downloadImage($url, $filePath);
        
        if ($success) {
            echo "<p>Successfully downloaded {$filename} using cURL: " . filesize($filePath) . " bytes</p>";
            continue;
        }
    }
    
    // Fall back to file_get_contents
    echo "<p>Attempting download using file_get_contents...</p>";
    $imageData = @file_get_contents($url);
    if ($imageData !== false) {
        if (file_put_contents($filePath, $imageData) !== false) {
            echo "<p>Successfully downloaded {$filename} using file_get_contents: " . filesize($filePath) . " bytes</p>";
        } else {
            echo "<p>Failed to save image: {$filename}</p>";
        }
    } else {
        echo "<p>Failed to download image: {$url}</p>";
        
        // For demonstration purposes, create a placeholder image if download fails
        echo "<p>Creating a placeholder image...</p>";
        $placeholderImg = imagecreatetruecolor(600, 400);
        $bgColor = imagecolorallocate($placeholderImg, 200, 200, 200);
        $textColor = imagecolorallocate($placeholderImg, 0, 0, 0);
        imagefill($placeholderImg, 0, 0, $bgColor);
        imagestring($placeholderImg, 5, 150, 180, 'Placeholder for ' . $filename, $textColor);
        imagejpeg($placeholderImg, $filePath, 90);
        imagedestroy($placeholderImg);
        
        echo "<p>Created placeholder image: " . filesize($filePath) . " bytes</p>";
    }
}

// Verify all images exist and have content
echo "<h3>Image Verification</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Image</th><th>Size</th><th>Status</th></tr>";

foreach ($imageUrls as $filename => $url) {
    $filePath = $servicesDir . '/' . $filename;
    $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
    $status = $fileSize > 0 ? "OK" : "MISSING";
    
    echo "<tr>";
    echo "<td>{$filename}</td>";
    echo "<td>{$fileSize} bytes</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p>Image setup process completed.</p>";
echo "<p><a href='services.php'>Go to Services Page</a></p>";
?> 