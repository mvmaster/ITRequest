<?php
// Include necessary files
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../auth/session.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit;
}

// Get current user info
$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Initialize variables
$message = '';
$messageType = '';

// Connect to database
$conn = connectDB();

// Get user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    // User not found
    closeDB($conn);
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department = $_POST['department'] ?? '';
    
    // Validate input
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = 'กรุณากรอกชื่อ';
    }
    
    if (empty($lastName)) {
        $errors[] = 'กรุณากรอกนามสกุล';
    }
    
    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($