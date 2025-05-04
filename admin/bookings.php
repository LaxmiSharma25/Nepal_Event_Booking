<?php
$pageTitle = "Manage Bookings";
require_once 'includes/header.php';

// Filter by status if provided
$statusFilter = '';
$statusParam = '';
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = sanitize($_GET['status']);
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($status, $validStatuses)) {
        $statusFilter = " WHERE b.status = '$status'";
        $statusParam = "&status=$status";
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$countSql = "SELECT COUNT(*) as total FROM bookings b" . $statusFilter;
$countResult = $conn->query($countSql);
$totalCount = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalCount / $perPage);

// Get bookings with pagination
$sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone, ec.name as event_name 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN event_categories ec ON b.event_category_id = ec.id
        $statusFilter
        ORDER BY b.created_at DESC
        LIMIT $offset, $perPage";
$result = $conn->query($sql);
$bookings = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Handle status update
if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $bookingId = (int)$_GET['id'];
    $newStatus = sanitize($_GET['status']);
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    
    if (in_array($newStatus, $validStatuses)) {
        $updateSql = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $newStatus, $bookingId);
        
        if ($stmt->execute()) {
            // Also update payment status if booking status is completed
            if ($newStatus == 'completed') {
                $paymentSql = "UPDATE payments SET status = 'completed' WHERE booking_id = ?";
                $paymentStmt = $conn->prepare($paymentSql);
                $paymentStmt->bind_param("i", $bookingId);
                $paymentStmt->execute();
            }
            
            $_SESSION['message'] = "Booking status updated successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to update booking status";
            $_SESSION['message_type'] = "error";
        }
        
        redirect("bookings.php?page=$page" . $statusParam);
    }
}
?>

<div class="booking-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="status-filters">
        <a href="bookings.php" class="btn <?php echo !isset($_GET['status']) ? 'btn-primary' : 'btn-outline'; ?>" style="margin-right: 10px;">All</a>
        <a href="bookings.php?status=pending" class="btn <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'btn-primary' : 'btn-outline'; ?>" style="margin-right: 10px;">Pending</a>
        <a href="bookings.php?status=confirmed" class="btn <?php echo isset($_GET['status']) && $_GET['status'] == 'confirmed' ? 'btn-primary' : 'btn-outline'; ?>" style="margin-right: 10px;">Confirmed</a>
        <a href="bookings.php?status=completed" class="btn <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'btn-primary' : 'btn-outline'; ?>" style="margin-right: 10px;">Completed</a>
        <a href="bookings.php?status=cancelled" class="btn <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'btn-primary' : 'btn-outline'; ?>">Cancelled</a>
    </div>
    
    <div class="bookings-count">
        <p>Showing <?php echo count($bookings); ?> of <?php echo $totalCount; ?> bookings</p>
    </div>
</div>

<div class="bookings-container" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
    <?php if (empty($bookings)): ?>
        <p>No bookings found.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">ID</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">Customer</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">Event</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">Date & Time</th>
                        <th style="padding: 10px; text-align: right; border-bottom: 1px solid #dee2e6;">Amount</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">Status</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">Booked On</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">#<?php echo $booking['id']; ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                <?php echo $booking['user_name']; ?><br>
                                <small><?php echo $booking['user_email']; ?></small><br>
                                <small><?php echo $booking['user_phone']; ?></small>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo $booking['event_name']; ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                <?php echo date('M d, Y', strtotime($booking['event_date'])); ?><br>
                                <small><?php echo date('h:i A', strtotime($booking['event_time'])); ?></small>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">NPR <?php echo number_format($booking['total_amount'], 2); ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <span class="status-badge <?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <?php echo date('M d, Y', strtotime($booking['created_at'])); ?><br>
                                <small><?php echo date('h:i A', strtotime($booking['created_at'])); ?></small>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <div class="dropdown" style="position: relative; display: inline-block;">
                                    <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">
                                        Actions <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="dropdown-content" style="display: none; position: absolute; right: 0; min-width: 120px; background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 1; border-radius: 5px; overflow: hidden;">
                                        <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" style="display: block; padding: 8px 10px; color: #333; text-decoration: none; font-size: 0.9rem;">View Details</a>
                                        
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="bookings.php?action=update_status&id=<?php echo $booking['id']; ?>&status=confirmed&page=<?php echo $page . $statusParam; ?>" style="display: block; padding: 8px 10px; color: #28a745; text-decoration: none; font-size: 0.9rem;" onclick="return confirm('Are you sure you want to confirm this booking?')">Confirm</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['status'] == 'confirmed'): ?>
                                            <a href="bookings.php?action=update_status&id=<?php echo $booking['id']; ?>&status=completed&page=<?php echo $page . $statusParam; ?>" style="display: block; padding: 8px 10px; color: #007bff; text-decoration: none; font-size: 0.9rem;" onclick="return confirm('Are you sure you want to mark this booking as completed?')">Complete</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                            <a href="bookings.php?action=update_status&id=<?php echo $booking['id']; ?>&status=cancelled&page=<?php echo $page . $statusParam; ?>" style="display: block; padding: 8px 10px; color: #dc3545; text-decoration: none; font-size: 0.9rem;" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top: 20px; text-align: center;">
                <?php if ($page > 1): ?>
                    <a href="bookings.php?page=<?php echo ($page - 1) . $statusParam; ?>" class="btn btn-outline" style="margin-right: 5px;">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="bookings.php?page=<?php echo $i . $statusParam; ?>" class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-outline'; ?>" style="margin-right: 5px;"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="bookings.php?page=<?php echo ($page + 1) . $statusParam; ?>" class="btn btn-outline">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
    
    .dropdown:hover .dropdown-content {
        display: block;
    }
    
    .dropdown-content a:hover {
        background-color: #f8f9fa;
    }
</style>

<?php
require_once 'includes/footer.php';
?> 