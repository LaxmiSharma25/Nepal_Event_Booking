<?php
$pageTitle = "Service Categories";
require_once 'includes/header.php';

// Handle category deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if services exist for this category
    $check_sql = "SELECT COUNT(*) as count FROM services WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        setMessage("Cannot delete category. There are services associated with it.", "error");
    } else {
        // Delete the category
        $sql = "DELETE FROM service_categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setMessage("Service category deleted successfully.", "success");
        } else {
            setMessage("Error deleting service category.", "error");
        }
    }
    header("Location: service_categories.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    $errors = [];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Category name is required";
    }
    
    // Check if category name already exists
    $check_sql = "SELECT id FROM service_categories WHERE name = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_id = $category_id ? $category_id : 0;
    $check_stmt->bind_param("si", $name, $check_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = "A category with this name already exists";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        if ($category_id) {
            // Update existing category
            $sql = "UPDATE service_categories SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $name, $description, $category_id);
        } else {
            // Add new category
            $sql = "INSERT INTO service_categories (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $name, $description);
        }
        
        if ($stmt->execute()) {
            setMessage($category_id ? "Category updated successfully" : "Category added successfully", "success");
            header("Location: service_categories.php");
            exit;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// Get all service categories
$sql = "SELECT 
            sc.*, 
            COUNT(s.id) as service_count 
        FROM 
            service_categories sc 
        LEFT JOIN 
            services s ON sc.id = s.category_id 
        GROUP BY 
            sc.id 
        ORDER BY 
            sc.name ASC";
$result = $conn->query($sql);
$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<div class="admin-content">
    <div class="page-header">
        <h2>Service Categories</h2>
        <button class="btn btn-primary" onclick="showCategoryModal()">
            <i class="fas fa-plus"></i> Add New Category
        </button>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Services</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['name']; ?></td>
                        <td><?php echo nl2br(substr($category['description'], 0, 100)) . (strlen($category['description']) > 100 ? '...' : ''); ?></td>
                        <td><?php echo $category['service_count']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="service_categories.php?delete=true&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No service categories found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Modal -->
<div class="modal" id="categoryModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">Add New Category</h5>
                <button type="button" class="close" onclick="closeCategoryModal()">&times;</button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="category_id" id="category_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
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

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-dialog {
    width: 100%;
    max-width: 500px;
    margin: 30px auto;
}

.modal-content {
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
    text-align: right;
}

.form-group {
    margin-bottom: 15px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}
</style>

<script>
function showCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'Add New Category';
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('category_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function editCategory(category) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('category_id').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('description').value = category.description;
    
    document.getElementById('categoryModal').style.display = 'flex';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    var modal = document.getElementById('categoryModal');
    if (event.target === modal) {
        closeCategoryModal();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 