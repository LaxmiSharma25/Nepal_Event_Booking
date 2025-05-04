<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to book events";
    $_SESSION['message_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Check if service ID is provided
if (!isset($_GET['service_id']) || empty($_GET['service_id'])) {
    $_SESSION['message'] = "Service ID is required";
    $_SESSION['message_type'] = "error";
    header("Location: services.php");
    exit;
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
    header("Location: $redirectUrl");
    exit;
}

// Add to cart - note that we remove quantity since it's not in the cart table
$query = "INSERT INTO cart (user_id, service_id) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $userId, $serviceId);

if ($stmt->execute()) {
    $_SESSION['message'] = "Service booked successfully and added to cart";
    $_SESSION['message_type'] = "success";
    header("Location: cart.php");
    exit;
} else {
    $_SESSION['message'] = "Failed to book service: " . $conn->error;
    $_SESSION['message_type'] = "danger";
    header("Location: $redirectUrl");
    exit;
}
?> 