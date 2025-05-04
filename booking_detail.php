<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to view booking details";
    $_SESSION['message_type'] = "error";
    redirect('login.php');
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Booking ID is required";
    $_SESSION['message_type'] = "error";
    redirect('bookings.php');
}

$bookingId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Get booking details
$sql = "SELECT b.*, ec.name as event_name, ec.image as event_image
        FROM bookings b
        JOIN event_categories ec ON b.event_category_id = ec.id
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['message'] = "Booking not found";
    $_SESSION['message_type'] = "error";
    redirect('bookings.php');
}

$booking = $result->fetch_assoc();

// Get services in this booking
$sql = "SELECT bd.*, s.name as service_name, s.description, s.image, sc.name as category_name
        FROM booking_details bd
        JOIN services s ON bd.service_id = s.id
        JOIN service_categories sc ON s.category_id = sc.id
        WHERE bd.booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$services = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Get payment details
$sql = "SELECT * FROM payments WHERE booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
?>

<div class="section-heading">
    <h2>Booking Details</h2>
</div>

<div class="booking-details-container" style="margin-bottom: 30px;">
    <div class="booking-header" style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
        <div class="booking-image" style="flex: 0 0 150px;">
            <img src="assets/images/event_categories/<?php echo basename($booking['event_image']); ?>" alt="<?php echo $booking['event_name']; ?>" style="width: 100%; height: auto; border-radius: 8px;">
        </div>
        <div class="booking-info">
            <h3><?php echo $booking['event_name']; ?></h3>
            <p><strong>Booking ID:</strong> #<?php echo $booking['id']; ?></p>
            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($booking['event_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['event_time'])); ?></p>
            <p><strong>Status:</strong> <span class="status-badge <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></p>
            <p><strong>Booked on:</strong> <?php echo date('F d, Y', strtotime($booking['created_at'])); ?></p>
        </div>
    </div>
    
    <div class="booking-sections" style="display: flex; flex-wrap: wrap; gap: 30px;">
        <div class="services-section" style="flex: 1; min-width: 300px;">
            <h3>Services</h3>
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 15px;">
                <?php if (empty($services)): ?>
                    <p>No services found for this booking.</p>
                <?php else: ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Category</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <img src="assets/images/services/<?php echo $service['image']; ?>" alt="<?php echo $service['service_name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 15px;">
                                        <div>
                                            <h4><?php echo $service['service_name']; ?></h4>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $service['category_name']; ?></td>
                                <td><?php echo formatPrice($service['price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="booking-total" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; text-align: right;">
                        <p><strong>Total:</strong> <?php echo formatPrice($booking['total_amount']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="payment-section" style="flex: 1; min-width: 300px;">
            <h3>Payment Information</h3>
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 15px;">
                <?php if (empty($payment)): ?>
                    <p>No payment information found for this booking.</p>
                <?php else: ?>
                    <p><strong>Amount:</strong> <?php echo formatPrice($payment['amount']); ?></p>
                    <p><strong>Method:</strong> <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></p>
                    <p><strong>Transaction ID:</strong> <?php echo $payment['transaction_id']; ?></p>
                    <p><strong>Status:</strong> <span class="status-badge <?php echo $payment['status']; ?>"><?php echo ucfirst($payment['status']); ?></span></p>
                    <p><strong>Payment Date:</strong> <?php echo date('F d, Y', strtotime($payment['created_at'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="booking-actions" style="margin-top: 30px; text-align: center;">
    <a href="bookings.php" class="btn btn-secondary">Back to Bookings</a>
    
    <?php if ($booking['status'] === 'pending'): ?>
        <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel Booking</a>
    <?php endif; ?>
</div>

<style>
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .pending {
        background-color: #FFC107;
        color: #212529;
    }
    
    .confirmed {
        background-color: #28A745;
        color: #fff;
    }
    
    .completed {
        background-color: #007BFF;
        color: #fff;
    }
    
    .cancelled {
        background-color: #DC3545;
        color: #fff;
    }
    
    .failed {
        background-color: #6C757D;
        color: #fff;
    }
</style>

<?php
require_once 'includes/footer.php';
?> 