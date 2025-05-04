<?php
require_once 'includes/header.php';

// Set page title
$pageTitle = 'Event Management';

// Handle event category deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if events exist for this category
    $check_sql = "SELECT COUNT(*) as count FROM events WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $_SESSION['message'] = "Cannot delete category. There are events associated with it.";
        $_SESSION['message_type'] = "error";
    } else {
        // Delete the category
        $sql = "DELETE FROM event_categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Event category deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting event category.";
            $_SESSION['message_type'] = "error";
        }
    }
    redirect('events.php');
}

// Get event categories with booking counts and revenue statistics
$sql = "SELECT ec.*, 
        COUNT(b.id) as bookings_count,
        SUM(CASE WHEN b.status != 'cancelled' THEN b.amount ELSE 0 END) as total_revenue
        FROM event_categories ec
        LEFT JOIN events e ON ec.id = e.category_id
        LEFT JOIN bookings b ON e.id = b.event_id
        GROUP BY ec.id
        ORDER BY ec.name ASC";
$result = $conn->query($sql);
$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get payment method statistics
$payment_sql = "SELECT 
                payment_method, 
                COUNT(*) as count, 
                SUM(amount) as total_amount 
                FROM bookings 
                WHERE status != 'cancelled' 
                GROUP BY payment_method";
$payment_result = $conn->query($payment_sql);
$payment_stats = [];
if ($payment_result->num_rows > 0) {
    while ($row = $payment_result->fetch_assoc()) {
        $payment_stats[] = $row;
    }
}

// Calculate Khalti payment percentage
$khalti_total = 0;
$all_payments_total = 0;
foreach ($payment_stats as $stat) {
    $all_payments_total += $stat['total_amount'];
    if ($stat['payment_method'] == 'khalti') {
        $khalti_total = $stat['total_amount'];
    }
}
$khalti_percentage = $all_payments_total > 0 ? round(($khalti_total / $all_payments_total) * 100) : 0;

// Get recent payments
$recent_payments_sql = "SELECT b.*, u.name as user_name, e.title as event_title 
                        FROM bookings b
                        JOIN users u ON b.user_id = u.id
                        JOIN events e ON b.event_id = e.id
                        ORDER BY b.created_at DESC
                        LIMIT 8";
$recent_payments_result = $conn->query($recent_payments_sql);
$recent_payments = [];
if ($recent_payments_result->num_rows > 0) {
    while ($row = $recent_payments_result->fetch_assoc()) {
        $recent_payments[] = $row;
    }
}

// Get total revenue
$revenue_sql = "SELECT SUM(amount) as total FROM bookings WHERE status != 'cancelled'";
$revenue_result = $conn->query($revenue_sql);
$total_revenue = $revenue_result->fetch_assoc()['total'] ?? 0;
?>

<div class="main-content">
    <div class="section-heading">
        <h2>Event Management</h2>
        <a href="#" class="btn btn-primary add-category-btn"><i class="fas fa-plus"></i> Add New Category</a>
    </div>
    
    <!-- Dashboard Cards -->
    <div class="dashboard-cards">
        <div class="card">
            <div class="card-body">
                <div class="card-stat">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo count($categories); ?></h3>
                        <p>Total Event Categories</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="card-stat">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-details">
                        <h3>NPR <?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="card-stat">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $khalti_percentage; ?>%</h3>
                        <p>Khalti Payments</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="card-stat">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-details">
                        <h3>NPR <?php echo number_format($khalti_total, 2); ?></h3>
                        <p>Khalti Revenue</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods Chart -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Payment Methods</h3>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Recent Khalti Payments</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Event</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <?php if ($payment['payment_method'] == 'khalti'): ?>
                                    <tr>
                                        <td>#<?php echo $payment['id']; ?></td>
                                        <td><?php echo $payment['user_name']; ?></td>
                                        <td><?php echo $payment['event_title']; ?></td>
                                        <td>NPR <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo date("M d, Y", strtotime($payment['created_at'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (count(array_filter($recent_payments, function($p) { return $p['payment_method'] == 'khalti'; })) == 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No recent Khalti payments found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Event Categories -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Event Categories</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Bookings</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['name']; ?></td>
                            <td><?php echo substr($category['description'], 0, 50) . (strlen($category['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo $category['bookings_count']; ?></td>
                            <td>NPR <?php echo number_format($category['total_revenue'], 2); ?></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary edit-category" data-id="<?php echo $category['id']; ?>"><i class="fas fa-edit"></i></a>
                                <a href="events.php?delete=true&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($categories) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center">No event categories found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding/editing event category -->
<div class="modal" id="categoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" action="process_category.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="category_id" id="category_id">
                    
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image</label>
                        <input type="file" name="image" id="image" class="form-control-file">
                        <div id="current_image" class="mt-2"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment Methods Chart
    var ctx = document.getElementById('paymentMethodsChart').getContext('2d');
    var paymentMethodsChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                foreach ($payment_stats as $stat) {
                    echo "'" . ucfirst($stat['payment_method']) . "', ";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($payment_stats as $stat) {
                        echo $stat['total_amount'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var total = dataset.data.reduce(function(previousValue, currentValue) {
                            return previousValue + currentValue;
                        });
                        var currentValue = dataset.data[tooltipItem.index];
                        var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                        return data.labels[tooltipItem.index] + ': NPR ' + currentValue + ' (' + percentage + '%)';
                    }
                }
            }
        }
    });
    
    // Add Category Button Click
    $('.add-category-btn').click(function(e) {
        e.preventDefault();
        $('#categoryForm')[0].reset();
        $('#category_id').val('');
        $('#current_image').html('');
        $('.modal-title').text('Add New Category');
        $('#categoryModal').modal('show');
    });
    
    // Edit Category Button Click
    $('.edit-category').click(function(e) {
        e.preventDefault();
        var categoryId = $(this).data('id');
        
        // Fetch category details via AJAX
        $.ajax({
            url: 'get_category.php',
            type: 'GET',
            data: {id: categoryId},
            dataType: 'json',
            success: function(data) {
                $('#category_id').val(data.id);
                $('#name').val(data.name);
                $('#description').val(data.description);
                
                if (data.image) {
                    $('#current_image').html('<img src="../assets/images/' + data.image + '" height="100" class="img-thumbnail">');
                } else {
                    $('#current_image').html('No image available');
                }
                
                $('.modal-title').text('Edit Category');
                $('#categoryModal').modal('show');
            },
            error: function() {
                alert('Error fetching category details');
            }
        });
    });
});
</script>

<style>
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.card {
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    overflow: hidden;
}

.card-header {
    background-color: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

.card-header h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.card-body {
    padding: 20px;
}

.card-stat {
    display: flex;
    align-items: center;
}

.stat-icon {
    background-color: #e9f5ff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stat-icon i {
    font-size: 24px;
    color: #1d3557;
}

.stat-details h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #1d3557;
}

.stat-details p {
    margin: 5px 0 0 0;
    color: #6c757d;
}

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.add-category-btn {
    margin-left: 15px;
}

.main-content {
    padding: 20px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0 15px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

.text-center {
    text-align: center;
}

.mt-2 {
    margin-top: 10px;
}

.mt-4 {
    margin-top: 20px;
}
</style>

<?php
require_once 'includes/footer.php';
?> 