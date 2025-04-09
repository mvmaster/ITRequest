<?php
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

// Handle different request methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequests($action);
        break;
    case 'POST':
        handlePostRequests($action);
        break;
    case 'PUT':
        handlePutRequests($action);
        break;
    case 'DELETE':
        handleDeleteRequests($action);
        break;
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
            getRequestTypes();
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
 * Handle DELETE requests
 */
function handleDeleteRequests($action) {
    // Check authentication
    apiCheckAdminAuth(); // Only admin/IT staff can delete requests
    
    // Handle different DELETE actions
    switch ($action) {
        case 'delete':
            // Delete request
            if (isset($_GET['id'])) {
                deleteRequest($_GET['id']);
            } else {
                sendJsonResponse(
                    apiResponse('error', 'Request ID is required'),
                    400 // Bad Request
                );
            }
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
function getRequestTypes() {
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
                
                $uploadResult = uploadFile($fileUpload, '../uploads/');
                
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
            // If multiple files
            else {
                $fileCount = count($files['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] === 0) {
                        $fileUpload = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        $uploadResult = uploadFile($fileUpload, '../uploads/');
                        
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
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If data is not in JSON format, try to get from POST
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['request_id']) || empty($data['status']) || empty($data['comment'])) {
        sendJsonResponse(
            apiResponse('error', 'Missing required fields'),
            400 // Bad Request
        );
        return;
    }
    
    $conn = connectDB();
    
    // Sanitize input
    $requestId = (int)$data['request_id'];
    $status = sanitizeInput($data['status'], $conn);
    $comment = sanitizeInput($data['comment'], $conn);
    $userId = getCurrentUserId();
    
    // Check if request exists
    $checkSql = "SELECT r.*, u.user_id as requester_id FROM requests r 
                 LEFT JOIN users u ON r.user_id = u.user_id
                 WHERE r.request_id = $requestId";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        $request = $checkResult->fetch_assoc();
        
        // Update request status
        $completedAt = ($status === 'completed') ? "completed_at = CURRENT_TIMESTAMP," : "";
        $sql = "UPDATE requests SET status = '$status', $completedAt updated_at = CURRENT_TIMESTAMP WHERE request_id = $requestId";
        
        if ($conn->query($sql)) {
            // Add log entry
            addRequestLog($requestId, $status, $comment, $userId);
            
            // Create notification for requester
            $statusThai = getStatusThai($status);
            $notificationMsg = "คำขอ #{$request['reference_no']} ได้เปลี่ยนสถานะเป็น \"{$statusThai}\"";
            createNotification($request['requester_id'], $requestId, $notificationMsg);
            
            closeDB($conn);
            
            sendJsonResponse(
                apiResponse('success', 'Request status updated successfully', [
                    'request_id' => $requestId,
                    'status' => $status,
                    'status_thai' => $statusThai
                ])
            );
        } else {
            closeDB($conn);
            sendJsonResponse(
                apiResponse('error', 'Failed to update request status: ' . $conn->error),
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
 * Assign request to IT staff
 */
function assignRequest() {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If data is not in JSON format, try to get from POST
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['request_id']) || empty($data['assigned_to'])) {
        sendJsonResponse(
            apiResponse('error', 'Missing required fields'),
            400 // Bad Request
        );
        return;
    }
    
    $conn = connectDB();
    
    // Sanitize input
    $requestId = (int)$data['request_id'];
    $assignedTo = (int)$data['assigned_to'];
    $comment = isset($data['comment']) ? sanitizeInput($data['comment'], $conn) : 'มอบหมายงานให้เจ้าหน้าที่ IT';
    $userId = getCurrentUserId();
    
    // Check if request exists
    $checkSql = "SELECT r.*, u.user_id as requester_id FROM requests r 
                 LEFT JOIN users u ON r.user_id = u.user_id
                 WHERE r.request_id = $requestId";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        $request = $checkResult->fetch_assoc();
        
        // Get assignee name
        $assigneeSql = "SELECT first_name, last_name FROM users WHERE user_id = $assignedTo";
        $assigneeResult = $conn->query($assigneeSql);
        $assigneeName = '';
        
        if ($assigneeResult->num_rows > 0) {
            $assignee = $assigneeResult->fetch_assoc();
            $assigneeName = $assignee['first_name'] . ' ' . $assignee['last_name'];
        }
        
        // Update request assigned_to
        $sql = "UPDATE requests SET assigned_to = $assignedTo, status = 'in_progress', updated_at = CURRENT_TIMESTAMP WHERE request_id = $requestId";
        
        if ($conn->query($sql)) {
            // Add log entry
            $logComment = $comment . " ($assigneeName)";
            addRequestLog($requestId, 'in_progress', $logComment, $userId);
            
            // Create notification for requester
            $notificationMsg = "คำขอ #{$request['reference_no']} ได้รับมอบหมายให้ $assigneeName ดำเนินการ";
            createNotification($request['requester_id'], $requestId, $notificationMsg);
            
            closeDB($conn);
            
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
                apiResponse('error', 'Failed to assign request: ' . $conn->error),
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
 * Delete request
 */
function deleteRequest($requestId) {
    // Only admin can delete requests
    if (!isAdmin()) {
        sendJsonResponse(
            apiResponse('error', 'You do not have permission to delete requests'),
            403 // Forbidden
        );
        return;
    }
    
    $conn = connectDB();
    $requestId = (int)$requestId;
    
    // Check if request exists
    $checkSql = "SELECT reference_no FROM requests WHERE request_id = $requestId";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        $request = $checkResult->fetch_assoc();
        
        // Get attachments
        $attachmentsSql = "SELECT file_path FROM attachments WHERE request_id = $requestId";
        $attachmentsResult = $conn->query($attachmentsSql);
        
        if ($attachmentsResult->num_rows > 0) {
            while ($attachment = $attachmentsResult->fetch_assoc()) {
                // Delete file from server
                $filePath = '../uploads/' . $attachment['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
        
        // Delete request and related data (cascading delete for attachments and logs)
        $sql = "DELETE FROM requests WHERE request_id = $requestId";
        
        if ($conn->query($sql)) {
            closeDB($conn);
            
            sendJsonResponse(
                apiResponse('success', 'Request deleted successfully', [
                    'request_id' => $requestId,
                    'reference_no' => $request['reference_no']
                ])
            );
        } else {
            closeDB($conn);
            sendJsonResponse(
                apiResponse('error', 'Failed to delete request: ' . $conn->error),
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