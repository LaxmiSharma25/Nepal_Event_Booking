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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    // Validate form data
    if (empty($name)) {
        $_SESSION['message'] = "Category name is required";
        $_SESSION['message_type'] = "error";
        redirect('events.php');
    }
    
    if (empty($description)) {
        $_SESSION['message'] = "Category description is required";
        $_SESSION['message_type'] = "error";
        redirect('events.php');
    }
    
    // Handle image upload
    $image = $imageUploadError = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        // Define allowed file types
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $imageUploadError = "Only JPG, JPEG, PNG, and GIF files are allowed";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $imageUploadError = "File size should be less than 2MB";
        } else {
            // Generate unique filename
            $image = time() . '_' . basename($_FILES['image']['name']);
            $target_path = "../assets/images/" . $image;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $imageUploadError = "Error uploading file";
            }
        }
        
        if (!empty($imageUploadError)) {
            $_SESSION['message'] = $imageUploadError;
            $_SESSION['message_type'] = "error";
            redirect('events.php');
        }
    }
    
    // Insert or update category
    if ($categoryId > 0) {
        // Update existing category
        if (!empty($image)) {
            // With new image
            $sql = "UPDATE event_categories SET name = ?, description = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $description, $image, $categoryId);
        } else {
            // Without new image
            $sql = "UPDATE event_categories SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $name, $description, $categoryId);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Event category updated successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating event category: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        // Insert new category
        if (empty($image)) {
            // Set default image if none provided
            $image = "default-event.jpg";
        }
        
        $sql = "INSERT INTO event_categories (name, description, image) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $description, $image);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Event category added successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding event category: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
    }
    
    redirect('events.php');
} else {
    // Invalid request method
    $_SESSION['message'] = "Invalid request";
    $_SESSION['message_type'] = "error";
    redirect('events.php');
}
?> 