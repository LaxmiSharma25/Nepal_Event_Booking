<?php
$pageTitle = "Manage Users";
require_once 'includes/header.php';

// Handle user deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if user has bookings
    $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        setMessage("Cannot delete user. The user has bookings associated with their account.", "error");
    } else {
        // Get user profile picture to delete if exists
        $sql = "SELECT profile_picture FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Delete user's profile picture if it exists
            if (!empty($user['profile_picture']) && file_exists("../uploads/profile_pictures/" . $user['profile_picture'])) {
                unlink("../uploads/profile_pictures/" . $user['profile_picture']);
            }
            
            // Delete the user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                setMessage("User deleted successfully.", "success");
            } else {
                setMessage("Error deleting user: " . $conn->error, "error");
            }
        } else {
            setMessage("User not found.", "error");
        }
    }
    header("Location: users.php");
    exit;
}

// Handle user status toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['toggle_status'] === 'active' ? 1 : 0;
    
    $sql = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $status, $id);
    
    if ($stmt->execute()) {
        setMessage("User status updated successfully.", "success");
    } else {
        setMessage("Error updating user status: " . $conn->error, "error");
    }
    
    header("Location: users.php");
    exit;
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$whereSql = "";
$params = [];
$types = "";

if (!empty($search)) {
    $whereSql = " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
    $types = "sss";
}

// Count total users for pagination
$countSql = "SELECT COUNT(*) as total FROM users" . $whereSql;
$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalUsers = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Get users for current page
$sql = "SELECT id, name, email, phone, created_at, status, profile_picture 
        FROM users" . $whereSql . " 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

$bindTypes = $types . "ii";
$bindParams = array_merge($params, [$limit, $offset]);

$stmt->bind_param($bindTypes, ...$bindParams);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<div class="admin-content">
    <div class="page-header">
        <h2>Manage Users</h2>
        <div class="search-form">
            <form action="" method="GET" class="d-flex">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                <button type="submit" class="btn btn-primary ml-2">Search</button>
                <?php if (!empty($search)): ?>
                <a href="users.php" class="btn btn-secondary ml-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Joined On</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <?php if (!empty($user['profile_picture']) && file_exists("../uploads/profile_pictures/" . $user['profile_picture'])): ?>
                                <img src="../uploads/profile_pictures/<?php echo $user['profile_picture']; ?>" alt="Profile Picture" class="profile-thumbnail">
                            <?php else: ?>
                                <div class="profile-placeholder">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $user['status'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="users.php?toggle_status=<?php echo $user['status'] ? 'inactive' : 'active'; ?>&id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm <?php echo $user['status'] ? 'btn-warning' : 'btn-success'; ?>" 
                                   title="<?php echo $user['status'] ? 'Deactivate' : 'Activate'; ?> User" 
                                   onclick="return confirm('Are you sure you want to <?php echo $user['status'] ? 'deactivate' : 'activate'; ?> this user?')">
                                    <i class="fas fa-<?php echo $user['status'] ? 'times' : 'check'; ?>"></i>
                                </a>
                                <a href="users.php?delete=true&id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete User" 
                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No users found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="users.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-item">&laquo; Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="users.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
               class="pagination-item <?php echo $page === $i ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="users.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-item">Next &raquo;</a>
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

.search-form {
    display: flex;
    align-items: center;
}

.search-form .form-control {
    width: 250px;
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

.profile-thumbnail {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.profile-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
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