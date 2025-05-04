<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session status - remove this after debugging
$_SESSION['debug_test'] = 'Session is working';

// Force-check login status
$isUserLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Nepali Event Booking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <h1>Nepali Event Booking</h1>
                </a>
            </div>
            
            <div class="hamburger-menu">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    
                    <!-- Debug: Show session status -->
                    <?php if (isset($_SESSION['debug_test'])): ?>
                    <li style="color: white; font-size: 10px;">Session OK | Logged in: <?php echo $isUserLoggedIn ? 'Yes' : 'No'; ?></li>
                    <?php endif; ?>
                    <!-- End debug -->
                    
                    <?php if (!$isUserLoggedIn): ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                    
                    <?php if ($isUserLoggedIn): ?>
                        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                        <li class="dropdown">
                            <a href="account.php"><i class="fas fa-user"></i> My Account</a>
                            <ul class="dropdown-menu">
                                <li><a href="account.php">Dashboard</a></li>
                                <li><a href="bookings.php">My Bookings</a></li>
                                <li><a href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container"><?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php echo $_SESSION['message']; ?>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?> 