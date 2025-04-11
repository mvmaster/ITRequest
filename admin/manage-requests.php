<?php
// Include necessary files
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../auth/session.php';
require_once '../includes/functions.php';

// Check if user is admin or IT staff
if (!isLoggedIn() || (!isAdmin() && !isITStaff())) {
    header("Location: ../auth/login.php");
    exit;
}

// Get filter and pagination parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$type = isset($_GET['type']) ? (int)$_GET['type'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Items per page
$offset = ($page - 1) * $limit;

// Connect to database
$conn = connectDB();

// Build filter conditions
$conditions = [];
$params = [];

if (!empty($status)) {
    $conditions[] = "r.status = ?";
    $params[] = $status;
}

if (!empty($priority)) {
    $conditions[] = "r.priority = ?";
    $params[] = $priority;
}

if (!empty($type)) {
    $conditions[] = "r.type_id = ?";
    $params[] = $type;
}

if (!empty($search)) {
    $conditions[] = "(r.reference_no LIKE ? OR r.subject LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM requests r 
               LEFT JOIN users u ON r.user_id = u.user_id 
               $whereClause";

$countStmt = $conn->prepare($countQuery);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get requests with pagination
$requestsQuery = "SELECT r.*, 
                 t.type_name,
                 u.first_name as requester_first_name, u.last_name as requester_last_name,
                 a.first_name as assigned_first_name, a.last_name as assigned_last_name
                 FROM requests r
                 LEFT JOIN users u ON r.user_id = u.user_id
                 LEFT JOIN users a ON r.assigned_to = a.user_id
                 LEFT JOIN request_types t ON r.type_id = t.type_id
                 $whereClause
                 ORDER BY 
                    CASE 
                        WHEN r.priority = 'urgent' THEN 1
                        WHEN r.priority = 'high' THEN 2
                        WHEN r.priority = 'medium' THEN 3
                        WHEN r.priority = 'low' THEN 4
                    END,
                    r.created_at DESC
                 LIMIT ?, ?";

$requestsStmt = $conn->prepare($requestsQuery);

if (!empty($params)) {
    $types = str_repeat('s', count($params)) . 'ii';
    $bindParams = array_merge($params, [$offset, $limit]);
    $requestsStmt->bind_param($types, ...$bindParams);
} else {
    $requestsStmt->bind_param('ii', $offset, $limit);
}

$requestsStmt->execute();
$requestsResult = $requestsStmt->get_result();
$requests = [];

if ($requestsResult->num_rows > 0) {
    while ($row = $requestsResult->fetch_assoc()) {
        $row['status_badge'] = getStatusBadge($row['status']);
        $row['priority_badge'] = getPriorityBadge($row['priority']);
        $row['created_at_formatted'] = formatDateThai($row['created_at']);
        $row['updated_at_formatted'] = formatDateThai($row['updated_at']);
        $row['requester_name'] = $row['requester_first_name'] . ' ' . $row['requester_last_name'];
        $row['assigned_to_name'] = $row['assigned_to'] ? 
                                  $row['assigned_first_name'] . ' ' . $row['assigned_last_name'] : '-';
                                   
        // Check for recent updates (within last 24 hours)
        $updateTime = strtotime($row['updated_at']);
        $currentTime = time();
        $row['is_recent'] = ($currentTime - $updateTime < 86400); // 24 hours in seconds
        
        $requests[] = $row;
    }
}

// Get request types for filter
$typesQuery = "SELECT * FROM request_types ORDER BY type_name";
$typesResult = $conn->query($typesQuery);
$requestTypes = [];

if ($typesResult->num_rows > 0) {
    while ($row = $typesResult->fetch_assoc()) {
        $requestTypes[] = $row;
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

?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="page-header">
                <i class="fas fa-tasks"></i> จัดการคำขอ
                <?php if (!empty($status)) : ?>
                    <span class="badge <?php echo 'bg-' . ($status == 'pending' ? 'warning' : ($status == 'in_progress' ? 'info' : ($status == 'completed' ? 'success' : ($status == 'rejected' ? 'danger' : 'secondary')))); ?>">
                        <?php echo getStatusThai($status); ?>
                    </span>
                <?php endif; ?>
            </h2>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> ตัวกรองและการค้นหา</h5>
        </div>
        <div class="card-body">
            <form action="manage-requests.php" method="get" class="row g-3">
                <!-- สถานะ -->
                <div class="col-md-2">
                    <label for="status" class="form-label">สถานะ</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>ปฏิเสธ</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>ปิดงาน</option>
                    </select>
                </div>
                
                <!-- ความสำคัญ -->
                <div class="col-md-2">
                    <label for="priority" class="form-label">ความสำคัญ</label>
                    <select name="priority" id="priority" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>ต่ำ</option>
                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>ปานกลาง</option>
                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>สูง</option>
                        <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>>เร่งด่วน</option>
                    </select>
                </div>
                
                <!-- ประเภท -->
                <div class="col-md-3">
                    <label for="type" class="form-label">ประเภท</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($requestTypes as $requestType) : ?>
                            <option value="<?php echo $requestType['type_id']; ?>" <?php echo $type == $requestType['type_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($requestType['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- ค้นหา -->
                <div class="col-md-3">
                    <label for="search" class="form-label">ค้นหา</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="หมายเลขอ้างอิง, หัวข้อ, ชื่อผู้แจ้ง" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                    <a href="manage-requests.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> รีเซ็ต
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Requests Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> รายการคำขอ 
                <span class="badge bg-primary"><?php echo $totalRecords; ?> รายการ</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (count($requests) > 0) : ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>หมายเลขอ้างอิง</th>
                                <th>หัวข้อ</th>
                                <th>ผู้แจ้ง</th>
                                <th>ประเภท</th>
                                <th>ความสำคัญ</th>
                                <th>วันที่สร้าง</th>
                                <th>อัพเดตล่าสุด</th>
                                <th>สถานะ</th>
                                <th>ผู้รับผิดชอบ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request) : ?>
                                <tr <?php echo $request['is_recent'] ? 'class="table-info"' : ''; ?>>
                                    <td><?php echo $request['reference_no']; ?></td>
                                    <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['type_name']); ?></td>
                                    <td><?php echo $request['priority_badge']; ?></td>
                                    <td><?php echo $request['created_at_formatted']; ?></td>
                                    <td>
                                        <?php echo $request['updated_at_formatted']; ?>
                                        <?php if ($request['is_recent']) : ?>
                                            <span class="badge bg-info">ใหม่</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $request['status_badge']; ?></td>
                                    <td><?php echo htmlspecialchars($request['assigned_to_name']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="request-details.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($request['status'] === 'pending') : ?>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#assignModal" 
                                                        data-request-id="<?php echo $request['request_id']; ?>"
                                                        data-reference-no="<?php echo $request['reference_no']; ?>"
                                                        data-subject="<?php echo htmlspecialchars($request['subject']); ?>">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($request['status'] !== 'completed' && $request['status'] !== 'closed' && $request['status'] !== 'rejected') : ?>
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal" 
                                                        data-request-id="<?php echo $request['request_id']; ?>"
                                                        data-reference-no="<?php echo $request['reference_no']; ?>"
                                                        data-subject="<?php echo htmlspecialchars($request['subject']); ?>"
                                                        data-current-status="<?php echo $request['status']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (isAdmin()) : ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal" 
                                                        data-request-id="<?php echo $request['request_id']; ?>"
                                                        data-reference-no="<?php echo $request['reference_no']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1) : ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>&type=<?php echo $type; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page-1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) : ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>&type=<?php echo $type; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>&type=<?php echo $type; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page+1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else : ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> ไม่พบข้อมูลคำขอตามเงื่อนไขที่ระบุ
                </div>
            <?php endif; ?>
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
            <form id="assignForm" action="<?php echo BASE_URL; ?>/api/requests.php?action=assign" method="post">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="assign_request_id">
                    
                    <div class="mb-3">
                        <label class="form-label">หมายเลขอ้างอิง:</label>
                        <div class="form-control-plaintext" id="assign_reference_no"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">หัวข้อ:</label>
                        <div class="form-control-plaintext" id="assign_subject"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">มอบหมายให้:</label>
                        <select name="assigned_to" id="assigned_to" class="form-select" required>
                            <option value="">-- เลือกผู้รับผิดชอบ --</option>
                            <?php foreach ($staffList as $staff) : ?>
                                <option value="<?php echo $staff['user_id']; ?>">
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
            <form id="statusForm" action="<?php echo BASE_URL; ?>/api/requests.php?action=status" method="post">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="status_request_id">
                    
                    <div class="mb-3">
                        <label class="form-label">หมายเลขอ้างอิง:</label>
                        <div class="form-control-plaintext" id="status_reference_no"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">หัวข้อ:</label>
                        <div class="form-control-plaintext" id="status_subject"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">สถานะใหม่:</label>
                        <select name="status" id="status_new" class="form-select" required>
                            <option value="">-- เลือกสถานะ --</option>
                            <option value="in_progress" data-current="pending,in_progress">กำลังดำเนินการ</option>
                            <option value="completed" data-current="in_progress,pending">เสร็จสิ้น</option>
                            <option value="rejected" data-current="pending,in_progress">ปฏิเสธ</option>
                            <option value="closed" data-current="completed,rejected">ปิดงาน</option>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">ยืนยันการลบคำขอ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการลบคำขอหมายเลข <strong id="delete_reference_no"></strong> ใช่หรือไม่?</p>
                <p class="text-danger">หมายเหตุ: การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <a href="#" id="delete_button" class="btn btn-danger">
                    <i class="fas fa-trash"></i> ลบคำขอ
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Assign Modal
        const assignModal = document.getElementById('assignModal');
        if (assignModal) {
            assignModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const requestId = button.getAttribute('data-request-id');
                const referenceNo = button.getAttribute('data-reference-no');
                const subject = button.getAttribute('data-subject');
                
                document.getElementById('assign_request_id').value = requestId;
                document.getElementById('assign_reference_no').textContent = referenceNo;
                document.getElementById('assign_subject').textContent = subject;
                document.getElementById('assigned_to').selectedIndex = 0;
                document.getElementById('assign_comment').value = '';
            });
        }
        
        // Status Modal
        const statusModal = document.getElementById('statusModal');
        if (statusModal) {
            statusModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const requestId = button.getAttribute('data-request-id');
                const referenceNo = button.getAttribute('data-reference-no');
                const subject = button.getAttribute('data-subject');
                const currentStatus = button.getAttribute('data-current-status');
                
                document.getElementById('status_request_id').value = requestId;
                document.getElementById('status_reference_no').textContent = referenceNo;
                document.getElementById('status_subject').textContent = subject;
                document.getElementById('status_comment').value = '';
                
                // Filter status options based on current status
                const statusSelect = document.getElementById('status_new');
                for (let i = 0; i < statusSelect.options.length; i++) {
                    const option = statusSelect.options[i];
                    if (option.value) {
                        const allowedCurrent = option.getAttribute('data-current').split(',');
                        if (allowedCurrent.includes(currentStatus)) {
                            option.disabled = false;
                        } else {
                            option.disabled = true;
                        }
                    }
                }
                statusSelect.selectedIndex = 0;
            });
        }
        
        // Delete Modal
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const requestId = button.getAttribute('data-request-id');
                const referenceNo = button.getAttribute('data-reference-no');
                
                document.getElementById('delete_reference_no').textContent = referenceNo;
                document.getElementById('delete_button').href = '<?php echo BASE_URL; ?>/api/requests.php?action=delete&id=' + requestId;
            });
        }
        
        // Form validation
        const assignForm = document.getElementById('assignForm');
        if (assignForm) {
            assignForm.addEventListener('submit', function(event) {
                if (!document.getElementById('assigned_to').value.trim() || !document.getElementById('assign_comment').value.trim()) {
                    event.preventDefault();
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
            });
        }
        
        const statusForm = document.getElementById('statusForm');
        if (statusForm) {
            statusForm.addEventListener('submit', function(event) {
                if (!document.getElementById('status_new').value.trim() || !document.getElementById('status_comment').value.trim()) {
                    event.preventDefault();
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
            });
        }
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามี Bootstrap 5 Modal หรือไม่
    if (typeof bootstrap !== 'undefined') {
        // ลงทะเบียน Modal ทั้งหมด
        const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const commentModal = document.getElementById('commentModal') ? 
            new bootstrap.Modal(document.getElementById('commentModal')) : null;
        
        // ลงทะเบียน Event Listeners สำหรับปุ่มที่เปิด Modal
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
            button.addEventListener('click', function() {
                const target = this.getAttribute('data-bs-target');
                
                // เตรียมข้อมูลสำหรับแต่ละ Modal
                const requestId = this.getAttribute('data-request-id');
                const referenceNo = this.getAttribute('data-reference-no');
                const subject = this.getAttribute('data-subject');
                const currentStatus = this.getAttribute('data-current-status');
                
                if (target === '#assignModal') {
                    document.getElementById('assign_request_id').value = requestId;
                    document.getElementById('assign_reference_no').textContent = referenceNo;
                    document.getElementById('assign_subject').textContent = subject;
                    document.getElementById('assigned_to').selectedIndex = 0;
                    document.getElementById('assign_comment').value = '';
                    assignModal.show();
                } 
                else if (target === '#statusModal') {
                    document.getElementById('status_request_id').value = requestId;
                    document.getElementById('status_reference_no').textContent = referenceNo;
                    document.getElementById('status_subject').textContent = subject;
                    document.getElementById('status_comment').value = '';
                    
                    // กรองตัวเลือกสถานะที่อนุญาตให้เปลี่ยน
                    if (currentStatus) {
                        const statusSelect = document.getElementById('status_new');
                        for (let i = 0; i < statusSelect.options.length; i++) {
                            const option = statusSelect.options[i];
                            if (option.value) {
                                const allowedCurrent = option.getAttribute('data-current').split(',');
                                option.disabled = !allowedCurrent.includes(currentStatus);
                            }
                        }
                        statusSelect.selectedIndex = 0;
                    }
                    statusModal.show();
                }
                else if (target === '#deleteModal') {
                    document.getElementById('delete_reference_no').textContent = referenceNo;
                    document.getElementById('delete_button').href = BASE_URL + '/api/requests.php?action=delete&id=' + requestId;
                    deleteModal.show();
                }
                else if (target === '#commentModal' && commentModal) {
                    commentModal.show();
                }
            });
        });
        
        // Form validation
        const forms = {
            'assignForm': {
                'fields': ['assigned_to', 'assign_comment'],
                'message': 'กรุณากรอกข้อมูลมอบหมายงานให้ครบถ้วน'
            },
            'statusForm': {
                'fields': ['status_new', 'status_comment'],
                'message': 'กรุณาเลือกสถานะและกรอกความคิดเห็นให้ครบถ้วน'
            },
            'commentForm': {
                'fields': ['comment'],
                'message': 'กรุณากรอกความคิดเห็น'
            }
        };
        
        for (const [formId, config] of Object.entries(forms)) {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(event) {
                    let isValid = true;
                    
                    for (const fieldId of config.fields) {
                        const field = document.getElementById(fieldId);
                        if (field && !field.value.trim()) {
                            isValid = false;
                            break;
                        }
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                        alert(config.message);
                    }
                });
            }
        }
    } else {
        console.error('Bootstrap JavaScript ไม่ได้ถูกโหลด ทำให้ Modal ไม่ทำงาน');
    }
});
</script>

<?php include '../includes/footer.php'; ?>