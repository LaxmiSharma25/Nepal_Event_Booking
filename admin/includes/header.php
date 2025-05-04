<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    $_SESSION['message'] = "Please login to access admin panel";
    $_SESSION['message_type'] = "error";
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Nepali Event Booking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            display: flex;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #1d3557;
            color: #fff;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }
        
        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background-color: #457b9d;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content-wrapper {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .dashboard-header {
            background-color: #fff;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-title h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #1d3557;
        }
        
        .admin-actions {
            display: flex;
            align-items: center;
        }
        
        .admin-actions .admin-name {
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="bookings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'class="active"' : ''; ?>><i class="fas fa-calendar-check"></i> Bookings</a></li>
            <li><a href="events.php" <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'class="active"' : ''; ?>><i class="fas fa-calendar-day"></i> Events</a></li>
            <li><a href="services.php" <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'class="active"' : ''; ?>><i class="fas fa-concierge-bell"></i> Services</a></li>
            <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Users</a></li>
            <li><a href="testimonials.php" <?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' ? 'class="active"' : ''; ?>><i class="fas fa-comment"></i> Testimonials</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="content-wrapper">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1><?php echo isset($pageTitle) ? $pageTitle : 'Admin Panel'; ?></h1>
            </div>
            <div class="admin-actions">
                <span class="admin-name">Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                <a href="logout.php" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.9rem;">Logout</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?> 