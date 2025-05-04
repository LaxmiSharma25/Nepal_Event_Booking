<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage("Please login to view booking details", "warning");
    header("Location: login.php");
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage("Invalid booking ID", "danger");
    header("Location: account.php");
    exit();
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$bookingQuery = "SELECT b.*, COUNT(bi.id) as total_items 
                FROM bookings b 
                LEFT JOIN booking_items bi ON b.id = bi.booking_id 
                WHERE b.id = ? AND b.user_id = ? 
                GROUP BY b.id";
$bookingStmt = $conn->prepare($bookingQuery);
$bookingStmt->bind_param("ii", $booking_id, $user_id);
$bookingStmt->execute();
$booking = $bookingStmt->get_result()->fetch_assoc();

// If booking not found or doesn't belong to user
if (!$booking) {
    setMessage("Booking not found or access denied", "danger");
    header("Location: account.php");
    exit();
}

// Get booking items
$itemsQuery = "SELECT bi.*, e.name as event_name, e.image, e.event_date, e.location 
              FROM booking_items bi 
              LEFT JOIN events e ON bi.event_id = e.id 
              WHERE bi.booking_id = ?";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->bind_param("i", $booking_id);
$itemsStmt->execute();
$bookingItems = $itemsStmt->get_result();

// Get user details
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Page title
$pageTitle = "Booking Confirmation";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-check-circle mr-2"></i> Booking Confirmed!</h3>
                </div>
                <div class="card-body">
                    <?php echo displayMessage(); ?>
                    
                    <div class="text-center mb-4">
                        <p class="lead">Thank you for your booking! Your booking has been confirmed and is being processed.</p>
                        <p>A confirmation email has been sent to <strong><?php echo $user['email']; ?></strong>.</p>
                        <h4 class="mt-3">Booking ID: #<?php echo $booking_id; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Booked Events</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bookingItems->num_rows > 0): ?>
                                    <?php while ($item = $bookingItems->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($item['image']): ?>
                                                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['event_name']; ?>" class="img-thumbnail mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo $item['event_name']; ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($item['event_date'])); ?></td>
                                            <td><?php echo $item['location']; ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No items found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Booking Details</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Booking Date:</span>
                            <strong><?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Status:</span>
                            <span class="badge bg-<?php echo ($booking['status'] == 'confirmed') ? 'success' : (($booking['status'] == 'pending') ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Payment Method:</span>
                            <strong><?php echo ucfirst($booking['payment_method']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Items:</span>
                            <strong><?php echo $booking['total_items']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Amount:</span>
                            <strong><?php echo formatPrice($booking['total_amount']); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Customer Information</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Name:</strong> <?php echo $user['name']; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Email:</strong> <?php echo $user['email']; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Phone:</strong> <?php echo $user['phone']; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Address:</strong> <?php echo $user['address']; ?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <a href="account.php" class="btn btn-primary">Go to My Account</a>
                <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 