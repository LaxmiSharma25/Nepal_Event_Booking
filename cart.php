<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to view your cart";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php");
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Handle remove item from cart
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $removeQuery = "DELETE FROM cart WHERE user_id = ? AND id = ?";
    $stmt = $conn->prepare($removeQuery);
    $stmt->bind_param("ii", $user_id, $cart_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Item removed from cart";
        $_SESSION['message_type'] = "success";
        header("Location: cart.php");
        exit;
    } else {
        $_SESSION['message'] = "Failed to remove item from cart";
        $_SESSION['message_type'] = "danger";
    }
}

// Check if cart table exists, if not create it
$checkCartTable = $conn->query("SHOW TABLES LIKE 'cart'");
if ($checkCartTable->num_rows == 0) {
    $createCartTable = "CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        booking_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )";
    
    $conn->query($createCartTable);
}

// Get cart items
$query = "SELECT c.*, s.name as service_name, s.price, s.image, s.description
          FROM cart c 
          LEFT JOIN services s ON c.service_id = s.id 
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = $result->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    // Set default quantity to 1 if not in database
    $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
    if (isset($item['price'])) {
        $total += $item['price'] * $quantity;
    }
}

// Handle checkout process
if (isset($_POST['checkout']) && !empty($cartItems)) {
    // Process each cart item as a booking
    $success = true;
    $bookingIds = [];
    
    foreach ($cartItems as $item) {
        // Check if booking already exists
        if (!empty($item['booking_id'])) {
            $bookingIds[] = $item['booking_id'];
            continue;
        }
        
        // Create a new booking
        $bookingQuery = "INSERT INTO bookings (user_id, service_id, quantity, status, total_price, booking_date) 
                        VALUES (?, ?, 1, 'pending', ?, NOW())";
        $bookingStmt = $conn->prepare($bookingQuery);
        // Use price directly since quantity is always 1
        $price = $item['price'];
        $status = 'pending';
        $bookingStmt->bind_param("iid", $user_id, $item['service_id'], $price);
        
        if ($bookingStmt->execute()) {
            $booking_id = $conn->insert_id;
            $bookingIds[] = $booking_id;
            
            // Update cart with booking ID
            $updateCartQuery = "UPDATE cart SET booking_id = ? WHERE id = ?";
            $updateCartStmt = $conn->prepare($updateCartQuery);
            $updateCartStmt->bind_param("ii", $booking_id, $item['id']);
            $updateCartStmt->execute();
        } else {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        // Clear cart after successful checkout
        $clearCartQuery = "DELETE FROM cart WHERE user_id = ?";
        $clearCartStmt = $conn->prepare($clearCartQuery);
        $clearCartStmt->bind_param("i", $user_id);
        $clearCartStmt->execute();
        
        $_SESSION['message'] = "Checkout completed successfully! Your booking IDs: " . implode(", ", $bookingIds);
        $_SESSION['message_type'] = "success";
        header("Location: bookings.php");
        exit;
    } else {
        $_SESSION['message'] = "Failed to complete checkout";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<div class="container">
    <h1>Your Cart</h1>
    
    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            <p>Your cart is empty.</p>
            <a href="events.php" class="btn btn-primary">Browse Events</a>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($cartItems as $item): ?>
                <div class="card mb-3">
                    <div class="row g-0">
                        <div class="col-md-2">
                            <?php if (!empty($item['image'])): ?>
                                <img src="assets/images/services/<?php echo $item['image']; ?>" class="img-fluid rounded-start" alt="<?php echo htmlspecialchars($item['service_name'] ?? 'Service'); ?>">
                            <?php else: ?>
                                <div class="text-center pt-4">
                                    <i class="fas fa-camera fa-4x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['service_name'] ?? 'Service ' . $item['service_id']); ?></h5>
                                <p class="card-text">
                                    <?php if (!empty($item['description'])): ?>
                                        <strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?><br>
                                    <?php endif; ?>
                                    <strong>Price:</strong> Rs. <?php echo number_format($item['price'] ?? 0, 2); ?><br>
                                    <strong>Quantity:</strong> 1<br>
                                    <strong>Subtotal:</strong> Rs. <?php echo number_format($item['price'] ?? 0, 2); ?>
                                </p>
                                <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <p class="card-text">
                        <strong>Total Items:</strong> <?php echo count($cartItems); ?><br>
                        <strong>Total Price:</strong> Rs. <?php echo number_format($total, 2); ?>
                    </p>
                    <form method="post">
                        <a href="create_bookings.php" class="btn btn-success">Proceed to Checkout</a>
                        <a href="events.php" class="btn btn-primary">Continue Shopping</a>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?> 