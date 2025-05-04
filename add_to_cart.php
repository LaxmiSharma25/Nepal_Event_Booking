<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to add items to cart";
    $_SESSION['message_type'] = "error";
    redirect('login.php');
}

// Check if service ID is provided
if (!isset($_GET['service_id']) || empty($_GET['service_id'])) {
    $_SESSION['message'] = "Service ID is required";
    $_SESSION['message_type'] = "error";
    redirect('services.php');
}

$serviceId = (int)$_GET['service_id'];
$userId = $_SESSION['user_id'];
$redirectUrl = 'services.php';

// If event_id is provided, set redirect URL to event_detail.php
if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];
    $redirectUrl = "event_detail.php?id=$eventId";
}

// Check if service exists
$service = getServiceById($conn, $serviceId);
if (!$service) {
    $_SESSION['message'] = "Service not found";
    $_SESSION['message_type'] = "error";
    redirect($redirectUrl);
}

// Add service to cart
if (addToCart($conn, $userId, $serviceId)) {
    $_SESSION['message'] = "Service added to cart successfully";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Service is already in your cart";
    $_SESSION['message_type'] = "warning";
}

redirect($redirectUrl);
?> 