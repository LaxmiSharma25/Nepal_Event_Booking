<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
require_once 'khalti_config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please login to proceed to checkout.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = [];
$total = 0;

// Check if we have current checkout bookings in session
if (isset($_SESSION['current_checkout_bookings']) && !empty($_SESSION['current_checkout_bookings'])) {
    $booking_ids = $_SESSION['current_checkout_bookings'];
    
    // Convert array of booking IDs to comma-separated string for IN clause
    $booking_ids_str = implode(',', array_map('intval', $booking_ids));
    
    $sql = "SELECT b.*, s.name as title, s.price, s.image 
            FROM bookings b 
            JOIN services s ON b.service_id = s.id 
            WHERE b.id IN ($booking_ids_str) AND b.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
            $total += $row['price'];
        }
    } else {
        // No items found, redirect to cart page
        $_SESSION['message'] = "Your cart is empty.";
        $_SESSION['message_type'] = "error";
        header("Location: cart.php");
        exit;
    }
} else {
    // No current checkout bookings in session, redirect to cart
    $_SESSION['message'] = "Please select items for checkout first.";
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit;
}

// Get user information
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $full_name = $first_name . ' ' . $last_name;
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    // Combine address and city
    $full_address = $address . ', ' . $city;
    $payment_method = $_POST['payment_method'];
    
    // Validate required fields
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    
    // If no errors, proceed with payment
    if (empty($errors)) {
        // Update user information
        $update_sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $full_address, $user_id);
        $update_stmt->execute();
        
        // Create order ID: ORDER-timestamp-user_id-booking1,booking2
        $booking_ids = array_column($cart_items, 'id');
        $booking_ids_str = implode(',', $booking_ids);
        $timestamp = time();
        $order_id = "ORDER-{$timestamp}-{$user_id}-{$booking_ids_str}";
        
        // Process based on payment method
        if ($payment_method == 'khalti') {
            // Khalti payment processing will be handled via JavaScript
            // Get payment details
            $amount = $total;
            
            // Clear checkout session variable 
            unset($_SESSION['current_checkout_bookings']);
            
            // Store order ID in session for reference
            $_SESSION['current_order_id'] = $order_id;
            
            // Redirect to Khalti payment page or use client-side payment
            // The actual implementation depends on your Khalti integration
            header("Location: khalti_payment.php?amount={$amount}&order_id={$order_id}");
            exit;
        } else {
            // Cash on delivery
            foreach ($booking_ids as $booking_id) {
                $update_booking_sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";
                $update_booking_stmt = $conn->prepare($update_booking_sql);
                $update_booking_stmt->bind_param("i", $booking_id);
                $update_booking_stmt->execute();
            }
            
            // Clear checkout session variable
            unset($_SESSION['current_checkout_bookings']);
            
            $_SESSION['message'] = "Order placed successfully! You will pay during the service.";
            $_SESSION['message_type'] = "success";
            header("Location: thank_you.php?order_id=" . $order_id);
            exit;
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0">Billing Details</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php
                                    // Split the name if it exists 
                                    $name_parts = isset($user['name']) ? explode(' ', $user['name'], 2) : ['', ''];
                                    echo htmlspecialchars($name_parts[0]);
                                ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php 
                                    // Use the second part of the name if it exists
                                    echo htmlspecialchars($name_parts[1] ?? '');  
                                ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>" required>
                        </div>

                        <?php
                            // Split address into address and city if it contains a comma
                            $address_parts = ['', ''];
                            if (isset($user['address']) && !empty($user['address'])) {
                                $address_parts = explode(',', $user['address'], 2);
                                // If no comma, use the whole address as the street address
                                if (count($address_parts) === 1) {
                                    $address_parts = [$user['address'], ''];
                                }
                            }
                        ?>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address *</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars(trim($address_parts[0])); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars(trim($address_parts[1] ?? '')); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="order_notes" class="form-label">Order Notes (Optional)</label>
                            <textarea class="form-control" id="order_notes" name="order_notes" rows="3"></textarea>
                        </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0">Your Order</h2>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($item['title']); ?><br>
                                    <small class="text-muted">
                                        <?php echo date('F d, Y', strtotime($item['event_date'])); ?> at 
                                        <?php echo date('h:i A', strtotime($item['event_time'])); ?>
                                    </small>
                                </td>
                                <td class="text-end">Rs. <?php echo number_format($item['price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="text-end">Rs. <?php echo number_format($total); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0">Payment Method</h2>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" id="khalti" value="khalti" checked>
                        <label class="form-check-label" for="khalti">
                            <img src="assets/images/khalti-logo.png" alt="Khalti" height="30" class="me-2">
                            Khalti
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash">
                        <label class="form-check-label" for="cash">
                            <i class="bi bi-cash me-2"></i>
                            Cash on Service
                        </label>
                    </div>

                    <hr>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 