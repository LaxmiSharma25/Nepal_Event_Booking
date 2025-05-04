<?php
// Validate password
if (empty($password)) {
    $_SESSION['register_error'] = "Password is required";
    header("Location: register.php");
    exit();
}

// Password complexity validation
$uppercase = preg_match('@[A-Z]@', $password);
$lowercase = preg_match('@[a-z]@', $password);
$number    = preg_match('@[0-9]@', $password);
$specialChars = preg_match('@[^\w]@', $password);

if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
    $_SESSION['register_error'] = "Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character";
    header("Location: register.php");
    exit();
} 