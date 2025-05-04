<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'khalti_config.php';

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Custom error logging
function logToFile($message) {
    $logFile = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Log for debugging
logToFile("Khalti process started");

// Check if user is logged in
if (!isLoggedIn()) {
    logToFile("User not logged in");
    $_SESSION['message'] = "Please login to continue";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php");
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];
logToFile("User ID: " . $user_id);

// Check for order details
if (!isset($_POST['booking_ids']) || !isset($_POST['amount'])) {
    logToFile("Missing booking_ids or amount in POST data");
    logToFile("POST data: " . print_r($_POST, true));
    $_SESSION['message'] = "Invalid order information";
    $_SESSION['message_type'] = "danger";
    header("Location: checkout.php");
    exit;
}

$booking_ids = $_POST['booking_ids'];
$amount = $_POST['amount'];
$customer_info = isset($_POST['customer_info']) ? $_POST['customer_info'] : [];

logToFile("Booking IDs: " . $booking_ids);
logToFile("Amount: " . $amount);
logToFile("Customer info: " . print_r($customer_info, true));

// Store booking IDs in session for verification later
$_SESSION['khalti_order_id'] = $booking_ids;
$_SESSION['khalti_amount'] = $amount;

// Convert amount from Rupees to Paisa (1 Rupee = 100 Paisa)
$amount_in_paisa = intval($amount * 100);
logToFile("Amount in paisa: " . $amount_in_paisa);

// Create a unique order identifier
$purchase_order_id = $booking_ids;

// Build the payload for Khalti
$payload = [
    'return_url' => KHALTI_RETURN_URL,
    'website_url' => 'http://' . $_SERVER['HTTP_HOST'],
    'amount' => $amount_in_paisa,
    'purchase_order_id' => $purchase_order_id,
    'purchase_order_name' => 'Event Booking #' . str_replace(',', '-', $booking_ids),
];

// Add customer information if available
if (!empty($customer_info)) {
    $payload['customer_info'] = [
        'name' => $customer_info['name'] ?? '',
        'email' => $customer_info['email'] ?? '',
        'phone' => $customer_info['phone'] ?? ''
    ];
}

logToFile("Khalti payload: " . print_r($payload, true));
logToFile("KHALTI_API_URL: " . KHALTI_API_URL);
logToFile("KHALTI_SECRET_KEY (first 4 chars): " . substr(KHALTI_SECRET_KEY, 0, 4) . "...");
logToFile("KHALTI_RETURN_URL: " . KHALTI_RETURN_URL);

// Initiate payment request to Khalti
function initiateKhaltiPayment($payload) {
    logToFile("Initiating Khalti payment");
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => KHALTI_API_URL . '/epayment/initiate/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Key ' . KHALTI_SECRET_KEY,
            'Content-Type: application/json'
        ],
    ]);
    
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    $err = curl_error($curl);
    
    logToFile("Curl HTTP code: " . $info['http_code']);
    
    curl_close($curl);
    
    if ($err) {
        logToFile("Curl error: " . $err);
        return ['success' => false, 'error' => $err];
    }
    
    logToFile("Khalti response: " . $response);
    return ['success' => true, 'data' => json_decode($response, true)];
}

// Initiate Khalti payment
$initiate_response = initiateKhaltiPayment($payload);

if (!$initiate_response['success']) {
    logToFile("Failed to initiate payment: " . $initiate_response['error']);
    $_SESSION['message'] = "Failed to initiate payment: " . $initiate_response['error'];
    $_SESSION['message_type'] = "danger";
    header("Location: checkout.php");
    exit;
}

$payment_data = $initiate_response['data'];
logToFile("Payment data: " . print_r($payment_data, true));

// Check for errors in Khalti response
if (isset($payment_data['error_key'])) {
    $error_message = "Payment error: ";
    
    if (isset($payment_data['amount'])) {
        $error_message .= implode(", ", $payment_data['amount']);
    } else if (isset($payment_data['detail'])) {
        $error_message .= $payment_data['detail'];
    } else {
        $error_message .= "Unknown error occurred";
    }
    
    logToFile("Khalti error: " . $error_message);
    $_SESSION['message'] = $error_message;
    $_SESSION['message_type'] = "danger";
    header("Location: checkout.php");
    exit;
}

// Redirect to Khalti payment page if successful
if (isset($payment_data['payment_url'])) {
    logToFile("Redirecting to payment URL: " . $payment_data['payment_url']);
    header("Location: " . $payment_data['payment_url']);
    exit;
} else {
    logToFile("Invalid response from payment gateway - missing payment_url");
    $_SESSION['message'] = "Invalid response from payment gateway";
    $_SESSION['message_type'] = "danger";
    header("Location: checkout.php");
    exit;
}
?> 