<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to manage your cart";
    $_SESSION['message_type'] = "error";
    redirect('login.php');
}

// Check if cart ID is provided
if (!isset($_GET['cart_id']) || empty($_GET['cart_id'])) {
    $_SESSION['message'] = "Cart ID is required";
    $_SESSION['message_type'] = "error";
    redirect('cart.php');
}

$cartId = (int)$_GET['cart_id'];
$userId = $_SESSION['user_id'];

// Verify the cart item belongs to the logged in user
$sql = "SELECT * FROM cart WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cartId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['message'] = "Invalid cart item";
    $_SESSION['message_type'] = "error";
    redirect('cart.php');
}

// Remove from cart
if (removeFromCart($conn, $cartId)) {
    $_SESSION['message'] = "Item removed from cart successfully";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to remove item from cart";
    $_SESSION['message_type'] = "error";
}

redirect('cart.php');
?> 