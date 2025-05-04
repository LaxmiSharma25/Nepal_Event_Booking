<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

echo "<h1>Complete System Fix</h1>";
echo "<p>This will fix both your image display issues and restore any cart functionality.</p>";

// Fix function to log all steps
function logStep($message, $type = 'info') {
    $color = 'black';
    $icon = 'ℹ️';
    
    if ($type == 'success') {
        $color = 'green';
        $icon = '✅';
    } else if ($type == 'error') {
        $color = 'red';
        $icon = '❌';
    } else if ($type == 'warning') {
        $color = 'orange';
        $icon = '⚠️';
    }
    
    echo "<p style='color:{$color};'>{$icon} {$message}</p>";
}

// 1. First, check the image status and fix
echo "<h2>Part 1: Fixing Image Display</h2>";

// 1.1 Verify/create image directories
logStep("Checking image directories...");
$directories = ['assets/images', 'assets/images/event_categories', 'assets/images/services'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            logStep("Created directory: {$dir}", 'success');
        } else {
            logStep("Failed to create directory: {$dir}", 'error');
        }
    } else {
        logStep("Directory exists: {$dir}", 'success');
    }
    
    // Make sure directory is writable
    if (!is_writable($dir)) {
        chmod($dir, 0755);
        logStep("Updated permissions for {$dir}", 'success');
    }
}

// 1.2 Set up image associations from database
$imageMap = [
    'Bratabandh' => 'bratabandh.jpg',
    'Marriage' => 'marriage.jpg',
    'Mehendi' => 'mehendi.jpg',
    'Birthday' => 'birthday.jpg'
];

// 1.3 Get current image paths from database
logStep("Checking image paths in database...");
$sql = "SELECT id, name, image FROM event_categories";
$result = $conn->query($sql);
$categories = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
        logStep("Found category: {$row['name']} with image: {$row['image']}");
    }
} else {
    logStep("No categories found in database!", 'warning');
}

// 1.4 Copy images to both directories and update database
logStep("Processing image files...");
foreach ($categories as $category) {
    $simpleImage = basename($category['image']);
    $expectedImage = isset($imageMap[$category['name']]) ? $imageMap[$category['name']] : $simpleImage;
    
    // First, check for existing image files
    $rootPath = "assets/images/{$simpleImage}";
    $categoryPath = "assets/images/event_categories/{$simpleImage}";
    $expectedRootPath = "assets/images/{$expectedImage}";
    $expectedCategoryPath = "assets/images/event_categories/{$expectedImage}";
    
    // Check for files at any of these locations
    $sourceFile = null;
    if (file_exists($rootPath) && filesize($rootPath) > 1000) {
        $sourceFile = $rootPath;
        logStep("Found image at {$rootPath}", 'success');
    } else if (file_exists($categoryPath) && filesize($categoryPath) > 1000) {
        $sourceFile = $categoryPath;
        logStep("Found image at {$categoryPath}", 'success');
    } else if (file_exists($expectedRootPath) && filesize($expectedRootPath) > 1000) {
        $sourceFile = $expectedRootPath;
        logStep("Found expected image at {$expectedRootPath}", 'success');
    } else if (file_exists($expectedCategoryPath) && filesize($expectedCategoryPath) > 1000) {
        $sourceFile = $expectedCategoryPath;
        logStep("Found expected image at {$expectedCategoryPath}", 'success');
    }
    
    // If source file found, ensure it's in both directories with the correct name
    if ($sourceFile) {
        // Make sure the file is in both directories with expected name
        if (!file_exists($expectedRootPath) || filesize($expectedRootPath) < 1000) {
            if (copy($sourceFile, $expectedRootPath)) {
                chmod($expectedRootPath, 0644);
                logStep("Copied to {$expectedRootPath}", 'success');
            } else {
                logStep("Failed to copy to {$expectedRootPath}", 'error');
            }
        }
        
        if (!file_exists($expectedCategoryPath) || filesize($expectedCategoryPath) < 1000) {
            if (copy($sourceFile, $expectedCategoryPath)) {
                chmod($expectedCategoryPath, 0644);
                logStep("Copied to {$expectedCategoryPath}", 'success');
            } else {
                logStep("Failed to copy to {$expectedCategoryPath}", 'error');
            }
        }
        
        // Update database if needed
        if ($category['image'] !== $expectedImage) {
            $updateSql = "UPDATE event_categories SET image = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $expectedImage, $category['id']);
            
            if ($stmt->execute()) {
                logStep("Updated database: {$category['name']} image to {$expectedImage}", 'success');
            } else {
                logStep("Failed to update database for {$category['name']}", 'error');
            }
        }
    } else {
        // No source file found, create a placeholder
        logStep("No image found for {$category['name']}, creating placeholder", 'warning');
        
        // Create a simple colored image
        $img = imagecreatetruecolor(800, 600);
        $bgColor = imagecolorallocate($img, 29, 53, 87); // dark blue
        $textColor = imagecolorallocate($img, 255, 255, 255); // white
        
        imagefill($img, 0, 0, $bgColor);
        $text = $category['name'] . " Event";
        
        // Center the text
        $font = 5; // Font size
        $textWidth = imagefontwidth($font) * strlen($text);
        $textX = (int)((imagesx($img) - $textWidth) / 2);
        
        imagestring($img, $font, $textX, 280, $text, $textColor);
        
        // Save to both locations
        imagejpeg($img, $expectedRootPath);
        imagejpeg($img, $expectedCategoryPath);
        chmod($expectedRootPath, 0644);
        chmod($expectedCategoryPath, 0644);
        
        imagedestroy($img);
        
        logStep("Created placeholder image for {$category['name']}", 'success');
        
        // Update database
        $updateSql = "UPDATE event_categories SET image = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $expectedImage, $category['id']);
        
        if ($stmt->execute()) {
            logStep("Updated database with new image name: {$expectedImage}", 'success');
        } else {
            logStep("Failed to update database", 'error');
        }
    }
}

// 1.5 Update PHP files to use correct image paths
logStep("Updating PHP files to use consistent image paths...");

// Fix all PHP files that reference images
$files_to_update = [
    'events.php' => 'src="assets/images/event_categories/',
    'event_detail.php' => 'src="assets/images/event_categories/',
    'bookings.php' => 'src="assets/images/event_categories/',
    'booking_detail.php' => 'src="assets/images/event_categories/'
];

foreach ($files_to_update as $file => $search) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $updated = str_replace($search, 'src="assets/images/', $content);
        
        if ($content !== $updated) {
            if (file_put_contents($file, $updated)) {
                logStep("Updated {$file} to use correct image paths", 'success');
            } else {
                logStep("Failed to update {$file}", 'error');
            }
        } else {
            logStep("No changes needed in {$file}");
        }
    } else {
        logStep("File not found: {$file}", 'warning');
    }
}

// 2. Fix cart functionality
echo "<h2>Part 2: Restoring Cart Functionality</h2>";

// 2.1 Verify cart table exists
logStep("Checking cart table...");
$checkCartTable = $conn->query("SHOW TABLES LIKE 'cart'");
if ($checkCartTable->num_rows == 0) {
    // Create cart table if it doesn't exist
    $createCartTable = "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($createCartTable)) {
        logStep("Created cart table", 'success');
    } else {
        logStep("Failed to create cart table: " . $conn->error, 'error');
    }
} else {
    logStep("Cart table exists", 'success');
}

// 2.2 Check for add_to_cart.php file
logStep("Checking cart files...");
if (!file_exists('add_to_cart.php')) {
    // Create add_to_cart.php file
    $addToCartContent = '<?php
require_once \'includes/header.php\';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION[\'message\'] = "Please login to add items to cart";
    $_SESSION[\'message_type\'] = "error";
    redirect(\'login.php\');
}

// Check if service ID is provided
if (!isset($_GET[\'service_id\']) || empty($_GET[\'service_id\'])) {
    $_SESSION[\'message\'] = "Service ID is required";
    $_SESSION[\'message_type\'] = "error";
    redirect(\'events.php\');
}

$serviceId = (int)$_GET[\'service_id\'];
$userId = $_SESSION[\'user_id\'];
$eventId = isset($_GET[\'event_id\']) ? (int)$_GET[\'event_id\'] : 0;

// Add item to cart
if (addToCart($conn, $userId, $serviceId)) {
    $_SESSION[\'message\'] = "Item added to cart successfully";
    $_SESSION[\'message_type\'] = "success";
} else {
    $_SESSION[\'message\'] = "Item already exists in cart";
    $_SESSION[\'message_type\'] = "error";
}

// Redirect back
if ($eventId > 0) {
    redirect(\'event_detail.php?id=\' . $eventId);
} else {
    redirect(\'cart.php\');
}
?>';
    
    if (file_put_contents('add_to_cart.php', $addToCartContent)) {
        logStep("Created add_to_cart.php file", 'success');
    } else {
        logStep("Failed to create add_to_cart.php file", 'error');
    }
}

// 2.3 Check for cart.php file
if (!file_exists('cart.php')) {
    // Create cart.php file
    $cartContent = '<?php
require_once \'includes/header.php\';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION[\'message\'] = "Please login to view your cart";
    $_SESSION[\'message_type\'] = "error";
    redirect(\'login.php\');
}

$userId = $_SESSION[\'user_id\'];

// Handle item removal
if (isset($_GET[\'remove\']) && isset($_GET[\'id\'])) {
    $cartId = (int)$_GET[\'id\'];
    
    if (removeFromCart($conn, $cartId)) {
        $_SESSION[\'message\'] = "Item removed from cart";
        $_SESSION[\'message_type\'] = "success";
    } else {
        $_SESSION[\'message\'] = "Failed to remove item from cart";
        $_SESSION[\'message_type\'] = "error";
    }
    
    redirect(\'cart.php\');
}

// Get cart items
$cartItems = getCartItems($conn, $userId);
$cartTotal = getCartTotal($conn, $userId);
?>

<div class="section-heading">
    <h2>Shopping Cart</h2>
</div>

<?php if (empty($cartItems)): ?>
    <div style="text-align: center; margin: 50px 0;">
        <p>Your cart is empty.</p>
        <a href="events.php" class="btn btn-primary">Browse Events</a>
    </div>
<?php else: ?>
    <div class="cart-container" style="max-width: 900px; margin: 0 auto 50px auto;">
        <table class="cart-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f7f7f7; text-align: left;">
                    <th style="padding: 15px;">Service</th>
                    <th style="padding: 15px;">Category</th>
                    <th style="padding: 15px;">Price</th>
                    <th style="padding: 15px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px;">
                        <div style="display: flex; align-items: center;">
                            <img src="assets/images/services/<?php echo $item[\'image\']; ?>" alt="<?php echo $item[\'name\']; ?>" style="width: 80px; height: 60px; object-fit: cover; margin-right: 15px; border-radius: 5px;">
                            <div>
                                <h4 style="margin: 0 0 5px 0;"><?php echo $item[\'name\']; ?></h4>
                                <p style="margin: 0; color: #777; font-size: 0.9rem;"><?php echo substr($item[\'description\'], 0, 50) . (strlen($item[\'description\']) > 50 ? \'...\' : \'\'); ?></p>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 15px;"><?php echo $item[\'category_name\']; ?></td>
                    <td style="padding: 15px;"><?php echo formatPrice($item[\'price\']); ?></td>
                    <td style="padding: 15px;">
                        <a href="cart.php?remove=true&id=<?php echo $item[\'cart_id\']; ?>" class="btn btn-outline btn-sm" onclick="return confirm(\'Are you sure you want to remove this item?\');">Remove</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f7f7f7;">
                    <td colspan="2" style="padding: 15px; text-align: right;"><strong>Total:</strong></td>
                    <td style="padding: 15px;"><strong><?php echo formatPrice($cartTotal); ?></strong></td>
                    <td style="padding: 15px;"></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="cart-actions" style="margin-top: 20px; text-align: right;">
            <a href="events.php" class="btn btn-outline">Continue Shopping</a>
            <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
        </div>
    </div>
<?php endif; ?>

<?php
require_once \'includes/footer.php\';
?>';
    
    if (file_put_contents('cart.php', $cartContent)) {
        logStep("Created cart.php file", 'success');
    } else {
        logStep("Failed to create cart.php file", 'error');
    }
}

// 2.4 Check for remove_from_cart.php
if (!file_exists('remove_from_cart.php')) {
    $removeFromCartContent = '<?php
require_once \'includes/header.php\';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION[\'message\'] = "Please login to manage your cart";
    $_SESSION[\'message_type\'] = "error";
    redirect(\'login.php\');
}

// Check if cart ID is provided
if (!isset($_GET[\'id\']) || empty($_GET[\'id\'])) {
    $_SESSION[\'message\'] = "Cart ID is required";
    $_SESSION[\'message_type\'] = "error";
    redirect(\'cart.php\');
}

$cartId = (int)$_GET[\'id\'];

// Remove item from cart
if (removeFromCart($conn, $cartId)) {
    $_SESSION[\'message\'] = "Item removed from cart successfully";
    $_SESSION[\'message_type\'] = "success";
} else {
    $_SESSION[\'message\'] = "Failed to remove item from cart";
    $_SESSION[\'message_type\'] = "error";
}

redirect(\'cart.php\');
?>';
    
    if (file_put_contents('remove_from_cart.php', $removeFromCartContent)) {
        logStep("Created remove_from_cart.php file", 'success');
    } else {
        logStep("Failed to create remove_from_cart.php file", 'error');
    }
}

// Add cart link to header
logStep("Checking for cart link in header...");
$headerFile = 'includes/header.php';
if (file_exists($headerFile)) {
    $headerContent = file_get_contents($headerFile);
    
    // Check if cart link is missing
    if (strpos($headerContent, 'cart.php') === false) {
        // Add cart link before logout link or at end of navigation
        $pattern = '<li><a href="logout.php">Logout</a></li>';
        $replacement = '<li><a href="cart.php">Cart</a></li>' . "\n                    " . $pattern;
        
        $updatedHeader = str_replace($pattern, $replacement, $headerContent);
        
        if ($headerContent !== $updatedHeader) {
            if (file_put_contents($headerFile, $updatedHeader)) {
                logStep("Added cart link to header", 'success');
            } else {
                logStep("Failed to update header with cart link", 'error');
            }
        } else {
            logStep("Couldn't find the right place to add cart link", 'warning');
        }
    } else {
        logStep("Cart link already exists in header", 'success');
    }
} else {
    logStep("Header file not found", 'error');
}

// Final message and redirect link
echo "<h2>All Fixes Complete!</h2>";
echo "<p>Your site should now have working images and cart functionality.</p>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='events.php' style='display: inline-block; margin-right: 10px; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Go to Events Page</a>";
echo "<a href='cart.php' style='display: inline-block; padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>Go to Cart Page</a>";
echo "</div>";
?> 