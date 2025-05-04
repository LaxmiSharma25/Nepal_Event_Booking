<?php
// Include necessary files
require_once 'config/db.php';
require_once 'config/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please login to view your orders.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    $_SESSION['message'] = "Invalid order information.";
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Extract booking IDs from order ID (Format: ORDER-{timestamp}-{user_id}-{booking_ids})
$order_parts = explode('-', $order_id);
if (count($order_parts) < 4) {
    $_SESSION['message'] = "Invalid order information.";
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit;
}

// Get booking IDs from the order ID
$booking_ids_part = implode('-', array_slice($order_parts, 3));
$booking_ids = explode(',', $booking_ids_part);

// Validate that these bookings belong to the current user
$valid_bookings = [];
foreach ($booking_ids as $booking_id) {
    if (!is_numeric($booking_id)) {
        continue;
    }
    
    $sql = "SELECT b.*, s.name as title, s.price, s.image 
            FROM bookings b 
            JOIN services s ON b.service_id = s.id 
            WHERE b.id = ? AND b.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_bookings[] = $result->fetch_assoc();
    }
}

// Include header
$pageTitle = "Order Confirmation";
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12 text-center mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
            <h1 class="mt-3">Thank You for Your Order!</h1>
            <p class="lead">Your payment has been processed successfully.</p>
            <p>Order ID: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0">Order Details</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        foreach ($valid_bookings as $booking): 
                            $total += $booking['price'];
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($booking['image'])): ?>
                                        <img src="assets/images/services/<?php echo htmlspecialchars($booking['image']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="me-3 text-muted" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                                            <i class="bi bi-camera" style="font-size: 1.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($booking['title']); ?></h6>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('F d, Y', strtotime($booking['event_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($booking['event_time'])); ?></td>
                            <td>Rs. <?php echo number_format($booking['price']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th>Rs. <?php echo number_format($total); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 text-center mb-4">
            <a href="index.php" class="btn btn-primary me-2">Continue Shopping</a>
            <a href="bookings.php" class="btn btn-outline-primary">View All Bookings</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 