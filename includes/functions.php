<?php
// Start session if not already started - make sure nothing is output before this
// Prevent "headers already sent" errors by using output buffering
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Function to redirect to a specific page
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Set flash message
 * 
 * @param string $message
 * @param string $type success, danger, warning, info
 * @return void
 */
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Display flash message
 * 
 * @return string HTML for message display
 */
function displayMessage() {
    if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        
        // Clear the message
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return '<div class="alert alert-' . $type . '">' . $message . '</div>';
    }
    return '';
}

/**
 * Add item to cart
 * 
 * @param object $conn Database connection
 * @param int $user_id User ID
 * @param int $service_id Service ID
 * @return bool|string True on success, error message on failure
 */
function addToCart($conn, $user_id, $service_id) {
    // Check if item already in cart
    $checkQuery = "SELECT * FROM cart WHERE user_id = ? AND service_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $user_id, $service_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Item already exists in cart
        return true;
    } else {
        // Add new item
        $insertQuery = "INSERT INTO cart (user_id, service_id) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("ii", $user_id, $service_id);
        
        if ($insertStmt->execute()) {
            return true;
        } else {
            return "Failed to add to cart: " . $conn->error;
        }
    }
}

// Function to format price with NPR
function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}

// Function to get all event categories
function getEventCategories($conn) {
    $sql = "SELECT * FROM event_categories";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Ensure image path is correct
            if (!empty($row['image']) && strpos($row['image'], '/') === false) {
                $row['image'] = 'assets/images/event_categories/' . $row['image'];
            }
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Function to get all service categories
function getServiceCategories($conn) {
    $sql = "SELECT * FROM service_categories";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Function to get services by category
function getServicesByCategory($conn, $categoryId) {
    $sql = "SELECT * FROM services WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $services = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    
    return $services;
}

// Function to get service by ID
function getServiceById($conn, $serviceId) {
    $sql = "SELECT * FROM services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to remove item from cart
function removeFromCart($conn, $cartId) {
    $sql = "DELETE FROM cart WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cartId);
    
    if ($stmt->execute()) {
        return true;
    }
    
    return false;
}

// Function to get cart items for a user
function getCartItems($conn, $userId) {
    $sql = "SELECT c.id as cart_id, s.*, sc.name as category_name 
            FROM cart c 
            JOIN services s ON c.service_id = s.id 
            JOIN service_categories sc ON s.category_id = sc.id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    return $items;
}

// Function to get cart total
function getCartTotal($conn, $userId) {
    $sql = "SELECT SUM(s.price) as total 
            FROM cart c 
            JOIN services s ON c.service_id = s.id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total'] ? $row['total'] : 0;
    }
    
    return 0;
}

// Function to check if user already has a service in cart
function isServiceInCart($conn, $userId, $serviceId) {
    $sql = "SELECT * FROM cart WHERE user_id = ? AND service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Get sanitized input
 * 
 * @param string $input
 * @param bool $is_email
 * @return string
 */
function sanitizeInput($input, $is_email = false) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    
    if ($is_email) {
        $input = filter_var($input, FILTER_SANITIZE_EMAIL);
    }
    
    return $input;
}

/**
 * Validate email
 * 
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get user information
 * 
 * @param object $conn Database connection
 * @param int $user_id User ID
 * @return array|bool User data or false if not found
 */
function getUserById($conn, $user_id) {
    $query = "SELECT id, name, email, phone, address, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Check if event exists
 * 
 * @param object $conn Database connection
 * @param int $event_id Event ID
 * @return bool
 */
function eventExists($conn, $event_id) {
    $query = "SELECT id FROM events WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Generate pagination links
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $url_pattern URL pattern with %d placeholder for page number
 * @return string HTML for pagination links
 */
function getPaginationLinks($current_page, $total_pages, $url_pattern) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $links = '<ul class="pagination">';
    
    // Previous link
    if ($current_page > 1) {
        $links .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $current_page - 1) . '">&laquo; Previous</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>';
    }
    
    // Page links
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $links .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $links .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    // Next link
    if ($current_page < $total_pages) {
        $links .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $current_page + 1) . '">Next &raquo;</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>';
    }
    
    $links .= '</ul>';
    
    return $links;
}
?> 