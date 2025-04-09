<?php
/**
 * Common Functions File
 * 
 * ไฟล์นี้รวบรวมฟังก์ชันที่ใช้งานทั่วไปในระบบ
 */

// Include database connection
require_once __DIR__ . '/../config/database.php';

/**
 * Generate a unique reference number for requests
 * 
 * @return string Reference number (e.g., REQ-20250409-001)
 */
function generateReferenceNumber() {
    $date = date('Ymd');
    $conn = connectDB();
    
    // Get the last reference number for today
    $sql = "SELECT reference_no FROM requests 
            WHERE reference_no LIKE 'REQ-{$date}-%' 
            ORDER BY request_id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastRef = $row['reference_no'];
        $lastNum = intval(substr($lastRef, -3));
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }
    
    // Format the number with leading zeros
    $formattedNum = str_pad($newNum, 3, '0', STR_PAD_LEFT);
    $referenceNo = "REQ-{$date}-{$formattedNum}";
    
    closeDB($conn);
    return $referenceNo;
}

/**
 * Get request status name in Thai
 * 
 * @param string $status Status in English
 * @return string Status in Thai
 */
function getStatusThai($status) {
    $statusMap = [
        'pending' => 'รอดำเนินการ',
        'in_progress' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        'rejected' => 'ปฏิเสธ',
        'closed' => 'ปิดงาน'
    ];
    
    return $statusMap[$status] ?? $status;
}

/**
 * Get status badge HTML
 * 
 * @param string $status Status in English
 * @return string HTML badge with appropriate color
 */
function getStatusBadge($status) {
    $badgeClass = '';
    
    switch ($status) {
        case 'pending':
            $badgeClass = 'bg-warning text-dark';
            break;
        case 'in_progress':
            $badgeClass = 'bg-info text-dark';
            break;
        case 'completed':
            $badgeClass = 'bg-success';
            break;
        case 'rejected':
            $badgeClass = 'bg-danger';
            break;
        case 'closed':
            $badgeClass = 'bg-secondary';
            break;
        default:
            $badgeClass = 'bg-primary';
    }
    
    $statusThai = getStatusThai($status);
    return "<span class='badge {$badgeClass}'>{$statusThai}</span>";
}

/**
 * Get priority badge HTML
 * 
 * @param string $priority Priority level
 * @return string HTML badge with appropriate color
 */
function getPriorityBadge($priority) {
    $badgeClass = '';
    $priorityThai = '';
    
    switch ($priority) {
        case 'low':
            $badgeClass = 'bg-success';
            $priorityThai = 'ต่ำ';
            break;
        case 'medium':
            $badgeClass = 'bg-info';
            $priorityThai = 'ปานกลาง';
            break;
        case 'high':
            $badgeClass = 'bg-warning text-dark';
            $priorityThai = 'สูง';
            break;
        case 'urgent':
            $badgeClass = 'bg-danger';
            $priorityThai = 'เร่งด่วน';
            break;
        default:
            $badgeClass = 'bg-secondary';
            $priorityThai = $priority;
    }
    
    return "<span class='badge {$badgeClass}'>{$priorityThai}</span>";
}

/**
 * Format date and time to Thai format
 * 
 * @param string $datetime Date and time in MySQL format
 * @param bool $withTime Include time in the result
 * @return string Formatted date and time
 */
function formatDateThai($datetime, $withTime = true) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    
    $date = new DateTime($datetime);
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $day = $date->format('j');
    $month = $thaiMonths[intval($date->format('n'))];
    $year = $date->format('Y') + 543; // Convert to Buddhist Era
    
    $result = "$day $month $year";
    
    if ($withTime) {
        $time = $date->format('H:i');
        $result .= " เวลา $time น.";
    }
    
    return $result;
}

/**
 * Get all request types from database
 * 
 * @return array Array of request types
 */
function getRequestTypes() {
    $conn = connectDB();
    $sql = "SELECT * FROM request_types WHERE status = 1 ORDER BY type_name ASC";
    $result = $conn->query($sql);
    
    $types = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
    }
    
    closeDB($conn);
    return $types;
}

/**
 * Get user information by ID
 * 
 * @param int $userId User ID
 * @return array|null User data or null if not found
 */
function getUserById($userId) {
    $conn = connectDB();
    $userId = (int)$userId;
    
    $sql = "SELECT * FROM users WHERE user_id = $userId";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        closeDB($conn);
        return $user;
    }
    
    closeDB($conn);
    return null;
}

/**
 * Check if user is authenticated
 * If not, redirect to login page
 */
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . $GLOBALS['base_url'] . "/auth/login.php");
        exit;
    }
}

/**
 * Check if user has admin/IT staff role
 * If not, redirect to unauthorized page
 */
function checkAdminAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'it_staff')) {
        header("Location: " . $GLOBALS['base_url'] . "/unauthorized.php");
        exit;
    }
}

/**
 * Create notification for user
 * 
 * @param int $userId User ID to send notification to
 * @param int $requestId Related request ID
 * @param string $message Notification message
 * @return bool Success status
 */
function createNotification($userId, $requestId, $message) {
    $conn = connectDB();
    
    $userId = (int)$userId;
    $requestId = (int)$requestId;
    $message = sanitizeInput($message, $conn);
    
    $sql = "INSERT INTO notifications (user_id, request_id, message) 
            VALUES ($userId, $requestId, '$message')";
    
    $result = $conn->query($sql);
    closeDB($conn);
    
    return $result ? true : false;
}

/**
 * Add request log entry
 * 
 * @param int $requestId Request ID
 * @param string $status New status
 * @param string $comment Comment
 * @param int $performedBy User ID who performed the action
 * @return bool Success status
 */
function addRequestLog($requestId, $status, $comment, $performedBy) {
    $conn = connectDB();
    
    $requestId = (int)$requestId;
    $status = sanitizeInput($status, $conn);
    $comment = sanitizeInput($comment, $conn);
    $performedBy = (int)$performedBy;
    
    $sql = "INSERT INTO request_logs (request_id, status, comment, performed_by) 
            VALUES ($requestId, '$status', '$comment', $performedBy)";
    
    $result = $conn->query($sql);
    closeDB($conn);
    
    return $result ? true : false;
}

/**
 * Upload file and return file path
 * 
 * @param array $file $_FILES array element
 * @param string $targetDir Target directory
 * @return array File info with status and message
 */
function uploadFile($file, $targetDir = '../uploads/') {
    $result = [
        'status' => false,
        'message' => '',
        'file_name' => '',
        'file_path' => '',
        'file_type' => '',
        'file_size' => 0
    ];
    
    // Check if file was uploaded without errors
    if ($file['error'] != 0) {
        $result['message'] = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
        return $result;
    }
    
    // Get file info
    $fileName = basename($file['name']);
    $fileSize = $file['size'];
    $fileType = $file['type'];
    
    // Create a unique filename
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetFilePath = $targetDir . $newFileName;
    
    // Check if file already exists
    if (file_exists($targetFilePath)) {
        $result['message'] = 'ไฟล์นี้มีอยู่แล้ว';
        return $result;
    }
    
    // Check file size (max 5MB)
    if ($fileSize > 5000000) {
        $result['message'] = 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)';
        return $result;
    }
    
    // Allow certain file formats
    $allowTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
    if (!in_array(strtolower($fileExt), $allowTypes)) {
        $result['message'] = 'ประเภทไฟล์ไม่ได้รับอนุญาต';
        return $result;
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        $result['status'] = true;
        $result['message'] = 'อัปโหลดไฟล์สำเร็จ';
        $result['file_name'] = $fileName;
        $result['file_path'] = $newFileName;
        $result['file_type'] = $fileType;
        $result['file_size'] = $fileSize;
    } else {
        $result['message'] = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
    }
    
    return $result;
}