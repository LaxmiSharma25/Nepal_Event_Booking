<?php
require_once 'config/db.php';
require_once 'config/functions.php';
require_once 'khalti_config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log the success response
$log_file = 'khalti_logs.txt';
$response_data = "SUCCESS RESPONSE: " . date('Y-m-d H:i:s') . "\n";
$response_data .= "GET data: " . print_r($_GET, true) . "\n";
$response_data .= "POST data: " . print_r($_POST, true) . "\n";
$response_data .= "----------------------------------------------------\n";
file_put_contents($log_file, $response_data, FILE_APPEND);

// Get transaction details - check GET parameters
if (isset($_GET['transaction_uuid']) && !empty($_GET['transaction_uuid'])) {
    $order_id = $_GET['transaction_uuid'];
    $token = $_GET['token'] ?? '';
    $amount = $_GET['amount'] ?? 0;
    
    // Verify the payment with Khalti server
    if (!empty($token)) {
        // Set up the data for verification
        $data = [
            'token' => $token,
            'amount' => $amount
        ];

        // Make API request to Khalti to verify payment
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => KHALTI_API_URL . '/payment/verify/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Key ' . KHALTI_SECRET_KEY,
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            // Log error and redirect to failure page
            file_put_contents($log_file, "CURL Error: " . $err . "\n", FILE_APPEND);
            $_SESSION['message'] = "Payment verification failed. Please contact support.";
            $_SESSION['message_type'] = "error";
            header("Location: khalti_failure.php?transaction_uuid=" . $order_id);
            exit;
        }
        
        // Log the verification response
        file_put_contents($log_file, "Verification Response: " . $response . "\n", FILE_APPEND);
        
        // Parse the response
        $response_data = json_decode($response, true);
        
        // Check if verification was successful
        if (!isset($response_data['idx']) || empty($response_data['idx'])) {
            $_SESSION['message'] = "Payment verification failed. Please contact support.";
            $_SESSION['message_type'] = "error";
            header("Location: khalti_failure.php?transaction_uuid=" . $order_id);
            exit;
        }
    }
    
    // Extract booking IDs from order ID (Format: ORDER-{timestamp}-{user_id}-{booking_ids})
    $order_parts = explode('-', $order_id);
    if (count($order_parts) < 4) {
        $_SESSION['message'] = "Invalid order information.";
        $_SESSION['message_type'] = "error";
        header("Location: cart.php");
        exit;
    }

    // Get booking IDs from the order ID
    $booking_ids_part = implode('-', array_slice($order_parts, 3));
    $booking_ids = explode(',', $booking_ids_part);

    // Update booking status for each booking in the order
    foreach ($booking_ids as $booking_id) {
        // Validate booking ID
        if (!is_numeric($booking_id)) {
            continue;
        }

        // Update booking status to 'confirmed'
        $sql = "UPDATE bookings SET 
                status = 'confirmed',
                payment_method = 'khalti',
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
    }

    // Set success message
    $_SESSION['message'] = "Payment successful! Your booking(s) have been confirmed.";
    $_SESSION['message_type'] = "success";

    // Redirect to a thank you page
    header("Location: thank_you.php?order_id=$order_id");
    exit;
} else {
    // If no transaction UUID found, redirect to cart
    $_SESSION['message'] = "Invalid payment response received.";
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit;
}
?> 