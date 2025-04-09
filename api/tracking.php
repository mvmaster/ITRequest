<?php
/**
 * Tracking API
 * 
 * API สำหรับติดตามสถานะคำขอ IT Request
 */

// Include API configuration
require_once 'config.php';

// Validate request method
validateApiRequest('POST');

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// If data is not in JSON format, try to get from POST
if (!$data) {
    $data = $_POST;
}

// Validate required fields
if (empty($data['reference_no'])) {
    sendJsonResponse(
        apiResponse('error', 'หมายเลขอ้างอิงไม่ถูกต้อง'),
        400 // Bad Request
    );
    exit;
}

$conn = connectDB();
$referenceNo = sanitizeInput($data['reference_no'], $conn);

// Get request details
$sql = "SELECT r.*, 
        t.type_name,
        u.first_name as requester_first_name, u.last_name as requester_last_name,
        a.first_name as assigned_first_name, a.last_name as assigned_last_name
        FROM requests r
        LEFT JOIN request_types t ON r.type_id = t.type_id
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN users a ON r.assigned_to = a.user_id
        WHERE r.reference_no = '$referenceNo'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $request = $result->fetch_assoc();
    
    // Format data for response
    $request['status_badge'] = getStatusBadge($request['status']);
    $request['priority_badge'] = getPriorityBadge($request['priority']);
    $request['created_at'] = formatDateThai($request['created_at']);
    $request['updated_at'] = formatDateThai($request['updated_at']);
    $request['completed_at'] = formatDateThai($request['completed_at']);
    $request['requester_name'] = $request['requester_first_name'] . ' ' . $request['requester_last_name'];
    $request['assigned_to_name'] = $request['assigned_to'] ? 
                                  $request['assigned_first_name'] . ' ' . $request['assigned_last_name'] : 'ยังไม่ได้รับมอบหมาย';
    
    // Get request logs/history
    $logsSql = "SELECT l.*, u.first_name, u.last_name 
                FROM request_logs l
                LEFT JOIN users u ON l.performed_by = u.user_id
                WHERE l.request_id = {$request['request_id']}
                ORDER BY l.created_at DESC";
    $logsResult = $conn->query($logsSql);
    $logs = [];
    
    if ($logsResult->num_rows > 0) {
        while ($log = $logsResult->fetch_assoc()) {
            $log['status_badge'] = getStatusBadge($log['status']);
            $log['status_thai'] = getStatusThai($log['status']);
            $log['created_at'] = formatDateThai($log['created_at']);
            $log['performed_by_name'] = $log['first_name'] . ' ' . $log['last_name'];
            $logs[] = $log;
        }
    }
    
    // Get attachments
    $attachmentsSql = "SELECT * FROM attachments WHERE request_id = {$request['request_id']}";
    $attachmentsResult = $conn->query($attachmentsSql);
    $attachments = [];
    
    if ($attachmentsResult->num_rows > 0) {
        while ($attachment = $attachmentsResult->fetch_assoc()) {
            $attachments[] = $attachment;
        }
    }
    
    closeDB($conn);
    
    sendJsonResponse(
        apiResponse('success', 'พบข้อมูลคำขอเรียบร้อย', [
            'request' => $request,
            'logs' => $logs,
            'attachments' => $attachments
        ])
    );
} else {
    closeDB($conn);
    sendJsonResponse(
        apiResponse('error', 'ไม่พบข้อมูลคำขอตามหมายเลขอ้างอิงที่ระบุ'),
        404 // Not Found
    );
}
