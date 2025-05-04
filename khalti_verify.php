<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'khalti_config.php';

session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to continue";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php");
    exit;
}

// Get parameters from callback
$pidx = isset($_GET['pidx']) ? $_GET['pidx'] : '';
$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';
$amount = isset($_GET['amount']) ? $_GET['amount'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$purchase_order_id = isset($_GET['purchase_order_id']) ? $_GET['purchase_order_id'] : '';

// Verify payment status using lookup API
function verifyKhaltiPayment($pidx) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => KHALTI_API_URL . '/epayment/lookup/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(['pidx' => $pidx]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Key ' . KHALTI_SECRET_KEY,
            'Content-Type: application/json'
        ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
        return ['success' => false, 'error' => $err];
    }
    
    return ['success' => true, 'data' => json_decode($response, true)];
}

// Get session data for the order
$order_id = isset($_SESSION['khalti_order_id']) ? $_SESSION['khalti_order_id'] : null;
$order_amount = isset($_SESSION['khalti_amount']) ? $_SESSION['khalti_amount'] : 0;
$user_id = $_SESSION['user_id'];

if (empty($pidx)) {
    $_SESSION['message'] = "Invalid payment information";
    $_SESSION['message_type'] = "danger";
    header("Location: checkout.php");
    exit;
}

// Verify payment with Khalti
$verification = verifyKhaltiPayment($pidx);

if (!$verification['success']) {
    $_SESSION['message'] = "Payment verification failed: " . $verification['error'];
    $_SESSION['message_type'] = "danger";
    header("Location: checkout.php");
    exit;
}

$payment_data = $verification['data'];

// Check if payment was successful
if (isset($payment_data['status']) && $payment_data['status'] === 'Completed') {
    // Payment successful
    // Update booking status from pending to paid
    
    // Check if we have order_id in session (from checkout.php)
    if (!empty($order_id)) {
        // Update the booking(s) status to paid
        $bookingIds = explode(',', $order_id);
        
        foreach ($bookingIds as $booking_id) {
            $booking_id = trim($booking_id);
            if (!empty($booking_id)) {
                $updateQuery = "UPDATE bookings SET 
                                status = 'paid',
                                payment_method = 'khalti',
                                transaction_id = ?
                                WHERE id = ? AND user_id = ?";
                
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("sii", $transaction_id, $booking_id, $user_id);
                $updateStmt->execute();
            }
        }
        
        // Clear the session data
        unset($_SESSION['khalti_order_id']);
        unset($_SESSION['khalti_amount']);
        
        $_SESSION['message'] = "Payment successful! Your order has been confirmed.";
        $_SESSION['message_type'] = "success";
        header("Location: bookings.php");
        exit;
    } else {
        // No order ID in session, perhaps this is a direct callback from Khalti
        // Check the purchase_order_id from Khalti callback
        if (!empty($purchase_order_id)) {
            // The purchase_order_id might be a comma-separated list of booking IDs
            $bookingIds = explode(',', $purchase_order_id);
            
            foreach ($bookingIds as $booking_id) {
                $booking_id = trim($booking_id);
                if (!empty($booking_id)) {
                    $updateQuery = "UPDATE bookings SET 
                                    status = 'paid',
                                    payment_method = 'khalti',
                                    transaction_id = ?
                                    WHERE id = ? AND user_id = ?";
                    
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("sii", $transaction_id, $booking_id, $user_id);
                    $updateStmt->execute();
                }
            }
            
            $_SESSION['message'] = "Payment successful! Your order has been confirmed.";
            $_SESSION['message_type'] = "success";
            header("Location: bookings.php");
            exit;
        }
    }
} else {
    // Payment failed or is pending
    $status_message = isset($payment_data['status']) ? $payment_data['status'] : 'Unknown';
    
    $_SESSION['message'] = "Payment not completed. Status: " . $status_message;
    $_SESSION['message_type'] = "warning";
    header("Location: checkout.php");
    exit;
}

// If we get here, something went wrong
$_SESSION['message'] = "Unable to process payment. Please try again.";
$_SESSION['message_type'] = "danger";
 