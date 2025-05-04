<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to proceed to checkout";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php");
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

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

// Check if cart is empty
if (empty($cartItems)) {
    $_SESSION['message'] = "Your cart is empty. Please add services before checkout.";
    $_SESSION['message_type'] = "warning";
    header("Location: cart.php");
    exit;
}

// Process each cart item as a booking
$success = true;
$bookingIds = [];

// Get today's date for setting event_date
$today = date('Y-m-d');
// Get current time for setting event_time
$current_time = date('H:i:s');

foreach ($cartItems as $item) {
    // Create a new booking
    $bookingQuery = "INSERT INTO bookings (user_id, service_id, event_category_id, event_date, event_time, total_amount, status, created_at) 
                    VALUES (?, ?, 1, ?, ?, ?, 'pending', NOW())";
    $bookingStmt = $conn->prepare($bookingQuery);
    
    // Use price directly since quantity is always 1
    $price = $item['price'];
    
    $bookingStmt->bind_param("iissd", $user_id, $item['service_id'], $today, $current_time, $price);
    
    if ($bookingStmt->execute()) {
        $booking_id = $conn->insert_id;
        $bookingIds[] = $booking_id;
    } else {
        $success = false;
        break;
    }
}

if ($success) {
    // Store booking IDs in session for checkout
    $_SESSION['current_checkout_bookings'] = $bookingIds;
    
    // Clear cart after successfully creating bookings
    $clearCartQuery = "DELETE FROM cart WHERE user_id = ?";
    $clearCartStmt = $conn->prepare($clearCartQuery);
    $clearCartStmt->bind_param("i", $user_id);
    $clearCartStmt->execute();
    
    // Redirect to checkout
    header("Location: checkout.php");
    exit;
} else {
    $_SESSION['message'] = "Failed to create bookings. Please try again.";
    $_SESSION['message_type'] = "danger";
    header("Location: cart.php");
    exit;
}
?> 