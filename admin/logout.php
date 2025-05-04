<?php
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);

// Redirect to admin login page
$_SESSION['message'] = "You have been logged out successfully.";
$_SESSION['message_type'] = "success";
redirect('index.php');
?> 