<?php
// Include necessary files
require_once '../config/database.php';
require_once '../auth/session.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit;
}

// Get request ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: track-request.php");
    exit;
}

$requestId = (int)$_GET['id'];
$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Get request details
$conn = connectDB();

// Check if request exists and user has permission to view
$permissionSql = "SELECT user_id FROM requests WHERE request_id = $requestId";
$permissionResult = $conn->query($permissionSql);

if ($permissionResult->num_rows === 0) {
    // Request not found
    closeDB($conn);
    header("Location: track-request.php");
    exit;
}

$requestOwner = $permissionResult->fetch_assoc();

// Check if user has permission (owner or admin/IT staff)
if ($requestOwner['user_id'] != $userId && $userRole !== 'admin' && $userRole !== 'it_staff') {
    // User does not have permission
    closeDB($conn);
    header("Location: unauthorized.php");
    exit;
}

// Get full request details
$requestSql = "SELECT r.*, 
               t.type_name, t.type_id,
               u.first_name as requester_first_name, u.last_name as requester_last_name,
               u.employee_id, u.email, u.department,
               a.first_name as assigned_first_name, a.last_name as assigned_last_name
               FROM requests r
               LEFT JOIN request_types t ON r.type_id = t.type_id
               LEFT JOIN users u ON r.user_id = u.user_id
               LEFT JOIN users a ON r.assigned_to = a.user_id
               WHERE r.request_id = $requestId";

$requestResult = $conn->query($requestSql);

if ($requestResult->num_rows === 0) {
    // Request not found (additional check)
    closeDB($conn);
    header("Location: track-request.php");
    exit;
}

$request = $requestResult->fetch_assoc();

// Format data for display
$request['status_badge'] = getStatusBadge($request['status']);
$request['priority_badge'] = getPriorityBadge($request['priority']);
$request['created_at_formatted'] = formatDateThai($request['created_at']);
$request['updated_at_formatted'] = formatDateThai($request['updated_at']);
$request['completed_at_formatted'] = formatDateThai($request['completed_at']);
$request['requester_name'] = $request['requester_first_name'] . ' ' . $request['requester_last_name'];
$request['assigned_to_name'] = $request['assigned_to'] ? 
                              $request['assigned_first_name'] . ' ' . $request['assigned_last_name'] : '-';

// Get attachments
$attachmentsSql = "SELECT * FROM attachments WHERE request_id = $requestId";
$attachmentsResult = $conn->query($attachmentsSql);
$attachments = [];

if ($attachmentsResult->num_rows > 0) {
    while ($attachment = $attachmentsResult->fetch_assoc()) {
        $attachment['file_size_formatted'] = number_format($attachment['file_size'] / 1024, 2) . ' KB';
        $attachment['uploaded_at_formatted'] = formatDateThai($attachment['uploaded_at']);
        
        // Get file extension
        $fileExt = pathinfo($attachment['file_name'], PATHINFO_EXTENSION);
        $attachment['file_icon'] = getFileIcon($fileExt);
        
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

// Mark notifications as read
$markReadSql = "UPDATE notifications SET is_read = 1 
               WHERE user_id = $userId AND request_id = $requestId AND is_read = 0";
$conn->query($markReadSql);

closeDB($conn);

// Helper function to get file icon based on extension
function getFileIcon($extension) {
    $extension = strtolower($extension);
    
    $icons = [
        'pdf' => 'fas fa-file-pdf text-danger',
        'doc' => 'fas fa-file-word text-primary',
        'docx' => 'fas fa-file-word text-primary',
        'xls' => 'fas fa-file-excel text-success',
        'xlsx' => 'fas fa-file-excel text-success',
        'jpg' => 'fas fa-file-image text-info',
        'jpeg' => 'fas fa-file-image text-info',
        'png' => 'fas fa-file-image text-info',
        'gif' => 'fas fa-file-image text-info',
        'zip' => 'fas fa-file-archive text-warning',
        'rar' => 'fas fa-file-archive text-warning',
        'txt' => 'fas fa-file-alt text-secondary',
    ];
    
    return isset($icons[$extension]) ? $icons[$extension] : 'fas fa-file text-secondary';
}

// Set base URL for includes
$base_url = "http://localhost/it-request-system"; // แก้ไขให้ตรงกับ URL ของเว็บไซต์คุณ
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">หน้าหลัก</a></li>
                    <li class="breadcrumb-item"><a href="track-request.php">ตรวจสอบสถานะคำขอ</a></li>
                    <li class="breadcrumb-item active">รายละเอียดคำขอ</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> รายละเอียดคำขอ #<?php echo $request['reference_no']; ?>
                    </h5>
                    <span><?php echo $request['status_badge']; ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">ข้อมูลคำขอ</h6>
                            <table class="table table-striped">
                                <tr>
                                    <td><strong>หมายเลขอ้างอิง:</strong></td>
                                    <td><?php echo $request['reference_no']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>หัวข้อ:</strong></td>
                                    <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>ประเภท:</strong></td>
                                    <td><?php echo htmlspecialchars($request['type_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>ความสำคัญ:</strong></td>
                                    <td><?php echo $request['priority_badge']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>วันที่สร้าง:</strong></td>
                                    <td><?php echo $request['created_at_formatted']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>อัพเดตล่าสุด:</strong></td>
                                    <td><?php echo $request['updated_at_formatted']; ?></td>
                                </tr>
                                <?php if ($request['status'] === 'completed' && !empty($request['completed_at'])) : ?>
                                <tr>
                                    <td><strong>วันที่เสร็จสิ้น:</strong></td>
                                    <td><?php echo $request['completed_at_formatted']; ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">ข้อมูลผู้แจ้ง</h6>
                            <table class="table table-striped">
                                <tr>
                                    <td><strong>ชื่อผู้แจ้ง:</strong></td>
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>รหัสพนักงาน:</strong></td>
                                    <td><?php echo htmlspecialchars($request['employee_id']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>แผนก:</strong></td>
                                    <td><?php echo htmlspecialchars($request['department']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>อีเมล:</strong></td>
                                    <td><?php echo htmlspecialchars($request['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>ช่องทางติดต่อ:</strong></td>
                                    <td><?php echo !empty($request['contact_info']) ? htmlspecialchars($request['contact_info']) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>ผู้รับผิดชอบ:</strong></td>
                                    <td><?php echo htmlspecialchars($request['assigned_to_name']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">รายละเอียด</h6>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($attachments) > 0) : ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">ไฟล์แนบ</h6>
                            <ul class="attachment-list">
                                <?php foreach ($attachments as $attachment) : ?>
                                <li class="attachment-item">
                                    <div>
                                        <i class="<?php echo $attachment['file_icon']; ?> attachment-icon"></i>
                                        <?php echo htmlspecialchars($attachment['file_name']); ?>
                                        <span class="text-muted ms-2">(<?php echo $attachment['file_size_formatted']; ?>)</span>
                                    </div>
                                    <a href="../uploads/<?php echo $attachment['file_path']; ?>" class="btn btn-sm btn-outline-primary" download="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                        <i class="fas fa-download"></i> ดาวน์โหลด
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">ประวัติการดำเนินการ</h6>
                            <ul class="timeline">
                                <?php foreach ($logs as $log) : ?>
                                <li>
                                    <div class="timeline-badge <?php echo $log['status']; ?>">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="timeline-panel">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?php echo $log['status_thai']; ?></h6>
                                            <small><?php echo $log['created_at_formatted']; ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($log['comment'])); ?></p>
                                        <small class="text-muted">โดย: <?php echo htmlspecialchars($log['performed_by_name']); ?></small>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="track-request.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> กลับไปยังรายการคำขอ
                            </a>
                        </div>
                        
                        <?php if ($request['status'] === 'pending' && $request['user_id'] == $userId) : ?>
                        <div>
                            <a href="edit-request.php?id=<?php echo $requestId; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> แก้ไขคำขอ
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>