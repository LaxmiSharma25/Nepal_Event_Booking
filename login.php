<?php
require_once 'includes/header.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Initialize variables
$loginAttempted = false;
$debugInfo = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginAttempted = true;
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    
    $errors = [];
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, process login
    if (empty($errors)) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Debug information
        $debugInfo = "Query executed. Number of matching users: " . $result->num_rows;
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Add debug info
            $debugInfo .= "<br>User found with ID: " . $user['id'];
            
            // Use simple password check for debugging
            if (password_verify($password, $user['password'])) {
                // Password is correct, set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Add debug info
                $debugInfo .= "<br>Password verified successfully. Setting session variables.";
                
                // Set success message
                $_SESSION['message'] = "Login successful. Welcome back, " . $user['name'] . "!";
                $_SESSION['message_type'] = "success";
                
                // Redirect to home page
                redirect('index.php');
            } else {
                // Add debug info
                $debugInfo .= "<br>Password verification failed.";
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
    }
}

// Password requirements for display in case of login failure
$passwordRequirements = [
    "At least 8 characters long",
    "At least one uppercase letter (A-Z)",
    "At least one lowercase letter (a-z)",
    "At least one number (0-9)",
    "At least one special character (!@#$%^&*...)"
];
?>

<div class="form-container">
    <div class="section-heading">
        <h2>Login</h2>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if (in_array("Invalid email or password", $errors)): ?>
                <p><strong>Password Requirements:</strong></p>
                <ul>
                    <?php foreach ($passwordRequirements as $req): ?>
                        <li><?php echo $req; ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>If you've forgotten your password, please <a href="register.php">register a new account</a>.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($loginAttempted && !empty($debugInfo)): ?>
        <div class="alert alert-info">
            <p><strong>Debug Information:</strong></p>
            <p><?php echo $debugInfo; ?></p>
        </div>
    <?php endif; ?>
    
    <form action="" method="post">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($email) ? $email : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary" name="login_submit">Login</button>
        </div>
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?> 