<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please log in to complete your booking";
    $_SESSION['message_type'] = "error";
    redirect('login.php');
}

// Check if booking ID is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    $_SESSION['message'] = "Invalid booking ID";
    $_SESSION['message_type'] = "error";
    redirect('bookings.php');
}

$bookingId = (int)$_GET['booking_id'];

// Get booking details
$sql = "SELECT b.*, e.title as event_title, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bookingId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Booking not found or you're not authorized to view it";
    $_SESSION['message_type'] = "error";
    redirect('bookings.php');
}

$booking = $result->fetch_assoc();

// Update booking status and payment method
$updateSql = "UPDATE bookings SET status = 'pending', payment_method = 'bank_transfer' WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("i", $bookingId);
$updateStmt->execute();

// Bank account details
$bankDetails = [
    'bank_name' => 'Nepal Bank Limited',
    'account_name' => 'Nepali Event Booking System',
    'account_number' => '0123456789012345',
    'branch' => 'Kathmandu Main Branch',
    'swift_code' => 'NEPALNPKABC'
];

// Process form submission for confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = sanitize($_POST['transaction_id']);
    $transferDate = sanitize($_POST['transfer_date']);
    
    // Update payment reference
    $paymentSql = "UPDATE bookings SET payment_reference = ?, payment_date = ? WHERE id = ?";
    $paymentStmt = $conn->prepare($paymentSql);
    $paymentStmt->bind_param("ssi", $transactionId, $transferDate, $bookingId);
    
    if ($paymentStmt->execute()) {
        $_SESSION['message'] = "Bank transfer details submitted successfully. Your booking will be confirmed after verification.";
        $_SESSION['message_type'] = "success";
        redirect('booking_detail.php?id=' . $bookingId);
    } else {
        $_SESSION['message'] = "Error updating payment details: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
}
?>

<div class="payment-page">
    <div class="section-heading">
        <h2>Bank Transfer Payment</h2>
    </div>
    
    <div class="bank-payment">
        <div class="payment-info">
            <h3>Payment Details</h3>
            <table class="payment-details">
                <tr>
                    <th>Booking ID:</th>
                    <td>#<?php echo $booking['id']; ?></td>
                </tr>
                <tr>
                    <th>Event:</th>
                    <td><?php echo $booking['event_title']; ?></td>
                </tr>
                <tr>
                    <th>Amount:</th>
                    <td><?php echo formatPrice($booking['amount']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="bank-details">
            <h3>Bank Account Details</h3>
            <div class="bank-info">
                <ul>
                    <li><strong>Bank Name:</strong> <?php echo $bankDetails['bank_name']; ?></li>
                    <li><strong>Account Name:</strong> <?php echo $bankDetails['account_name']; ?></li>
                    <li><strong>Account Number:</strong> <?php echo $bankDetails['account_number']; ?></li>
                    <li><strong>Branch:</strong> <?php echo $bankDetails['branch']; ?></li>
                    <li><strong>SWIFT Code:</strong> <?php echo $bankDetails['swift_code']; ?></li>
                </ul>
            </div>
            
            <div class="transfer-instructions">
                <h4>Instructions:</h4>
                <ol>
                    <li>Transfer the exact amount (<?php echo formatPrice($booking['amount']); ?>) to the bank account above</li>
                    <li>Include your Booking ID (#<?php echo $booking['id']; ?>) in the transfer reference</li>
                    <li>After completing the transfer, fill in the form below with your transfer details</li>
                    <li>Your booking will be confirmed once we verify your payment</li>
                </ol>
            </div>
        </div>
        
        <div class="confirmation-form">
            <h3>Confirm Your Transfer</h3>
            <form action="" method="post">
                <div class="form-group">
                    <label for="transaction-id">Transaction Reference</label>
                    <input type="text" name="transaction_id" id="transaction-id" class="form-control" required placeholder="Enter the transaction reference or receipt number">
                </div>
                
                <div class="form-group">
                    <label for="transfer-date">Transfer Date</label>
                    <input type="date" name="transfer_date" id="transfer-date" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary">Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.payment-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.bank-payment {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
}

.payment-info {
    margin-bottom: 30px;
}

.payment-details {
    width: 100%;
    border-collapse: collapse;
}

.payment-details th,
.payment-details td {
    padding: 10px;
    border-bottom: 1px solid #e9ecef;
}

.payment-details th {
    text-align: left;
    width: 30%;
    color: #6c757d;
}

.bank-details {
    margin-bottom: 30px;
}

.bank-info {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.bank-info ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.bank-info li {
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.bank-info li:last-child {
    border-bottom: none;
}

.transfer-instructions {
    background-color: #e9f5ff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.transfer-instructions ol {
    margin: 10px 0 0 20px;
    padding: 0;
}

.transfer-instructions li {
    margin-bottom: 10px;
}

.confirmation-form {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
}

.text-center {
    text-align: center;
}
</style>

<?php
require_once 'includes/footer.php';
?> 