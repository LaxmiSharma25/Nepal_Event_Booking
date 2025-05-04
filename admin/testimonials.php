<?php
$pageTitle = "Manage Testimonials";
require_once 'includes/header.php';

// Handle testimonial status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        $sql = "UPDATE testimonials SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            setMessage("Testimonial " . ucfirst($action) . "d successfully.", "success");
        } else {
            setMessage("Error updating testimonial: " . $conn->error, "error");
        }
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM testimonials WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setMessage("Testimonial deleted successfully.", "success");
        } else {
            setMessage("Error deleting testimonial: " . $conn->error, "error");
        }
    }
    
    header("Location: testimonials.php");
    exit;
}

// Get all testimonials with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$whereSql = "";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $whereSql = " WHERE status = ?";
    $params = [$status_filter];
    $types = "s";
}

// Count total testimonials for pagination
$countSql = "SELECT COUNT(*) as total FROM testimonials" . $whereSql;
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalTestimonials = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalTestimonials / $limit);

// Get testimonials for current page
$sql = "SELECT t.*, u.name as user_name, u.email as user_email 
        FROM testimonials t
        JOIN users u ON t.user_id = u.id" . 
        $whereSql . " 
        ORDER BY t.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

$bindTypes = $types . "ii";
$bindParams = array_merge($params, [$limit, $offset]);

$stmt->bind_param($bindTypes, ...$bindParams);
$stmt->execute();
$result = $stmt->get_result();

$testimonials = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $testimonials[] = $row;
    }
}
?>

<div class="admin-content">
    <div class="page-header">
        <h2>Manage Testimonials</h2>
        <div class="filter-form">
            <form action="" method="GET" class="d-flex">
                <select name="status" class="form-control">
                    <option value="">All Testimonials</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button type="submit" class="btn btn-primary ml-2">Filter</button>
                <?php if (!empty($status_filter)): ?>
                <a href="testimonials.php" class="btn btn-secondary ml-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Testimonial</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($testimonials) > 0): ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($testimonial['user_name']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($testimonial['user_email']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $testimonial['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars($testimonial['content'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($testimonial['created_at'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $testimonial['status']; ?>">
                                <?php echo ucfirst($testimonial['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($testimonial['status'] === 'pending'): ?>
                                <a href="testimonials.php?action=approve&id=<?php echo $testimonial['id']; ?>" 
                                   class="btn btn-sm btn-success" 
                                   title="Approve Testimonial" 
                                   onclick="return confirm('Are you sure you want to approve this testimonial?')">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="testimonials.php?action=reject&id=<?php echo $testimonial['id']; ?>" 
                                   class="btn btn-sm btn-warning" 
                                   title="Reject Testimonial" 
                                   onclick="return confirm('Are you sure you want to reject this testimonial?')">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php elseif ($testimonial['status'] === 'approved'): ?>
                                <a href="testimonials.php?action=reject&id=<?php echo $testimonial['id']; ?>" 
                                   class="btn btn-sm btn-warning" 
                                   title="Reject Testimonial" 
                                   onclick="return confirm('Are you sure you want to reject this testimonial?')">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php elseif ($testimonial['status'] === 'rejected'): ?>
                                <a href="testimonials.php?action=approve&id=<?php echo $testimonial['id']; ?>" 
                                   class="btn btn-sm btn-success" 
                                   title="Approve Testimonial" 
                                   onclick="return confirm('Are you sure you want to approve this testimonial?')">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                                <a href="testimonials.php?action=delete&id=<?php echo $testimonial['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete Testimonial" 
                                   onclick="return confirm('Are you sure you want to delete this testimonial? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No testimonials found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="testimonials.php?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-item">&laquo; Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="testimonials.php?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
               class="pagination-item <?php echo $page === $i ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="testimonials.php?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-item">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
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
    flex-wrap: wrap;
}

.filter-form {
    display: flex;
    align-items: center;
}

.filter-form .form-control {
    width: 180px;
    margin-right: 10px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
}

.user-email {
    font-size: 0.85rem;
    color: #6c757d;
}

.rating {
    color: #ffc107;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-approved {
    background-color: #d4edda;
    color: #155724;
}

.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 5px;
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

.ml-2 {
    margin-left: 0.5rem;
}

.d-flex {
    display: flex;
}
</style>

<?php require_once 'includes/footer.php'; ?> 