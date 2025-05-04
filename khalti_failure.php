<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log the failure response for debugging
$log_file = 'khalti_logs.txt';
$log_data = date("Y-m-d H:i:s") . " - Failure Response:\n";
$log_data .= "GET data: " . print_r($_GET, true) . "\n";
$log_data .= "POST data: " . print_r($_POST, true) . "\n";
$log_data .= "----------------------------------------------------\n";
file_put_contents($log_file, $log_data, FILE_APPEND);

// Get transaction details from GET parameters
if (isset($_GET['transaction_uuid']) && !empty($_GET['transaction_uuid'])) {
    $order_id = $_GET['transaction_uuid'];
    $message = $_GET['message'] ?? 'Payment was unsuccessful.';
    
    // Log the specific error message
    error_log("Khalti Payment Failed: {$message} | Order: {$order_id}");
}
// If not in GET params, check SESSION
else if (isset($_SESSION['current_order_id']) && !empty($_SESSION['current_order_id'])) {
    $order_id = $_SESSION['current_order_id'];
    unset($_SESSION['current_order_id']);
    $message = 'Payment was cancelled or unsuccessful.';
}
// If still not found, redirect to cart
else {
    $_SESSION['message'] = "Payment was unsuccessful. Please try again or choose a different payment method.";
    $_SESSION['message_type'] = "danger";
    header("Location: cart.php");
    exit;
}

// Extract booking IDs from order ID (Format: ORDER-{timestamp}-{user_id}-{booking_ids})
$order_parts = explode('-', $order_id);
if (count($order_parts) < 4) {
    $_SESSION['message'] = "Invalid order information.";
    $_SESSION['message_type'] = "danger";
    header("Location: cart.php");
    exit;
}

// Get booking IDs from the order ID
$booking_ids_part = implode('-', array_slice($order_parts, 3));
$booking_ids = explode(',', $booking_ids_part);

// Update bookings to cancelled status
if (!empty($booking_ids)) {
    foreach ($booking_ids as $booking_id) {
        if (is_numeric($booking_id)) {
            $update_query = "UPDATE bookings SET 
                            status = 'cancelled'
                            WHERE id = ?";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        }
    }
}

// Use the specific message if available, otherwise use generic message
if (isset($message)) {
    $_SESSION['message'] = "Payment failed: " . $message . ". Please try again or choose a different payment method.";
} else {
    $_SESSION['message'] = "Payment was unsuccessful. Please try again or choose a different payment method.";
}
$_SESSION['message_type'] = "danger";

// Redirect to cart page so the user can try again
header("Location: cart.php");
exit;
?> 