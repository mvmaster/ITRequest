<?php
// Include necessary files
require_once '../config/database.php';
require_once '../auth/session.php';
require_once '../includes/functions.php';

// Check if user is admin or IT staff
if (!isLoggedIn() || (!isAdmin() && !isITStaff())) {
    header("Location: ../auth/login.php");
    exit;
}

// Get request ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage-requests.php");
    exit;
}

$requestId = (int)$_GET['id'];

// Get request details
$conn = connectDB();

// Check if request exists
$checkSql = "SELECT request_id FROM requests WHERE request_id = $requestId";
$checkResult = $conn->query($checkSql);

if ($checkResult->num_rows === 0) {
    // Request not found
    closeDB($conn);
    header("Location: manage-requests.php");
    exit;
}

// Get full request details
$requestSql = "SELECT r.*, 
              t.type_name,
              u.first_name as requester_first_name, u.last_name as requester_last_name,
              u.employee_id, u.email, u.department, u.phone,
              a.first_name as assigned_first_name, a.last_name as assigned_last_name
              FROM requests r
              LEFT JOIN request_types t ON r.type_id = t.type_id
              LEFT JOIN users u ON r.user_id = u.user_id
              LEFT JOIN users a ON r.assigned_to = a.user_id
              WHERE r.request_id = $requestId";

$requestResult = $conn->query($requestSql);
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

// Get IT staff for assign dropdown
$staffQuery = "SELECT user_id, first_name, last_name FROM users 
              WHERE user_role IN ('admin', 'it_staff') 
              ORDER BY first_name";
$staffResult = $conn->query($staffQuery);
$staffList = [];

if ($staffResult->num_rows > 0) {
    while ($row = $staffResult->fetch_assoc()) {
        $staffList[] = $row;
    }
}

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
                    <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                    <li class="breadcrumb-item"><a href="manage-requests.php">จัดการคำขอ</a></li>
                    <li class="breadcrumb-item active">รายละเอียดคำขอ</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-9">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> รายละเอียดคำขอ #<?php echo $request['reference_no']; ?>
                    </h5>
                    <div>
                        <span><?php echo $request['status_badge']; ?></span>
                        <span class="ms-2"><?php echo $request['priority_badge']; ?></span>
                    </div>
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
                                    <td><strong>เบอร์โทรศัพท์:</strong></td>
                                    <td><?php echo htmlspecialchars($request['phone'] ?? '-'); ?></td>
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
            </div>
        </div>
        
        <div class="col-xl-3">
            <!-- Action Panel -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs"></i> การดำเนินการ</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($request['status'] === 'pending') : ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                                <i class="fas fa-user-check"></i> มอบหมายงาน
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($request['status'] !== 'completed' && $request['status'] !== 'closed' && $request['status'] !== 'rejected') : ?>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#statusModal">
                                <i class="fas fa-edit"></i> อัพเดตสถานะ
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#commentModal">
                            <i class="fas fa-comment"></i> เพิ่มความคิดเห็น
                        </button>
                        
                        <?php if (isAdmin()) : ?>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="fas fa-trash"></i> ลบคำขอ
                            </button>
                        <?php endif; ?>
                        
                        <a href="manage-requests.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> กลับไปยังรายการคำขอ
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- User Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-clock"></i> ข้อมูลกิจกรรม</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>เวลาที่ใช้ในการดำเนินการ</span>
                            <?php 
                                $createdTime = strtotime($request['created_at']);
                                $completedTime = !empty($request['completed_at']) ? strtotime($request['completed_at']) : time();
                                $timeDiff = $completedTime - $createdTime;
                                $days = floor($timeDiff / (60 * 60 * 24));
                                $hours = floor(($timeDiff % (60 * 60 * 24)) / (60 * 60));
                                $timeText = '';
                                
                                if ($days > 0) {
                                    $timeText .= $days . ' วัน ';
                                }
                                if ($hours > 0 || $days > 0) {
                                    $timeText .= $hours . ' ชั่วโมง';
                                } else {
                                    $timeText = 'น้อยกว่า 1 ชั่วโมง';
                                }
                            ?>
                            <span class="badge bg-primary rounded-pill"><?php echo $timeText; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>จำนวนการอัพเดต</span>
                            <span class="badge bg-primary rounded-pill"><?php echo count($logs); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>จำนวนไฟล์แนบ</span>
                            <span class="badge bg-primary rounded-pill"><?php echo count($attachments); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>อัพเดตล่าสุดโดย</span>
                            <span class="text-primary"><?php echo !empty($logs) ? htmlspecialchars($logs[0]['performed_by_name']) : '-'; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignModalLabel">มอบหมายงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignForm" action="../api/requests.php?action=assign" method="post">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">หมายเลขอ้างอิง:</label>
                        <div class="form-control-plaintext"><?php echo $request['reference_no']; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">มอบหมายให้:</label>
                        <select name="assigned_to" id="assigned_to" class="form-select" required>
                            <option value="">-- เลือกผู้รับผิดชอบ --</option>
                            <?php foreach ($staffList as $staff) : ?>
                                <option value="<?php echo $staff['user_id']; ?>" <?php echo $request['assigned_to'] == $staff['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assign_comment" class="form-label">ความคิดเห็น:</label>
                        <textarea name="comment" id="assign_comment" class="form-control" rows="3" required
                                  placeholder="กรอกรายละเอียดเพิ่มเติม"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-check"></i> มอบหมาย
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">อัพเดตสถานะคำขอ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm" action="../api/requests.php?action=status" method="post">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">หมายเลขอ้างอิง:</label>
                        <div class="form-control-plaintext"><?php echo $request['reference_no']; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">สถานะปัจจุบัน:</label>
                        <div class="form-control-plaintext"><?php echo $request['status_badge']; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">สถานะใหม่:</label>
                        <select name="status" id="status_new" class="form-select" required>
                            <option value="">-- เลือกสถานะ --</option>
                            <?php if ($request['status'] === 'pending' || $request['status'] === 'in_progress') : ?>
                                <option value="in_progress">กำลังดำเนินการ</option>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] === 'pending' || $request['status'] === 'in_progress') : ?>
                                <option value="completed">เสร็จสิ้น</option>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] === 'pending' || $request['status'] === 'in_progress') : ?>
                                <option value="rejected">ปฏิเสธ</option>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] === 'completed' || $request['status'] === 'rejected') : ?>
                                <option value="closed">ปิดงาน</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status_comment" class="form-label">ความคิดเห็น:</label>
                        <textarea name="comment" id="status_comment" class="form-control" rows="3" required
                                  placeholder="กรอกรายละเอียดการอัพเดตสถานะ"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentModalLabel">เพิ่มความคิดเห็น</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="commentForm" action="../api/requests.php?action=status" method="post">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                    <input type="hidden" name="status" value="<?php echo $request['status']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">หมายเลขอ้างอิง:</label>
                        <div class="form-control-plaintext"><?php echo $request['reference_no']; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">ความคิดเห็น:</label>
                        <textarea name="comment" id="comment" class="form-control" rows="4" required
                                  placeholder="กรอกความคิดเห็นหรือบันทึกการดำเนินการ"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-comment"></i> บันทึกความคิดเห็น
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">ยืนยันการลบคำขอ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการลบคำขอหมายเลข <strong><?php echo $request['reference_no']; ?></strong> ใช่หรือไม่?</p>
                <p class="text-danger">หมายเหตุ: การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <a href="../api/requests.php?action=delete&id=<?php echo $requestId; ?>" class="btn btn-danger">
                    <i class="fas fa-trash"></i> ลบคำขอ
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation for assign form
        const assignForm = document.getElementById('assignForm');
        if (assignForm) {
            assignForm.addEventListener('submit', function(event) {
                const assignedTo = document.getElementById('assigned_to').value;
                const comment = document.getElementById('assign_comment').value;
                
                if (!assignedTo.trim() || !comment.trim()) {
                    event.preventDefault();
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
            });
        }
        
        // Form validation for status form
        const statusForm = document.getElementById('statusForm');
        if (statusForm) {
            statusForm.addEventListener('submit', function(event) {
                const status = document.getElementById('status_new').value;
                const comment = document.getElementById('status_comment').value;
                
                if (!status.trim() || !comment.trim()) {
                    event.preventDefault();
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
            });
        }
        
        // Form validation for comment form
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', function(event) {
                const comment = document.getElementById('comment').value;
                
                if (!comment.trim()) {
                    event.preventDefault();
                    alert('กรุณากรอกความคิดเห็น');
                }
            });
        }
    });
</script>

<?php include '../includes/footer.php'; ?>