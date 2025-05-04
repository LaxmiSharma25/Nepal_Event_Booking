<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'khalti_config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to process payment";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php");
    exit;
}

// Check if order ID and amount are provided
if (!isset($_GET['order_id']) || !isset($_GET['amount'])) {
    $_SESSION['message'] = "Missing payment information";
    $_SESSION['message_type'] = "danger";
    header("Location: cart.php");
    exit;
}

$order_id = $_GET['order_id'];
$amount = $_GET['amount'];

// Log the payment request
$log_file = fopen("khalti_logs.txt", "a");
fwrite($log_file, date("Y-m-d H:i:s") . " - Payment Request: Order ID: " . $order_id . ", Amount: " . $amount . "\n");
fclose($log_file);

// Include header
$pageTitle = "Khalti Payment";
include 'includes/header.php';
?>

<!-- Khalti Payment Integration -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header" style="background-color: #6200ea; color: white;">
                    <h3 class="mb-0">Khalti Payment</h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="assets/images/khalti-logo.png" alt="Khalti" height="60" class="mb-3">
                        <h4>Payment Gateway</h4>
                        <p class="text-muted">Secure payment via Khalti digital wallet</p>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?><br>
                        <strong>Amount:</strong> Rs. <?php echo number_format($amount); ?>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button id="payment-button" class="btn btn-lg" style="background-color: #6200ea; color: white;">Pay with Khalti</button>
                        <a href="cart.php" class="btn btn-outline-secondary">Cancel Payment</a>
                    </div>
                </div>
                <div class="card-footer text-muted text-center">
                    <small>Your transaction is secured with industry standard encryption.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Khalti JS SDK -->
<script src="https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.22.0.0.0/khalti-checkout.iffe.js"></script>

<script>
    // Khalti payment configuration
    var config = {
        // replace this key with yours
        "publicKey": "<?php echo KHALTI_PUBLIC_KEY; ?>",
        "productIdentity": "<?php echo htmlspecialchars($order_id); ?>",
        "productName": "Event Booking",
        "productUrl": "http://localhost/nepali_event_booking/",
        "paymentPreference": [
            "KHALTI",
            "EBANKING",
            "MOBILE_BANKING",
            "CONNECT_IPS",
            "SCT"
        ],
        "eventHandler": {
            onSuccess (payload) {
                // hit merchant api for initiating verification
                console.log(payload);
                
                // Redirect to success page with transaction details
                window.location.href = "khalti_success.php?transaction_uuid=" + payload.product_identity + 
                                      "&token=" + payload.token +
                                      "&amount=" + payload.amount;
            },
            onError (error) {
                console.log(error);
                // Redirect to failure page
                window.location.href = "khalti_failure.php?transaction_uuid=<?php echo htmlspecialchars($order_id); ?>&message=" + error.message;
            },
            onClose () {
                console.log('widget is closing');
            }
        }
    };

    var checkout = new KhaltiCheckout(config);
    var btn = document.getElementById("payment-button");
    btn.onclick = function () {
        // minimum transaction amount must be 10, i.e 1000 in paisa
        checkout.show({amount: <?php echo $amount * 100; ?>});
    }
</script>

<?php include 'includes/footer.php'; ?> 