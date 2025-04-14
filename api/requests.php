<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Requests API
 * 
 * API สำหรับจัดการคำขอ IT Request
 */

// Include API configuration
require_once 'config.php';

// Validate request method
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST', 'PUT', 'DELETE'])) {
    sendJsonResponse(
        apiResponse('error', 'Invalid request method'),
        405 // Method Not Allowed
    );
}

// Define API endpoint based on request method and parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ตรวจสอบ HTTP Method และดำเนินการตาม action
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($action === 'all' || $action === 'user' || $action === 'detail' || $action === 'types') {
            handleGetRequests($action);
        } elseif ($action === 'delete') {
            // บางครั้ง delete อาจถูกส่งมาเป็น GET
            handleDeleteRequest();
        } else {
            sendJsonResponse(
                apiResponse('error', 'Invalid action for GET method', ['action' => $action]),
                400 // Bad Request
            );
        }
        break;
        
    case 'POST':
        if ($action === 'create') {
            createRequest();
        } elseif ($action === 'status') {
            updateRequestStatus();
        } elseif ($action === 'assign') {
            assignRequest();
        } else {
            sendJsonResponse(
                apiResponse('error', 'Invalid action for POST method', ['action' => $action]),
                400 // Bad Request
            );
        }
        break;
        
    default:
        sendJsonResponse(
            apiResponse('error', 'Unsupported HTTP method', ['method' => $_SERVER['REQUEST_METHOD']]),
            405 // Method Not Allowed
        );
}

/**
 * Handle GET requests
 */
function handleGetRequests($action) {
    // Check authentication
    apiCheckAuth();
    
    // Handle different GET actions
    switch ($action) {
        case 'all':
            apiCheckAdminAuth(); // Only admin/IT staff can view all requests
            getAllRequests();
            break;
        case 'user':
            // Get all requests for current user
            getUserRequests();
            break;
        case 'detail':
            // Get request details
            if (isset($_GET['id'])) {
                getRequestDetails($_GET['id']);
            } else {
                sendJsonResponse(
                    apiResponse('error', 'Request ID is required'),
                    400 // Bad Request
                );
            }
            break;
        case 'types':
            // Get request types
            getApiRequestTypes();
            break;
        default:
            sendJsonResponse(
                apiResponse('error', 'Invalid action'),
                400 // Bad Request
            );
    }
}

/**
 * Handle POST requests
 */
function handlePostRequests($action) {
    // Check authentication
    apiCheckAuth();
    
    // Handle different POST actions
    switch ($action) {
        case 'create':
            // Create new request
            createRequest();
            break;
        case 'status':
            // Update request status
            apiCheckAdminAuth(); // Only admin/IT staff can update status
            updateRequestStatus();
            break;
        case 'assign':
            // Assign request to IT staff
            apiCheckAdminAuth(); // Only admin/IT staff can assign requests
            assignRequest();
            break;
        default:
            sendJsonResponse(
                apiResponse('error', 'Invalid action'),
                400 // Bad Request
            );
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequests($action) {
    // Check authentication
    apiCheckAuth();
    
    // Handle different PUT actions
    switch ($action) {
        case 'update':
            // Update request
            updateRequest();
            break;
        default:
            sendJsonResponse(
                apiResponse('error', 'Invalid action'),
                400 // Bad Request
            );
    }
}

/**
 * Get all requests for admin/IT staff
 */
function getAllRequests() {
    $conn = connectDB();
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status'], $conn) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    // Build status condition
    $statusCondition = '';
    if (!empty($status)) {
        $statusCondition = "WHERE r.status = '$status'";
    }
    
    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM requests r $statusCondition";
    $countResult = $conn->query($countSql);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get requests with pagination
    $sql = "SELECT r.*, 
            u.first_name, u.last_name, u.employee_id,
            t.type_name,
            a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM requests r
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN users a ON r.assigned_to = a.user_id
            LEFT JOIN request_types t ON r.type_id = t.type_id
            $statusCondition
            ORDER BY r.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($sql);
    $requests = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format data for response
            $row['status_badge'] = getStatusBadge($row['status']);
            $row['priority_badge'] = getPriorityBadge($row['priority']);
            $row['created_at_formatted'] = formatDateThai($row['created_at']);
            $row['requester_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $row['assigned_to_name'] = $row['assigned_to'] ? 
                                      $row['assigned_first_name'] . ' ' . $row['assigned_last_name'] : '-';
            
            $requests[] = $row;
        }
    }
    
    closeDB($conn);
    
    // Prepare pagination data
    $pagination = [
        'total' => (int)$totalRecords,
        'per_page' => $limit,
        'current_page' => $page,
        'total_pages' => $totalPages
    ];
    
    sendJsonResponse(
        apiResponse('success', 'Requests retrieved successfully', [
            'requests' => $requests,
            'pagination' => $pagination
        ])
    );
}

/**
 * Get requests for current user
 */
function getUserRequests() {
    $conn = connectDB();
    $userId = getCurrentUserId();
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status'], $conn) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    // Build status condition
    $statusCondition = "WHERE r.user_id = $userId";
    if (!empty($status)) {
        $statusCondition .= " AND r.status = '$status'";
    }
    
    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM requests r $statusCondition";
    $countResult = $conn->query($countSql);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get requests with pagination
    $sql = "SELECT r.*, 
            t.type_name,
            a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM requests r
            LEFT JOIN users a ON r.assigned_to = a.user_id
            LEFT JOIN request_types t ON r.type_id = t.type_id
            $statusCondition
            ORDER BY r.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($sql);
    $requests = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format data for response
            $row['status_badge'] = getStatusBadge($row['status']);
            $row['priority_badge'] = getPriorityBadge($row['priority']);
            $row['created_at_formatted'] = formatDateThai($row['created_at']);
            $row['assigned_to_name'] = $row['assigned_to'] ? 
                                      $row['assigned_first_name'] . ' ' . $row['assigned_last_name'] : '-';
            
            // Check for recent updates (within last 24 hours)
            $updateTime = strtotime($row['updated_at']);
            $currentTime = time();
            $row['is_recent'] = ($currentTime - $updateTime < 86400); // 24 hours in seconds
            
            $requests[] = $row;
        }
    }
    
    closeDB($conn);
    
    // Prepare pagination data
    $pagination = [
        'total' => (int)$totalRecords,
        'per_page' => $limit,
        'current_page' => $page,
        'total_pages' => $totalPages
    ];
    
    sendJsonResponse(
        apiResponse('success', 'Requests retrieved successfully', [
            'requests' => $requests,
            'pagination' => $pagination
        ])
    );
}

/**
 * Get request details by ID
 */
function getRequestDetails($requestId) {
    $conn = connectDB();
    $requestId = (int)$requestId;
    $userId = getCurrentUserId();
    $userRole = getCurrentUserRole();
    
    // Get request details
    $sql = "SELECT r.*, 
            u.first_name, u.last_name, u.employee_id, u.email, u.phone, u.department,
            t.type_name,
            a.first_name as assigned_first_name, a.last_name as assigned_last_name
            FROM requests r
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN users a ON r.assigned_to = a.user_id
            LEFT JOIN request_types t ON r.type_id = t.type_id
            WHERE r.request_id = $requestId";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        
        // Check if user has permission to view this request
        if ($userRole !== 'admin' && $userRole !== 'it_staff' && $request['user_id'] != $userId) {
            sendJsonResponse(
                apiResponse('error', 'You do not have permission to view this request'),
                403 // Forbidden
            );
            return;
        }
        
        // Format data for response
        $request['status_badge'] = getStatusBadge($request['status']);
        $request['priority_badge'] = getPriorityBadge($request['priority']);
        $request['created_at_formatted'] = formatDateThai($request['created_at']);
        $request['updated_at_formatted'] = formatDateThai($request['updated_at']);
        $request['completed_at_formatted'] = formatDateThai($request['completed_at']);
        $request['requester_name'] = $request['first_name'] . ' ' . $request['last_name'];
        $request['assigned_to_name'] = $request['assigned_to'] ? 
                                      $request['assigned_first_name'] . ' ' . $request['assigned_last_name'] : '-';
        
        // Get attachments
        $attachmentsSql = "SELECT * FROM attachments WHERE request_id = $requestId";
        $attachmentsResult = $conn->query($attachmentsSql);
        $attachments = [];
        
        if ($attachmentsResult->num_rows > 0) {
            while ($attachment = $attachmentsResult->fetch_assoc()) {
                $attachments[] = $attachment;
            }
        }
        
        // Get request logs/history
        $logsSql = "SELECT l.*, u.first_name, u.last_name 
                    FROM request_logs l
                    LEFT JOIN users u ON l.performed_by = u.user_id
                    WHERE l.request_id = $requestId
                    ORDER BY l.created_at DESC";
        $logsResult = $conn->query($logsSql);
        $logs = [];
        
        if ($logsResult->num_rows > 0) {
            while ($log = $logsResult->fetch_assoc()) {
                $log['status_badge'] = getStatusBadge($log['status']);
                $log['status_thai'] = getStatusThai($log['status']);
                $log['created_at_formatted'] = formatDateThai($log['created_at']);
                $log['performed_by_name'] = $log['first_name'] . ' ' . $log['last_name'];
                $logs[] = $log;
            }
        }
        
        closeDB($conn);
        
        sendJsonResponse(
            apiResponse('success', 'Request details retrieved successfully', [
                'request' => $request,
                'attachments' => $attachments,
                'logs' => $logs
            ])
        );
    } else {
        closeDB($conn);
        sendJsonResponse(
            apiResponse('error', 'Request not found'),
            404 // Not Found
        );
    }
}

/**
 * Get all request types
 */
function getApiRequestTypes() {
    // คงโค้ดเดิมไว้แต่เปลี่ยนชื่อฟังก์ชัน
    $conn = connectDB();
    $sql = "SELECT * FROM request_types WHERE status = 1 ORDER BY type_name";
    $result = $conn->query($sql);
    $types = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
    }
    
    closeDB($conn);
    
    sendJsonResponse(
        apiResponse('success', 'Request types retrieved successfully', [
            'types' => $types
        ])
    );
}

/**
 * Create new request
 */
function createRequest() {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If data is not in JSON format, try to get from POST
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['type_id']) || empty($data['subject']) || empty($data['description'])) {
        sendJsonResponse(
            apiResponse('error', 'Missing required fields'),
            400 // Bad Request
        );
        return;
    }
    
    $conn = connectDB();
    
    // Sanitize input
    $typeId = (int)$data['type_id'];
    $subject = sanitizeInput($data['subject'], $conn);
    $description = sanitizeInput($data['description'], $conn);
    $contactInfo = isset($data['contact_info']) ? sanitizeInput($data['contact_info'], $conn) : '';
    $priority = isset($data['priority']) ? sanitizeInput($data['priority'], $conn) : 'medium';
    $userId = getCurrentUserId();
    
    // Generate reference number
    $referenceNo = generateReferenceNumber();
    
    // Create request
    $sql = "INSERT INTO requests (reference_no, user_id, type_id, subject, description, contact_info, priority, status) 
            VALUES ('$referenceNo', $userId, $typeId, '$subject', '$description', '$contactInfo', '$priority', 'pending')";
    
    if ($conn->query($sql)) {
        $requestId = $conn->insert_id;
        
        // Add initial log
        addRequestLog($requestId, 'pending', 'คำขอถูกสร้างขึ้น', $userId);
        
        // Handle file uploads
        $attachments = [];
        if (isset($_FILES['attachments'])) {
            // Create upload directory if it doesn't exist
            $uploadDir = '../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $files = $_FILES['attachments'];
            
            // If single file
            if (!is_array($files['name'])) {
                $fileUpload = [
                    'name' => $files['name'],
                    'type' => $files['type'],
                    'tmp_name' => $files['tmp_name'],
                    'error' => $files['error'],
                    'size' => $files['size']
                ];
                
                if ($fileUpload['error'] === 0) {
                    $uploadResult = uploadFile($fileUpload, $uploadDir);
                    
                    if ($uploadResult['status']) {
                        // Save attachment info to database
                        $fileName = sanitizeInput($uploadResult['file_name'], $conn);
                        $filePath = sanitizeInput($uploadResult['file_path'], $conn);
                        $fileType = sanitizeInput($uploadResult['file_type'], $conn);
                        $fileSize = (int)$uploadResult['file_size'];
                        
                        $attachSql = "INSERT INTO attachments (request_id, file_name, file_path, file_type, file_size) 
                                    VALUES ($requestId, '$fileName', '$filePath', '$fileType', $fileSize)";
                        
                        if ($conn->query($attachSql)) {
                            $attachments[] = [
                                'attachment_id' => $conn->insert_id,
                                'file_name' => $fileName,
                                'file_path' => $filePath
                            ];
                        }
                    }
                }
            } 
            // If multiple files
            else {
                $fileCount = count($files['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] === 0) { // No error
                        $fileUpload = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        $uploadResult = uploadFile($fileUpload, $uploadDir);
                        
                        if ($uploadResult['status']) {
                            // Save attachment info to database
                            $fileName = sanitizeInput($uploadResult['file_name'], $conn);
                            $filePath = sanitizeInput($uploadResult['file_path'], $conn);
                            $fileType = sanitizeInput($uploadResult['file_type'], $conn);
                            $fileSize = (int)$uploadResult['file_size'];
                            
                            $attachSql = "INSERT INTO attachments (request_id, file_name, file_path, file_type, file_size) 
                                        VALUES ($requestId, '$fileName', '$filePath', '$fileType', $fileSize)";
                            
                            if ($conn->query($attachSql)) {
                                $attachments[] = [
                                    'attachment_id' => $conn->insert_id,
                                    'file_name' => $fileName,
                                    'file_path' => $filePath
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        closeDB($conn);
        
        sendJsonResponse(
            apiResponse('success', 'Request created successfully', [
                'request_id' => $requestId,
                'reference_no' => $referenceNo,
                'attachments' => $attachments
            ])
        );
    } else {
        closeDB($conn);
        sendJsonResponse(
            apiResponse('error', 'Failed to create request: ' . $conn->error),
            500 // Internal Server Error
        );
    }
}

/**
 * Update request status
 */
function updateRequestStatus() {
    // เพิ่ม error reporting เพื่อดูข้อผิดพลาด (เอาออกเมื่อใช้งานจริง)
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    
    // Get data from POST
    $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate input
    if ($requestId <= 0) {
        sendJsonResponse(
            apiResponse('error', 'Invalid request ID', ['request_id' => $requestId]),
            400 // Bad Request
        );
        return;
    }
    
    if (empty($comment)) {
        sendJsonResponse(
            apiResponse('error', 'Comment is required'),
            400 // Bad Request
        );
        return;
    }
    
    // Connect to database
    $conn = connectDB();
    
    // Clean input to prevent SQL injection
    $requestId = (int)$requestId; // Cast to integer for safety
    $status = $conn->real_escape_string($status);
    $comment = $conn->real_escape_string($comment);
    $userId = getCurrentUserId();
    
    // Check if request exists
    $checkSql = "SELECT r.*, u.user_id as requester_id, r.status as current_status 
                FROM requests r 
                LEFT JOIN users u ON r.user_id = u.user_id
                WHERE r.request_id = $requestId";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        $request = $checkResult->fetch_assoc();
        $currentStatus = $request['current_status'];
        
        // If status is not provided, use current status (for comments only)
        if (empty($status)) {
            $status = $currentStatus;
        }
        
        // Update request status
        $completedAt = ($status === 'completed' && $currentStatus !== 'completed') ? 
            "completed_at = CURRENT_TIMESTAMP," : "";
        
        $updateSql = "UPDATE requests SET status = '$status', $completedAt updated_at = CURRENT_TIMESTAMP 
                      WHERE request_id = $requestId";
        
        if ($conn->query($updateSql)) {
            // Add log entry
            $logSql = "INSERT INTO request_logs (request_id, status, comment, performed_by) 
                      VALUES ($requestId, '$status', '$comment', $userId)";
            $conn->query($logSql);
            
            // Create notification for requester if status has changed
            if ($status !== $currentStatus) {
                $statusThai = getStatusThai($status);
                $notificationMsg = "คำขอ #{$request['reference_no']} ได้เปลี่ยนสถานะเป็น \"{$statusThai}\"";
                
                $notifSql = "INSERT INTO notifications (user_id, request_id, message) 
                            VALUES ({$request['requester_id']}, $requestId, '$notificationMsg')";
                $conn->query($notifSql);
            }
            
            closeDB($conn);
            
            // ตรวจสอบว่ามีการส่งมาจากหน้าเว็บและต้องการ redirect กลับ
            if (!empty($_SERVER['HTTP_REFERER']) && 
                (strpos($_SERVER['HTTP_REFERER'], 'request-details.php') !== false || 
                 strpos($_SERVER['HTTP_REFERER'], 'manage-requests.php') !== false)) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
            
            sendJsonResponse(
                apiResponse('success', 'Request status updated successfully', [
                    'request_id' => $requestId,
                    'status' => $status,
                    'status_thai' => getStatusThai($status)
                ])
            );
        } else {
            closeDB($conn);
            sendJsonResponse(
                apiResponse('error', 'Database error: ' . $conn->error),
                500 // Internal Server Error
            );
        }
    } else {
        closeDB($conn);
        sendJsonResponse(
            apiResponse('error', 'Request not found', ['request_id' => $requestId]),
            404 // Not Found
        );
    }
}

/**
 * Assign request to IT staff
 */
function assignRequest() {
    // Get data from POST
    $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $assignedTo = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate input
    if ($requestId <= 0 || $assignedTo <= 0 || empty($comment)) {
        sendJsonResponse(
            apiResponse('error', 'Missing or invalid input', [
                'request_id' => $requestId,
                'assigned_to' => $assignedTo,
                'has_comment' => !empty($comment)
            ]),
            400 // Bad Request
        );
        return;
    }
    
    // Connect to database
    $conn = connectDB();
    
    // Clean input to prevent SQL injection
    $requestId = (int)$requestId;
    $assignedTo = (int)$assignedTo;
    $comment = $conn->real_escape_string($comment);
    $userId = getCurrentUserId();
    
    // Check if request exists
    $checkSql = "SELECT r.*, u.user_id as requester_id FROM requests r 
                 LEFT JOIN users u ON r.user_id = u.user_id
                 WHERE r.request_id = $requestId";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        $request = $checkResult->fetch_assoc();
        
        // Get assignee name
        $assigneeSql = "SELECT first_name, last_name FROM users WHERE user_id = $assignedTo";
        $assigneeResult = $conn->query($assigneeSql);
        $assigneeName = '';
        
        if ($assigneeResult && $assigneeResult->num_rows > 0) {
            $assignee = $assigneeResult->fetch_assoc();
            $assigneeName = $assignee['first_name'] . ' ' . $assignee['last_name'];
        }
        
        // Update request assigned_to and set to in_progress
        $updateSql = "UPDATE requests SET assigned_to = $assignedTo, status = 'in_progress', updated_at = CURRENT_TIMESTAMP 
                     WHERE request_id = $requestId";
        
        if ($conn->query($updateSql)) {
            // Add log entry
            $logComment = $comment . " (มอบหมายให้: $assigneeName)";
            $logSql = "INSERT INTO request_logs (request_id, status, comment, performed_by) 
                      VALUES ($requestId, 'in_progress', '$logComment', $userId)";
            $conn->query($logSql);
            
            // Create notification for requester
            $notificationMsg = "คำขอ #{$request['reference_no']} ได้รับมอบหมายให้ $assigneeName ดำเนินการ";
            $notifSql = "INSERT INTO notifications (user_id, request_id, message) 
                        VALUES ({$request['requester_id']}, $requestId, '$notificationMsg')";
            $conn->query($notifSql);
            
            closeDB($conn);
            
            // ตรวจสอบว่ามีการส่งมาจากหน้าเว็บและต้องการ redirect กลับ
            if (!empty($_SERVER['HTTP_REFERER']) && 
                (strpos($_SERVER['HTTP_REFERER'], 'request-details.php') !== false || 
                 strpos($_SERVER['HTTP_REFERER'], 'manage-requests.php') !== false)) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
            
            sendJsonResponse(
                apiResponse('success', 'Request assigned successfully', [
                    'request_id' => $requestId,
                    'assigned_to' => $assignedTo,
                    'assigned_name' => $assigneeName
                ])
            );
        } else {
            closeDB($conn);
            sendJsonResponse(
                apiResponse('error', 'Database error: ' . $conn->error),
                500 // Internal Server Error
            );
        }
    } else {
        closeDB($conn);
        sendJsonResponse(
            apiResponse('error', 'Request not found', ['request_id' => $requestId]),
            404 // Not Found
        );
    }
}

/**
 * Update request
 */
function updateRequest() {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If data is not in JSON format, try to get from POST
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['request_id']) || empty($data['subject']) || empty($data['description'])) {
        sendJsonResponse(
            apiResponse('error', 'Missing required fields'),
            400 // Bad Request
        );
        return;
    }
    
    $conn = connectDB();
    
    // Sanitize input
    $requestId = (int)$data['request_id'];
    $typeId = (int)$data['type_id'];
    $subject = sanitizeInput($data['subject'], $conn);
    $description = sanitizeInput($data['description'], $conn);
    $contactInfo = isset($data['contact_info']) ? sanitizeInput($data['contact_info'], $conn) : '';
    $priority = isset($data['priority']) ? sanitizeInput($data['priority'], $conn) : 'medium';
    $userId = getCurrentUserId();
    
    // Check if request exists and user has permission to update
    $checkSql = "SELECT user_id, status FROM requests WHERE request_id = $requestId";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        $request = $checkResult->fetch_assoc();
        
        // Only the request creator or admin/IT staff can update
        if ($request['user_id'] != $userId && !isITStaff()) {
            closeDB($conn);
            sendJsonResponse(
                apiResponse('error', 'You do not have permission to update this request'),
                403 // Forbidden
            );
            return;
        }
        
        // Check if request can be updated (only pending requests can be updated by users)
        if ($request['status'] != 'pending' && !isITStaff() && $request['user_id'] == $userId) {
            closeDB($conn);
            sendJsonResponse(
                apiResponse('error', 'This request cannot be updated because it is already in progress'),
                400 // Bad Request
            );
            return;
        }
        
        // Update request
        $sql = "UPDATE requests SET 
                type_id = $typeId, 
                subject = '$subject', 
                description = '$description', 
                contact_info = '$contactInfo', 
                priority = '$priority', 
                updated_at = CURRENT_TIMESTAMP 
                WHERE request_id = $requestId";
        
        if ($conn->query($sql)) {
            // Add log entry
            addRequestLog($requestId, $request['status'], 'อัพเดตข้อมูลคำขอ', $userId);
            
            closeDB($conn);
            
            sendJsonResponse(
                apiResponse('success', 'Request updated successfully', [
                    'request_id' => $requestId
                ])
            );
        } else {
            closeDB($conn);
            sendJsonResponse(
                apiResponse('error', 'Failed to update request: ' . $conn->error),
                500 // Internal Server Error
            );
        }
    } else {
        closeDB($conn);
        sendJsonResponse(
            apiResponse('error', 'Request not found'),
            404 // Not Found
        );
    }
}


/**
 * Handle DELETE requests
 */

function handleDeleteRequest() {
    // Check if admin
    if (!isAdmin()) {
        sendJsonResponse(
            apiResponse('error', 'Permission denied: Only administrators can delete requests'),
            403 // Forbidden
        );
        return;
    }
    
    // Get request ID
    $requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($requestId <= 0) {
        sendJsonResponse(
            apiResponse('error', 'Invalid request ID', ['request_id' => $requestId]),
            400 // Bad Request
        );
        return;
    }
    
    $conn = connectDB();
    
    // Check if request exists
    $checkSql = "SELECT reference_no FROM requests WHERE request_id = $requestId";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        $request = $checkResult->fetch_assoc();
        
        // Get attachments to delete files
        $attachmentsSql = "SELECT file_path FROM attachments WHERE request_id = $requestId";
        $attachmentsResult = $conn->query($attachmentsSql);
        
        if ($attachmentsResult && $attachmentsResult->num_rows > 0) {
            while ($attachment = $attachmentsResult->fetch_assoc()) {
                // Delete file from server
                $filePath = '../uploads/' . $attachment['file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }
        
        // Disable foreign key checks temporarily to avoid constraint issues
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        // Delete related records first
        $conn->query("DELETE FROM attachments WHERE request_id = $requestId");
        $conn->query("DELETE FROM request_logs WHERE request_id = $requestId");
        $conn->query("DELETE FROM notifications WHERE request_id = $requestId");
        
        // Delete request
        $deleteSql = "DELETE FROM requests WHERE request_id = $requestId";
        $result = $conn->query($deleteSql);
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        closeDB($conn);
        
        if ($result) {
            // ตรวจสอบว่ามีการส่งมาจากหน้าเว็บและต้องการ redirect กลับ
            if (!empty($_SERVER['HTTP_REFERER']) && 
                (strpos($_SERVER['HTTP_REFERER'], 'admin') !== false)) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
            
            sendJsonResponse(
                apiResponse('success', 'Request deleted successfully', [
                    'request_id' => $requestId,
                    'reference_no' => $request['reference_no']
                ])
            );
        } else {
            sendJsonResponse(
                apiResponse('error', 'Database error: ' . $conn->error),
                500 // Internal Server Error
            );
        }
    } else {
        closeDB($conn);
        sendJsonResponse(
            apiResponse('error', 'Request not found', ['request_id' => $requestId]),
            404 // Not Found
        );
    }
}