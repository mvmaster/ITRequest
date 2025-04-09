<?php
/**
 * Session Management
 * 
 * ไฟล์นี้ใช้สำหรับจัดการ Session ของผู้ใช้งาน
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configure session parameters
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.use_only_cookies', 1); // Forces sessions to only use cookies
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS only

// Set session timeout (30 minutes)
ini_set('session.gc_maxlifetime', 1800);

// Check if session has expired (after 30 minutes of inactivity)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../auth/login.php");
    exit;
}

// Update last activity time stamp
$_SESSION['last_activity'] = time();

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has admin role
 * 
 * @return bool True if user has admin role, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user has IT staff role
 * 
 * @return bool True if user has IT staff role, false otherwise
 */
function isITStaff() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'it_staff' || $_SESSION['user_role'] === 'admin');
}

/**
 * Get current user ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user role
 * 
 * @return string|null User role if logged in, null otherwise
 */
function getCurrentUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

/**
 * Get current user full name
 * 
 * @return string User full name
 */
function getCurrentUserName() {
    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        return $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    }
    return 'ผู้ใช้งาน';
}

/**
 * Get current user employee ID
 * 
 * @return string|null Employee ID if logged in, null otherwise
 */
function getCurrentEmployeeId() {
    return isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : null;
}