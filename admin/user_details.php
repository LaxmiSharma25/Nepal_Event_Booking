<?php
$pageTitle = "User Details";
require_once 'includes/header.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage("User ID is required.", "error");
    header("Location: users.php");
    exit;
}

$userId = (int)$_GET['id'];

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setMessage("User not found.", "error");
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();

// Get user's bookings with service details
$bookingSql = "SELECT b.*, s.name as service_name, s.price, s.image, c.name as category_name 
               FROM bookings b 
               JOIN services s ON b.service_id = s.id 
               LEFT JOIN service_categories c ON s.category_id = c.id 
               WHERE b.user_id = ? 
               ORDER BY b.booking_date DESC, b.created_at DESC";
$bookingStmt = $conn->prepare($bookingSql);
$bookingStmt->bind_param("i", $userId);
$bookingStmt->execute();
$bookingResult = $bookingStmt->get_result();

$bookings = [];
if ($bookingResult->num_rows > 0) {
    while ($booking = $bookingResult->fetch_assoc()) {
        $bookings[] = $booking;
    }
}

// Calculate statistics
$totalBookings = count($bookings);
$totalAmountSpent = 0;
$upcomingBookings = 0;
$completedBookings = 0;
$cancelledBookings = 0;

foreach ($bookings as $booking) {
    $totalAmountSpent += $booking['total_amount'];
    
    if ($booking['status'] === 'confirmed' && strtotime($booking['booking_date']) >= strtotime('today')) {
        $upcomingBookings++;
    } elseif ($booking['status'] === 'completed') {
        $completedBookings++;
    } elseif ($booking['status'] === 'cancelled') {
        $cancelledBookings++;
    }
}

// Get pagination for bookings if needed
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalBookings / $limit);

// Get paginated bookings
$paginatedSql = "SELECT b.*, s.name as service_name, s.price, s.image, c.name as category_name 
                 FROM bookings b 
                 JOIN services s ON b.service_id = s.id 
                 LEFT JOIN service_categories c ON s.category_id = c.id 
                 WHERE b.user_id = ? 
                 ORDER BY b.booking_date DESC, b.created_at DESC
                 LIMIT ? OFFSET ?";
$paginatedStmt = $conn->prepare($paginatedSql);
$paginatedStmt->bind_param("iii", $userId, $limit, $offset);
$paginatedStmt->execute();
$paginatedResult = $paginatedStmt->get_result();

$paginatedBookings = [];
if ($paginatedResult->num_rows > 0) {
    while ($booking = $paginatedResult->fetch_assoc()) {
        $paginatedBookings[] = $booking;
    }
}
?>

<div class="admin-content">
    <div class="page-header">
        <h2>User Details</h2>
        <a href="users.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>

    <div class="user-profile">
        <div class="profile-header">
            <div class="profile-image">
                <?php if (!empty($user['profile_picture']) && file_exists("../uploads/profile_pictures/" . $user['profile_picture'])): ?>
                    <img src="../uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" alt="Profile Picture">
                <?php else: ?>
                    <div class="profile-initials">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p class="user-status status-<?php echo $user['status'] ? 'active' : 'inactive'; ?>">
                    <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                </p>
                <p class="user-since">Member since: <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
            </div>
            <div class="profile-actions">
                <a href="users.php?toggle_status=<?php echo $user['status'] ? 'inactive' : 'active'; ?>&id=<?php echo $user['id']; ?>" 
                   class="btn <?php echo $user['status'] ? 'btn-warning' : 'btn-success'; ?>" 
                   onclick="return confirm('Are you sure you want to <?php echo $user['status'] ? 'deactivate' : 'activate'; ?> this user?')">
                    <i class="fas fa-<?php echo $user['status'] ? 'times' : 'check'; ?>"></i> 
                    <?php echo $user['status'] ? 'Deactivate' : 'Activate'; ?> User
                </a>
            </div>
        </div>

        <div class="profile-details">
            <div class="details-section">
                <h4>Contact Information</h4>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-envelope"></i> Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-phone"></i> Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                </div>
            </div>

            <div class="details-section">
                <h4>Booking Statistics</h4>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $totalBookings; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $upcomingBookings; ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $completedBookings; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $cancelledBookings; ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">Rs <?php echo number_format($totalAmountSpent, 2); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking History -->
        <div class="booking-history">
            <h4>Booking History</h4>
            
            <?php if (count($paginatedBookings) > 0): ?>
                <div class="booking-list">
                    <?php foreach($paginatedBookings as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-image">
                                <?php if (!empty($booking['image']) && file_exists("../uploads/services/" . $booking['image'])): ?>
                                    <img src="../uploads/services/<?php echo $booking['image']; ?>" alt="<?php echo htmlspecialchars($booking['service_name']); ?>">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="booking-details">
                                <h5><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                                <p class="booking-category"><?php echo htmlspecialchars($booking['category_name']); ?></p>
                                <div class="booking-date-time">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($booking['booking_time'])); ?></span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-price">Rs <?php echo number_format($booking['total_amount'], 2); ?></div>
                                    <div class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="user_details.php?id=<?php echo $userId; ?>&page=<?php echo $page - 1; ?>" class="pagination-item">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="user_details.php?id=<?php echo $userId; ?>&page=<?php echo $i; ?>" 
                           class="pagination-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="user_details.php?id=<?php echo $userId; ?>&page=<?php echo $page + 1; ?>" class="pagination-item">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <p class="no-bookings">This user has no bookings.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.admin-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.user-profile {
    margin-top: 20px;
}

.profile-header {
    display: flex;
    align-items: center;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.profile-image {
    margin-right: 20px;
}

.profile-image img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
}

.profile-initials {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
}

.profile-info {
    flex: 1;
}

.profile-info h3 {
    margin: 0 0 10px 0;
    font-size: 1.5rem;
}

.user-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 8px;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.user-since {
    color: #6c757d;
    font-size: 0.9rem;
}

.profile-actions {
    margin-left: auto;
}

.profile-details {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.details-section {
    flex: 1;
    min-width: 300px;
    margin-bottom: 20px;
}

.details-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 10px;
}

.detail-item {
    margin-bottom: 10px;
    display: flex;
    flex-wrap: wrap;
}

.detail-label {
    flex: 0 0 100px;
    font-weight: 500;
    color: #495057;
}

.detail-value {
    flex: 1;
}

.stats-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.stat-card {
    flex: 1;
    min-width: 120px;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

.booking-history h4 {
    margin-top: 0;
    margin-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 10px;
}

.booking-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.booking-card {
    display: flex;
    background-color: #f8f9fa;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.booking-image {
    width: 150px;
    height: 120px;
}

.booking-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-placeholder {
    width: 100%;
    height: 100%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    font-size: 2rem;
}

.booking-details {
    flex: 1;
    padding: 15px;
    position: relative;
}

.booking-details h5 {
    margin-top: 0;
    margin-bottom: 5px;
    font-size: 1.1rem;
}

.booking-category {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.booking-date-time {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.booking-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.booking-price {
    font-weight: 600;
    font-size: 1.1rem;
}

.booking-status {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-confirmed {
    background-color: #cce5ff;
    color: #004085;
}

.status-completed {
    background-color: #d4edda;
    color: #155724;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.no-bookings {
    text-align: center;
    padding: 20px;
    color: #6c757d;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.pagination-item {
    padding: 8px 12px;
    margin: 0 5px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    text-decoration: none;
    color: #007bff;
}

.pagination-item:hover {
    background-color: #f8f9fa;
}

.pagination-item.active {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .profile-image {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .profile-actions {
        margin-left: 0;
        margin-top: 15px;
    }
    
    .booking-card {
        flex-direction: column;
    }
    
    .booking-image {
        width: 100%;
        height: 180px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 