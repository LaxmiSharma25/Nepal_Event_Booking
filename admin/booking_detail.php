<?php
$pageTitle = "Booking Details";
require_once 'includes/header.php';

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No booking ID provided";
    $_SESSION['message_type'] = "error";
    redirect("bookings.php");
}

$bookingId = (int)$_GET['id'];

// Get booking details with customer and event info
$sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone, 
               u.address as user_address, ec.name as event_name, ec.description as event_description,
               p.payment_method, p.status as payment_status, p.transaction_id
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN event_categories ec ON b.event_category_id = ec.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Booking not found";
    $_SESSION['message_type'] = "error";
    redirect("bookings.php");
}

$booking = $result->fetch_assoc();

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    $newStatus = sanitize($_POST['status']);
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    
    if (in_array($newStatus, $validStatuses)) {
        $updateSql = "UPDATE bookings SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newStatus, $bookingId);
        
        if ($updateStmt->execute()) {
            // Also update payment status if booking status is completed
            if ($newStatus == 'completed') {
                $paymentSql = "UPDATE payments SET status = 'completed' WHERE booking_id = ?";
                $paymentStmt = $conn->prepare($paymentSql);
                $paymentStmt->bind_param("i", $bookingId);
                $paymentStmt->execute();
            }
            
            $_SESSION['message'] = "Booking status updated successfully";
            $_SESSION['message_type'] = "success";
            
            // Refresh current page to show updated status
            redirect("booking_detail.php?id=$bookingId");
        } else {
            $_SESSION['message'] = "Failed to update booking status";
            $_SESSION['message_type'] = "error";
        }
    }
}
?>

<div class="booking-detail-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h1 style="margin-bottom: 5px;">Booking #<?php echo $booking['id']; ?></h1>
        <p class="text-muted">Created on <?php echo date('F d, Y \a\t h:i A', strtotime($booking['created_at'])); ?></p>
    </div>
    <div>
        <a href="bookings.php" class="btn btn-outline" style="margin-right: 10px;">Back to Bookings</a>
    </div>
</div>

<div class="booking-detail-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div class="booking-main-info" style="background-color: #fff; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); padding: 20px;">
        <div class="booking-status" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <h3 style="margin-bottom: 15px;">Status</h3>
            <div class="status-container" style="display: flex; align-items: center; justify-content: space-between;">
                <span class="status-badge <?php echo $booking['status']; ?>" style="display: inline-block; padding: 8px 15px; border-radius: 3px; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">
                    <?php echo ucfirst($booking['status']); ?>
                </span>
                
                <form method="POST" style="display: flex; gap: 10px;">
                    <select name="status" class="form-control" style="padding: 8px; border-radius: 3px; border: 1px solid #ced4da;">
                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-primary" style="padding: 8px 15px;">Update Status</button>
                </form>
            </div>
        </div>
        
        <div class="booking-event-details" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <h3 style="margin-bottom: 15px;">Event Details</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div>
                    <p style="margin-bottom: 5px; color: #6c757d;">Event Type</p>
                    <p style="font-weight: 600;"><?php echo $booking['event_name']; ?></p>
                </div>
                <div>
                    <p style="margin-bottom: 5px; color: #6c757d;">Event Date & Time</p>
                    <p style="font-weight: 600;"><?php echo date('F d, Y', strtotime($booking['event_date'])); ?> at <?php echo date('h:i A', strtotime($booking['event_time'])); ?></p>
                </div>
                <div>
                    <p style="margin-bottom: 5px; color: #6c757d;">Location</p>
                    <p style="font-weight: 600;"><?php echo $booking['location']; ?></p>
                </div>
                <div>
                    <p style="margin-bottom: 5px; color: #6c757d;">Expected Guests</p>
                    <p style="font-weight: 600;"><?php echo $booking['guest_count']; ?> people</p>
                </div>
            </div>
            
            <?php if (!empty($booking['special_requests'])): ?>
                <div style="margin-top: 15px;">
                    <p style="margin-bottom: 5px; color: #6c757d;">Special Requests</p>
                    <p><?php echo nl2br($booking['special_requests']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="booking-payment" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <h3 style="margin-bottom: 15px;">Payment Information</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div>
                    <p style="margin-bottom: 5px; color: #6c757d;">Total Amount</p>
                    <p style="font-weight: 600; font-size: 1.2rem;">NPR <?php echo number_format($booking['total_amount'], 2); ?></p>
                </div>
                <div>
                    <p style="margin-bottom: 5px; color: #6c757d;">Payment Method</p>
                    <p style="font-weight: 600;"><?php echo ucfirst($booking['payment_method'] ?? 'Not specified'); ?></p>
                </div>
                <div>
                    <p style="margin-bottom: 5px; color: #6c757d;">Payment Status</p>
                    <span class="status-badge <?php echo $booking['payment_status'] ?? 'pending'; ?>" style="display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                        <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                    </span>
                </div>
                <?php if (!empty($booking['transaction_id'])): ?>
                    <div>
                        <p style="margin-bottom: 5px; color: #6c757d;">Transaction ID</p>
                        <p style="font-weight: 600;"><?php echo $booking['transaction_id']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="booking-customer-info" style="background-color: #fff; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); padding: 20px;">
        <h3 style="margin-bottom: 15px;">Customer Information</h3>
        <div style="margin-bottom: 20px;">
            <p style="margin-bottom: 5px; color: #6c757d;">Name</p>
            <p style="font-weight: 600; font-size: 1.1rem;"><?php echo $booking['user_name']; ?></p>
        </div>
        <div style="margin-bottom: 20px;">
            <p style="margin-bottom: 5px; color: #6c757d;">Email</p>
            <p style="font-weight: 600;"><?php echo $booking['user_email']; ?></p>
        </div>
        <div style="margin-bottom: 20px;">
            <p style="margin-bottom: 5px; color: #6c757d;">Phone</p>
            <p style="font-weight: 600;"><?php echo $booking['user_phone']; ?></p>
        </div>
        <?php if (!empty($booking['user_address'])): ?>
            <div style="margin-bottom: 20px;">
                <p style="margin-bottom: 5px; color: #6c757d;">Address</p>
                <p style="font-weight: 600;"><?php echo $booking['user_address']; ?></p>
            </div>
        <?php endif; ?>
        
        <a href="mailto:<?php echo $booking['user_email']; ?>" class="btn btn-primary" style="width: 100%; margin-top: 10px; margin-bottom: 10px;">
            <i class="fas fa-envelope" style="margin-right: 5px;"></i> Send Email
        </a>
        
        <a href="tel:<?php echo $booking['user_phone']; ?>" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-phone" style="margin-right: 5px;"></i> Call Customer
        </a>
    </div>
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
</style>

<?php
require_once 'includes/footer.php';
?> 