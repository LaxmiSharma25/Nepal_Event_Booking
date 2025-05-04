<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Category ID is required']);
    exit;
}

// Get category ID
$category_id = (int)$_GET['id'];

// Get category details
$sql = "SELECT * FROM event_categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $category = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode($category);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Category not found']);
}
exit;
?> 