<?php
$pageTitle = "Manage Services";
require_once 'includes/header.php';

// Handle service deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if there are bookings for this service
    $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE service_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        setMessage("Cannot delete service. There are bookings associated with it.", "error");
    } else {
        // Get service image to delete
        $img_sql = "SELECT image FROM services WHERE id = ?";
        $img_stmt = $conn->prepare($img_sql);
        $img_stmt->bind_param("i", $id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        $service = $img_result->fetch_assoc();
        
        // Delete the service
        $sql = "DELETE FROM services WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Delete image file if it exists
            if ($service && !empty($service['image'])) {
                $image_path = "../assets/images/services/" . $service['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            setMessage("Service deleted successfully.", "success");
        } else {
            setMessage("Error deleting service.", "error");
        }
    }
    header("Location: services.php");
    exit;
}

// Process service form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = isset($_POST['service_id']) ? $_POST['service_id'] : null;
    $name = sanitize($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $description = sanitize($_POST['description']);
    
    $errors = [];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Service name is required";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Please select a valid category";
    }
    
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    // Process image upload if no errors
    $image = null;
    
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $temp_name = $_FILES['image']['tmp_name'];
        $filesize = $_FILES['image']['size'];
        
        // Get file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check if file type is allowed
        if (!in_array($ext, $allowed)) {
            $errors[] = "Please upload an image file (jpg, jpeg, png, gif)";
        }
        
        // Check file size (max 2MB)
        if ($filesize > 2097152) {
            $errors[] = "Image file size must be less than 2MB";
        }
        
        // If no errors, process image
        if (empty($errors)) {
            // Create a unique filename
            $new_filename = 'service_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = '../assets/images/services/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../assets/images/services/')) {
                mkdir('../assets/images/services/', 0755, true);
            }
            
            // Upload the file
            if (move_uploaded_file($temp_name, $upload_path)) {
                $image = $new_filename;
                
                // If updating, delete old image
                if ($service_id) {
                    $old_img_sql = "SELECT image FROM services WHERE id = ?";
                    $old_img_stmt = $conn->prepare($old_img_sql);
                    $old_img_stmt->bind_param("i", $service_id);
                    $old_img_stmt->execute();
                    $old_img_result = $old_img_stmt->get_result();
                    $old_service = $old_img_result->fetch_assoc();
                    
                    if ($old_service && !empty($old_service['image']) && $old_service['image'] != $image) {
                        $old_image_path = "../assets/images/services/" . $old_service['image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                }
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        if ($service_id) {
            // Update existing service
            if ($image) {
                $sql = "UPDATE services SET name = ?, category_id = ?, price = ?, description = ?, image = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sidssi", $name, $category_id, $price, $description, $image, $service_id);
            } else {
                $sql = "UPDATE services SET name = ?, category_id = ?, price = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sidsi", $name, $category_id, $price, $description, $service_id);
            }
            
            if ($stmt->execute()) {
                setMessage("Service updated successfully", "success");
            } else {
                setMessage("Error updating service: " . $conn->error, "error");
            }
        } else {
            // Add new service
            if ($image) {
                $sql = "INSERT INTO services (name, category_id, price, description, image) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sidss", $name, $category_id, $price, $description, $image);
            } else {
                $sql = "INSERT INTO services (name, category_id, price, description) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sids", $name, $category_id, $price, $description);
            }
            
            if ($stmt->execute()) {
                setMessage("Service added successfully", "success");
            } else {
                setMessage("Error adding service: " . $conn->error, "error");
            }
        }
        
        // Redirect to services page
        header("Location: services.php");
        exit;
    }
}

// Get all services with category names
$sql = "SELECT s.*, sc.name as category_name 
        FROM services s
        JOIN service_categories sc ON s.category_id = sc.id
        ORDER BY s.name ASC";
$result = $conn->query($sql);
$services = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Get all service categories for dropdown
$category_sql = "SELECT * FROM service_categories ORDER BY name ASC";
$category_result = $conn->query($category_sql);
$categories = [];
if ($category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<div class="admin-content">
    <div class="page-header">
        <h2>Manage Services</h2>
        <button class="btn btn-primary" onclick="showServiceModal()">
            <i class="fas fa-plus"></i> Add New Service
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
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($services) > 0): ?>
                    <?php foreach ($services as $service): ?>
                    <tr>
                        <td>
                            <?php if (!empty($service['image']) && file_exists("../assets/images/services/{$service['image']}")): ?>
                            <img src="../assets/images/services/<?php echo $service['image']; ?>" alt="<?php echo $service['name']; ?>" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                            <div style="width: 50px; height: 50px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="color: #ccc;"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $service['name']; ?></td>
                        <td><?php echo $service['category_name']; ?></td>
                        <td>Rs. <?php echo number_format($service['price'], 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="services.php?delete=true&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this service?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No services found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Service Modal -->
<div class="modal" id="serviceModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalTitle">Add New Service</h5>
                <button type="button" class="close" onclick="closeServiceModal()">&times;</button>
            </div>
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="service_id" id="service_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Service Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (Rs.)</label>
                        <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Service Image</label>
                        <input type="file" name="image" id="image" class="form-control-file">
                        <div id="current_image" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeServiceModal()">Cancel</button>
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
function showServiceModal() {
    document.getElementById('serviceModalTitle').textContent = 'Add New Service';
    document.getElementById('serviceModal').style.display = 'flex';
    document.getElementById('service_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('price').value = '';
    document.getElementById('description').value = '';
    document.getElementById('current_image').innerHTML = '';
}

function closeServiceModal() {
    document.getElementById('serviceModal').style.display = 'none';
}

function editService(service) {
    document.getElementById('serviceModalTitle').textContent = 'Edit Service';
    document.getElementById('service_id').value = service.id;
    document.getElementById('name').value = service.name;
    document.getElementById('category_id').value = service.category_id;
    document.getElementById('price').value = service.price;
    document.getElementById('description').value = service.description;
    
    if (service.image) {
        document.getElementById('current_image').innerHTML = `
            <img src="../assets/images/services/${service.image}" height="100" class="img-thumbnail">
            <p class="mt-1">Current image: ${service.image}</p>
        `;
    } else {
        document.getElementById('current_image').innerHTML = 'No image available';
    }
    
    document.getElementById('serviceModal').style.display = 'flex';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    var modal = document.getElementById('serviceModal');
    if (event.target === modal) {
        closeServiceModal();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 