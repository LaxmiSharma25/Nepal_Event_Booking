<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';

// Get statistics for dashboard
// Total users
$sql = "SELECT COUNT(*) as total_users FROM users";
$result = $conn->query($sql);
$totalUsers = $result->fetch_assoc()['total_users'];

// Bookings by status
$sql = "SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
        FROM bookings";
$result = $conn->query($sql);
$bookingStats = $result->fetch_assoc();
$totalBookings = $bookingStats['total_bookings'];
$pendingBookings = $bookingStats['pending_bookings'];
$confirmedBookings = $bookingStats['confirmed_bookings'];
$completedBookings = $bookingStats['completed_bookings'];
$cancelledBookings = $bookingStats['cancelled_bookings'];

// Revenue statistics
$sql = "SELECT 
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_revenue,
            SUM(CASE WHEN status = 'confirmed' THEN total_amount ELSE 0 END) as confirmed_revenue,
            SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_revenue
        FROM bookings 
        WHERE status != 'cancelled'";
$result = $conn->query($sql);
$revenueStats = $result->fetch_assoc();
$totalRevenue = $revenueStats['total_revenue'] ?: 0;
$completedRevenue = $revenueStats['completed_revenue'] ?: 0;
$confirmedRevenue = $revenueStats['confirmed_revenue'] ?: 0;
$pendingRevenue = $revenueStats['pending_revenue'] ?: 0;

// Popular event categories
$sql = "SELECT ec.name, COUNT(b.id) as booking_count
        FROM bookings b
        JOIN event_categories ec ON b.event_category_id = ec.id
        GROUP BY b.event_category_id
        ORDER BY booking_count DESC
        LIMIT 5";
$result = $conn->query($sql);
$popularCategories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $popularCategories[] = $row;
    }
}

// Upcoming bookings (next 7 days)
$sql = "SELECT COUNT(*) as upcoming_bookings 
        FROM bookings 
        WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND (status = 'confirmed' OR status = 'pending')";
$result = $conn->query($sql);
$upcomingBookings = $result->fetch_assoc()['upcoming_bookings'];

// Recent bookings
$sql = "SELECT b.*, u.name as user_name, ec.name as event_name 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN event_categories ec ON b.event_category_id = ec.id
        ORDER BY b.created_at DESC LIMIT 5";
$result = $conn->query($sql);
$recentBookings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
}
?>

<div class="dashboard-welcome" style="margin-bottom: 20px; background-color: #1d3557; color: #fff; padding: 20px; border-radius: 5px;">
    <h1 style="margin: 0;">Welcome to the Dashboard</h1>
    <p style="margin: 5px 0 0 0;">Here's an overview of your event booking system statistics</p>
</div>

<!-- Booking Statistics -->
<div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #457b9d; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-calendar-check" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;"><?php echo $totalBookings; ?></h3>
                <p style="margin: 0; color: #666;">Total Bookings</p>
            </div>
        </div>
        <a href="bookings.php" style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">View Details <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #ffc107; color: #212529; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-clock" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;"><?php echo $pendingBookings; ?></h3>
                <p style="margin: 0; color: #666;">Pending Bookings</p>
            </div>
        </div>
        <a href="bookings.php?status=pending" style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">View Details <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #28a745; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;"><?php echo $confirmedBookings; ?></h3>
                <p style="margin: 0; color: #666;">Confirmed Bookings</p>
            </div>
        </div>
        <a href="bookings.php?status=confirmed" style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">View Details <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #007bff; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-calendar-day" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;"><?php echo $upcomingBookings; ?></h3>
                <p style="margin: 0; color: #666;">Upcoming (7 days)</p>
            </div>
        </div>
        <a href="bookings.php" style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">View Calendar <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<!-- Revenue Statistics -->
<div class="revenue-stats" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #28a745; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-money-bill-wave" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;">NPR <?php echo number_format($totalRevenue, 2); ?></h3>
                <p style="margin: 0; color: #666;">Total Revenue</p>
            </div>
        </div>
        <span style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">All time</span>
    </div>
    
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #007bff; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-check-double" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;">NPR <?php echo number_format($completedRevenue, 2); ?></h3>
                <p style="margin: 0; color: #666;">Completed Revenue</p>
            </div>
        </div>
        <span style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">From <?php echo $completedBookings; ?> bookings</span>
    </div>
    
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #28a745; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-handshake" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;">NPR <?php echo number_format($confirmedRevenue, 2); ?></h3>
                <p style="margin: 0; color: #666;">Confirmed Revenue</p>
            </div>
        </div>
        <span style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">From <?php echo $confirmedBookings; ?> bookings</span>
    </div>
    
    <div class="stat-card" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 50px; height: 50px; background-color: #ffc107; color: #212529; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                <i class="fas fa-hourglass-half" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;">NPR <?php echo number_format($pendingRevenue, 2); ?></h3>
                <p style="margin: 0; color: #666;">Pending Revenue</p>
            </div>
        </div>
        <span style="display: block; text-align: right; color: #1d3557; font-size: 0.9rem;">From <?php echo $pendingBookings; ?> bookings</span>
    </div>
</div>

<!-- Content Sections Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
    <!-- Recent Bookings Section -->
    <div class="recent-bookings" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Recent Bookings</h2>
            <a href="bookings.php" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.9rem;">View All</a>
        </div>
        
        <?php if (empty($recentBookings)): ?>
            <p>No bookings found.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f8f9fa;">
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">ID</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">User</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">Event</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6;">Date & Time</th>
                            <th style="padding: 10px; text-align: right; border-bottom: 1px solid #dee2e6;">Amount</th>
                            <th style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">Status</th>
                            <th style="padding: 10px; text-align: center; border-bottom: 1px solid #dee2e6;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">#<?php echo $booking['id']; ?></td>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo $booking['user_name']; ?></td>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo $booking['event_name']; ?></td>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                    <?php echo date('M d, Y', strtotime($booking['event_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($booking['event_time'])); ?></small>
                                </td>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">NPR <?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                    <span class="status-badge <?php echo $booking['status']; ?>" style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                    <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline" style="padding: 3px 8px; font-size: 0.8rem;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Popular Categories Section -->
    <div class="popular-categories" style="background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Popular Event Categories</h2>
        </div>
        
        <?php if (empty($popularCategories)): ?>
            <p>No data available.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($popularCategories as $index => $category): ?>
                    <?php
                        $colors = ['#e63946', '#457b9d', '#1d3557', '#2a9d8f', '#f4a261'];
                        $color = $colors[$index % count($colors)];
                    ?>
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span style="font-weight: 600;"><?php echo $category['name']; ?></span>
                            <span style="background-color: <?php echo $color; ?>; color: #fff; border-radius: 20px; padding: 3px 10px; font-size: 0.8rem;">
                                <?php echo $category['booking_count']; ?> bookings
                            </span>
                        </div>
                        <div style="height: 8px; background-color: #f5f5f5; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo min(100, ($category['booking_count'] / max(1, $totalBookings) * 100)); ?>%; background-color: <?php echo $color; ?>;"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <!-- User Stats -->
        <div style="margin-top: 30px;">
            <h2 style="margin: 0 0 20px 0;">User Statistics</h2>
            <div class="stat-card" style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 40px; height: 40px; background-color: #e63946; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                        <i class="fas fa-users" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.3rem;"><?php echo $totalUsers; ?></h3>
                        <p style="margin: 0; color: #666;">Total Registered Users</p>
                    </div>
                </div>
            </div>
        </div>
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

    @media (max-width: 768px) {
        div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php
require_once 'includes/footer.php';
?> 