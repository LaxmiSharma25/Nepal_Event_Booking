<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to view your bookings";
    $_SESSION['message_type'] = "error";
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get user's bookings
$sql = "SELECT b.*, ec.name as event_name, ec.image as event_image
        FROM bookings b
        JOIN event_categories ec ON b.event_category_id = ec.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
?>

<div class="section-heading">
    <h2>My Bookings</h2>
</div>

<?php if (empty($bookings)): ?>
    <div style="text-align: center; margin: 50px 0;">
        <p>You don't have any bookings yet.</p>
        <a href="events.php" class="btn btn-primary">Book an Event</a>
    </div>
<?php else: ?>
    <div class="card-container">
        <?php foreach ($bookings as $booking): ?>
        <div class="card booking-card">
            <div class="card-img">
                <img src="assets/images/event_categories/<?php echo basename($booking['event_image']); ?>" alt="<?php echo $booking['event_name']; ?>">
            </div>
            <div class="card-content">
                <h3><?php echo $booking['event_name']; ?></h3>
                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($booking['event_date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['event_time'])); ?></p>
                <p><strong>Amount:</strong> <?php echo formatPrice($booking['total_amount']); ?></p>
                <p><strong>Status:</strong> <span class="status-badge <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></p>
                <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

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