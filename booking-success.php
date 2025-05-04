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
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    setMessage("Invalid booking ID", "danger");
    header("Location: account.php");
    exit();
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$bookingQuery = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
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

// Empty the cart as purchase is complete
$clearCartQuery = "DELETE FROM cart WHERE user_id = ?";
$clearCartStmt = $conn->prepare($clearCartQuery);
$clearCartStmt->bind_param("i", $user_id);
$clearCartStmt->execute();

// Page title
$pageTitle = "Booking Success";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0 text-center"><i class="fas fa-check-circle me-2"></i> Booking Successful!</h3>
                </div>
                <div class="card-body text-center">
                    <?php echo displayMessage(); ?>
                    
                    <div class="py-4">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                        </div>
                        <h3>Thank You for Your Booking!</h3>
                        <p class="lead mb-1">Your booking has been confirmed.</p>
                        <p class="mb-4">Your booking ID is: <strong>#<?php echo $booking_id; ?></strong></p>
                        <p>A confirmation email has been sent to your registered email address.</p>
                        <p class="mb-0">You will receive all the details about your booking shortly.</p>
                    </div>
                    
                    <div class="bg-light p-3 rounded mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-start mb-2">
                                    <strong>Booking Date:</strong> <?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-start mb-2">
                                    <strong>Total Amount:</strong> <?php echo formatPrice($booking['total_amount']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-start mb-2">
                                    <strong>Payment Method:</strong> <?php echo ucfirst($booking['payment_method']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-start mb-2">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php echo ($booking['status'] == 'confirmed') ? 'success' : (($booking['status'] == 'pending') ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="booking-confirmation.php?id=<?php echo $booking_id; ?>" class="btn btn-primary">View Booking Details</a>
                        <a href="account.php" class="btn btn-outline-secondary">Go to My Account</a>
                        <a href="index.php" class="btn btn-outline-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">What's Next?</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="mb-2">
                                <i class="fas fa-envelope text-primary" style="font-size: 40px;"></i>
                            </div>
                            <h5>Check Your Email</h5>
                            <p class="small">A confirmation email has been sent with all details</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="mb-2">
                                <i class="fas fa-ticket-alt text-primary" style="font-size: 40px;"></i>
                            </div>
                            <h5>Your E-Tickets</h5>
                            <p class="small">E-tickets will be sent to your email soon</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="mb-2">
                                <i class="fas fa-user-circle text-primary" style="font-size: 40px;"></i>
                            </div>
                            <h5>Account Dashboard</h5>
                            <p class="small">Track all your bookings in your account area</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 