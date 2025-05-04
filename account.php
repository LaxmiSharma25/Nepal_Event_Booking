<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage("Please login to view your account", "warning");
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Get user's bookings
$bookingsQuery = "SELECT b.*, COUNT(bi.id) as total_items 
                 FROM bookings b 
                 LEFT JOIN booking_items bi ON b.id = bi.booking_id 
                 WHERE b.user_id = ? 
                 GROUP BY b.id 
                 ORDER BY b.created_at DESC";
$bookingsStmt = $conn->prepare($bookingsQuery);
$bookingsStmt->bind_param("i", $user_id);
$bookingsStmt->execute();
$bookings = $bookingsStmt->get_result();

// Create uploads directory if it doesn't exist
$uploadsDir = 'assets/images/profiles';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email'], true);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    $errors = [];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !isValidEmail($email)) {
        $errors[] = "Valid email is required";
    } else {
        // Check if email exists for other users
        $emailCheckQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
        $emailCheckStmt = $conn->prepare($emailCheckQuery);
        $emailCheckStmt->bind_param("si", $email, $user_id);
        $emailCheckStmt->execute();
        $emailResult = $emailCheckStmt->get_result();
        
        if ($emailResult->num_rows > 0) {
            $errors[] = "Email is already in use by another account";
        }
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Process profile picture upload
    $profile_picture = $user['profile_picture']; // Keep existing profile picture by default
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filesize = $_FILES['profile_picture']['size'];
        $fileTmp = $_FILES['profile_picture']['tmp_name'];
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Validate file extension
        if (!in_array($ext, $allowed)) {
            $errors[] = "Please upload an image file (jpg, jpeg, png, or gif)";
        }
        
        // Validate file size (max 2MB)
        if ($filesize > 2097152) {
            $errors[] = "File size must be less than 2MB";
        }
        
        if (empty($errors)) {
            // Generate unique filename
            $newFilename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $destination = $uploadsDir . '/' . $newFilename;
            
            // Move the uploaded file
            if (move_uploaded_file($fileTmp, $destination)) {
                // Delete old profile picture if exists
                if (!empty($profile_picture) && file_exists($uploadsDir . '/' . $profile_picture)) {
                    unlink($uploadsDir . '/' . $profile_picture);
                }
                
                $profile_picture = $newFilename;
            } else {
                $errors[] = "Failed to upload the profile picture";
            }
        }
    }
    
    // If no errors, update the profile
    if (empty($errors)) {
        $updateQuery = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sssssi", $name, $email, $phone, $address, $profile_picture, $user_id);
        
        if ($updateStmt->execute()) {
            setMessage("Your profile has been updated successfully", "success");
            // Refresh user data
            $userStmt->execute();
            $user = $userStmt->get_result()->fetch_assoc();
        } else {
            setMessage("Failed to update profile: " . $conn->error, "danger");
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate inputs
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    } else {
        // Verify current password
        $passwordQuery = "SELECT password FROM users WHERE id = ?";
        $passwordStmt = $conn->prepare($passwordQuery);
        $passwordStmt->bind_param("i", $user_id);
        $passwordStmt->execute();
        $passwordResult = $passwordStmt->get_result()->fetch_assoc();
        
        if (!password_verify($current_password, $passwordResult['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Password confirmation does not match";
    }
    
    // If no errors, update the password
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updatePasswordQuery = "UPDATE users SET password = ? WHERE id = ?";
        $updatePasswordStmt = $conn->prepare($updatePasswordQuery);
        $updatePasswordStmt->bind_param("si", $hashed_password, $user_id);
        
        if ($updatePasswordStmt->execute()) {
            setMessage("Your password has been changed successfully", "success");
        } else {
            setMessage("Failed to change password: " . $conn->error, "danger");
        }
    }
}

// Page title
$pageTitle = "My Account";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Profile Sidebar -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Profile</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (!empty($user['profile_picture']) && file_exists($uploadsDir . '/' . $user['profile_picture'])): ?>
                            <img src="<?php echo $uploadsDir . '/' . $user['profile_picture']; ?>" alt="Profile" class="img-fluid rounded-circle border" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/150" alt="Profile" class="img-fluid rounded-circle border" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="mt-3">
                        <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="#" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-phone text-primary me-2"></i> <?php echo htmlspecialchars($user['phone']); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i> <?php echo htmlspecialchars($user['address']); ?>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-calendar-alt text-primary me-2"></i> Member since: <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </li>
                </ul>
                <div class="card-footer">
                    <a href="logout.php" class="btn btn-danger w-100">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- My Bookings -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Bookings</h4>
                </div>
                <div class="card-body p-0">
                    <?php echo displayMessage(); ?>
                    
                    <?php if ($bookings->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($booking['created_at'])); ?></td>
                                            <td><?php echo $booking['total_items']; ?></td>
                                            <td><?php echo formatPrice($booking['total_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($booking['status'] == 'confirmed') ? 'success' : (($booking['status'] == 'pending') ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="booking-confirmation.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <div class="mb-3">
                                <i class="fas fa-calendar-times text-muted" style="font-size: 50px;"></i>
                            </div>
                            <h5>No Bookings Found</h5>
                            <p class="text-muted">You haven't made any bookings yet</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Explore Events
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php if (isset($errors) && !empty($errors) && isset($_POST['update_profile'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3 text-center">
                        <?php if (!empty($user['profile_picture']) && file_exists($uploadsDir . '/' . $user['profile_picture'])): ?>
                            <img src="<?php echo $uploadsDir . '/' . $user['profile_picture']; ?>" alt="Profile" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/150" alt="Profile" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <label for="profile_picture" class="form-label">Change Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            <div class="form-text">Max file size: 2MB. Supported formats: JPG, PNG, GIF</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <?php if (isset($errors) && !empty($errors) && isset($_POST['change_password'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 