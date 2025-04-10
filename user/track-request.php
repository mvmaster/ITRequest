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

// Get user requests
$conn = connectDB();
$status = isset($_GET['status']) ? sanitizeInput($_GET['status'], $conn) : '';

// Build status condition
$statusCondition = "WHERE r.user_id = $userId";
if (!empty($status)) {
    $statusCondition .= " AND r.status = '$status'";
}

// Get requests
$requestsSql = "SELECT r.*, t.type_name,
                a.first_name as assigned_first_name, a.last_name as assigned_last_name
                FROM requests r
                LEFT JOIN request_types t ON r.type_id = t.type_id
                LEFT JOIN users a ON r.assigned_to = a.user_id
                $statusCondition
                ORDER BY r.created_at DESC";

$requestsResult = $conn->query($requestsSql);
$requests = [];

if ($requestsResult->num_rows > 0) {
    while ($row = $requestsResult->fetch_assoc()) {
        $row['status_badge'] = getStatusBadge($row['status']);
        $row['priority_badge'] = getPriorityBadge($row['priority']);
        $row['created_at_formatted'] = formatDateThai($row['created_at']);
        $row['updated_at_formatted'] = formatDateThai($row['updated_at']);
        $row['assigned_to_name'] = $row['assigned_to'] ? 
                                   $row['assigned_first_name'] . ' ' . $row['assigned_last_name'] : '-';
                                   
        // Check for recent updates (within last 24 hours)
        $updateTime = strtotime($row['updated_at']);
        $currentTime = time();
        $row['is_recent'] = ($currentTime - $updateTime < 86400); // 24 hours in seconds
        
        $requests[] = $row;
    }
}

// Get status counts
$countsSql = "SELECT status, COUNT(*) as count FROM requests WHERE user_id = $userId GROUP BY status";
$countsResult = $conn->query($countsSql);
$counts = [
    'all' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'rejected' => 0,
    'closed' => 0
];

if ($countsResult->num_rows > 0) {
    while ($row = $countsResult->fetch_assoc()) {
        $counts[$row['status']] = $row['count'];
        $counts['all'] += $row['count'];
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
                <i class="fas fa-search"></i> ตรวจสอบสถานะคำขอ
            </h2>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <!-- ค้นหาด้วยเลขอ้างอิง -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-search"></i> ค้นหาด้วยหมายเลขอ้างอิง</h5>
                </div>
                <div class="card-body">
                    <form id="track-form">
                        <div class="input-group mb-3">
                            <input type="text" id="reference_no" class="form-control" placeholder="กรอกหมายเลขอ้างอิง (เช่น REQ-20250409-001)" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> ค้นหา
                            </button>
                        </div>
                    </form>
                    <div id="tracking-result"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- ตัวกรองสถานะ -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> ตัวกรองสถานะ</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <a href="track-request.php" class="btn btn-outline-primary mb-2 <?php echo empty($status) ? 'active' : ''; ?>">
                                ทั้งหมด (<?php echo $counts['all']; ?>)
                            </a>
                            <a href="track-request.php?status=pending" class="btn btn-outline-warning mb-2 <?php echo $status === 'pending' ? 'active' : ''; ?>">
                                รอดำเนินการ (<?php echo $counts['pending']; ?>)
                            </a>
                            <a href="track-request.php?status=in_progress" class="btn btn-outline-info mb-2 <?php echo $status === 'in_progress' ? 'active' : ''; ?>">
                                กำลังดำเนินการ (<?php echo $counts['in_progress']; ?>)
                            </a>
                            <a href="track-request.php?status=completed" class="btn btn-outline-success mb-2 <?php echo $status === 'completed' ? 'active' : ''; ?>">
                                เสร็จสิ้น (<?php echo $counts['completed']; ?>)
                            </a>
                            <a href="track-request.php?status=rejected" class="btn btn-outline-danger mb-2 <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                                ปฏิเสธ (<?php echo $counts['rejected']; ?>)
                            </a>
                            <a href="track-request.php?status=closed" class="btn btn-outline-secondary mb-2 <?php echo $status === 'closed' ? 'active' : ''; ?>">
                                ปิดงาน (<?php echo $counts['closed']; ?>)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <!-- รายการคำขอ -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> รายการคำขอของคุณ 
                        <?php if (!empty($status)) : ?>
                            <span class="badge bg-primary"><?php echo getStatusThai($status); ?></span>
                        <?php endif; ?>
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
                                        <th>ประเภท</th>
                                        <th>ความสำคัญ</th>
                                        <th>วันที่สร้าง</th>
                                        <th>อัพเดตล่าสุด</th>
                                        <th>สถานะ</th>
                                        <th>ผู้รับผิดชอบ</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request) : ?>
                                        <tr <?php echo $request['is_recent'] ? 'class="table-info"' : ''; ?>>
                                            <td><?php echo $request['reference_no']; ?></td>
                                            <td><?php echo htmlspecialchars($request['subject']); ?></td>
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
                                                <a href="view-request.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> ไม่พบข้อมูลคำขอ
                            <?php if (!empty($status)) : ?>
                                ที่มีสถานะ "<?php echo getStatusThai($status); ?>"
                            <?php endif; ?>
                            
                            <a href="create-request.php" class="btn btn-sm btn-primary ms-3">
                                <i class="fas fa-plus-circle"></i> สร้างคำขอใหม่
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // AJAX สำหรับค้นหาด้วยหมายเลขอ้างอิง
    document.addEventListener('DOMContentLoaded', function() {
        const trackForm = document.getElementById('track-form');
        if (trackForm) {
            trackForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const refNo = document.getElementById('reference_no').value;
                const resultDiv = document.getElementById('tracking-result');
                
                if (!refNo) {
                    resultDiv.innerHTML = '<div class="alert alert-danger">กรุณากรอกหมายเลขอ้างอิง</div>';
                    return;
                }
                
                resultDiv.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                
                // AJAX request to track-api.php
                fetch('../api/tracking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'reference_no=' + encodeURIComponent(refNo)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Render request information
                        resultDiv.innerHTML = `
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">ข้อมูลคำขอ ${data.data.request.reference_no}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>หัวข้อ:</strong> ${data.data.request.subject}</p>
                                            <p><strong>ประเภท:</strong> ${data.data.request.type_name}</p>
                                            <p><strong>สถานะ:</strong> ${data.data.request.status_badge}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>วันที่สร้าง:</strong> ${data.data.request.created_at}</p>
                                            <p><strong>อัพเดตล่าสุด:</strong> ${data.data.request.updated_at}</p>
                                            <p><strong>ความสำคัญ:</strong> ${data.data.request.priority_badge}</p>
                                        </div>
                                    </div>
                                    
                                    <h6 class="border-bottom pb-2 mb-3">ประวัติการดำเนินการ</h6>
                                    <ul class="timeline">
                                        ${data.data.logs.map(log => `
                                            <li>
                                                <div class="timeline-badge ${log.status}">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                                <div class="timeline-panel">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1">${log.status_thai}</h6>
                                                        <small>${log.created_at}</small>
                                                    </div>
                                                    <p class="mb-0">${log.comment}</p>
                                                    <small class="text-muted">โดย: ${log.performed_by_name}</small>
                                                </div>
                                            </li>
                                        `).join('')}
                                    </ul>
                                    
                                    <div class="mt-3">
                                        <a href="view-request.php?id=${data.data.request.request_id}" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> ดูรายละเอียดเพิ่มเติม
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `<div class="alert alert-danger mt-3">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = '<div class="alert alert-danger mt-3">เกิดข้อผิดพลาดในการค้นหาข้อมูล กรุณาลองใหม่อีกครั้ง</div>';
                });
            });
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
