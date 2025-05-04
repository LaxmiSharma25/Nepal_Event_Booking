<?php
require_once 'includes/header.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $confirmPassword = sanitize($_POST['confirm_password']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $address_detail = sanitize($_POST['address_detail']);
    
    // Combine location and address detail
    $address = $location . ', ' . $address_detail;
    
    $errors = [];
    
    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // Validate password - Simplified requirements
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = "Password must contain at least one letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    // Validate confirm password
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // Validate phone
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Validate location
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    // If no errors, process registration
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $phone, $address);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful. Please login.";
            $_SESSION['message_type'] = "success";
            redirect('login.php');
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
    }
}
?>

<div class="form-container">
    <div class="section-heading">
        <h2>Register</h2>
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
    
    <form action="" method="post" id="register-form">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($name) ? $name : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($email) ? $email : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo isset($phone) ? $phone : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="location">Location</label>
            <select name="location" id="location" class="form-control" required>
                <option value="">Select your location</option>
                <option value="Kathmandu" <?php echo (isset($address) && strpos($address, 'Kathmandu') !== false) ? 'selected' : ''; ?>>Kathmandu</option>
                <option value="Pokhara" <?php echo (isset($address) && strpos($address, 'Pokhara') !== false) ? 'selected' : ''; ?>>Pokhara</option>
                <option value="Lalitpur" <?php echo (isset($address) && strpos($address, 'Lalitpur') !== false) ? 'selected' : ''; ?>>Lalitpur</option>
                <option value="Bhaktapur" <?php echo (isset($address) && strpos($address, 'Bhaktapur') !== false) ? 'selected' : ''; ?>>Bhaktapur</option>
                <option value="Biratnagar" <?php echo (isset($address) && strpos($address, 'Biratnagar') !== false) ? 'selected' : ''; ?>>Biratnagar</option>
                <option value="Birgunj" <?php echo (isset($address) && strpos($address, 'Birgunj') !== false) ? 'selected' : ''; ?>>Birgunj</option>
                <option value="Dharan" <?php echo (isset($address) && strpos($address, 'Dharan') !== false) ? 'selected' : ''; ?>>Dharan</option>
                <option value="Nepalgunj" <?php echo (isset($address) && strpos($address, 'Nepalgunj') !== false) ? 'selected' : ''; ?>>Nepalgunj</option>
                <option value="Butwal" <?php echo (isset($address) && strpos($address, 'Butwal') !== false) ? 'selected' : ''; ?>>Butwal</option>
                <option value="Dhangadhi" <?php echo (isset($address) && strpos($address, 'Dhangadhi') !== false) ? 'selected' : ''; ?>>Dhangadhi</option>
                <option value="Janakpur" <?php echo (isset($address) && strpos($address, 'Janakpur') !== false) ? 'selected' : ''; ?>>Janakpur</option>
                <option value="Hetauda" <?php echo (isset($address) && strpos($address, 'Hetauda') !== false) ? 'selected' : ''; ?>>Hetauda</option>
                <option value="Itahari" <?php echo (isset($address) && strpos($address, 'Itahari') !== false) ? 'selected' : ''; ?>>Itahari</option>
                <option value="Lumbini" <?php echo (isset($address) && strpos($address, 'Lumbini') !== false) ? 'selected' : ''; ?>>Lumbini</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="address_detail">Detailed Address</label>
            <textarea name="address_detail" id="address_detail" class="form-control" rows="2" placeholder="Street, Ward No., etc."><?php echo isset($address_detail) ? $address_detail : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div class="password-requirements">
                <p><strong>Password must contain:</strong></p>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>At least one letter (a-z or A-Z)</li>
                    <li>At least one number (0-9)</li>
                    <li>At least one special character (!@#$%^&*...)</li>
                </ul>
            </div>
        </div>
        
        <div class="form-group">
            <label for="confirm-password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm-password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

<style>
.password-requirements {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    margin-top: 5px;
    font-size: 0.9em;
}

.password-requirements ul {
    margin: 5px 0 0 20px;
    padding: 0;
}

.password-requirements li {
    margin-bottom: 3px;
}
</style>

<?php
require_once 'includes/footer.php';
?> 