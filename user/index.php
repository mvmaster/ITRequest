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

// Get current user info
$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Redirect admin/IT staff to admin dashboard
if ($userRole === 'admin' || $userRole === 'it_staff') {
    header("Location: ../admin/index.php");
    exit;
}

// Get recent requests
$conn = connectDB();
$recentRequestsSql = "SELECT r.*, t.type_name
                      FROM requests r
                      LEFT JOIN request_types t ON r.type_id = t.type_id
                      WHERE r.user_id = $userId
                      ORDER BY r.created_at DESC
                      LIMIT 5";
$recentRequestsResult = $conn->query($recentRequestsSql);
$recentRequests = [];

if ($recentRequestsResult->num_rows > 0) {
    while ($row = $recentRequestsResult->fetch_assoc()) {
        $row['status_badge'] = getStatusBadge($row['status']);
        $row['created_at_formatted'] = formatDateThai($row['created_at']);
        $recentRequests[] = $row;
    }
}

// Get request counts by status
$countsSql = "SELECT status, COUNT(*) as count 
              FROM requests 
              WHERE user_id = $userId 
              GROUP BY status";
$countsResult = $conn->query($countsSql);
$counts = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'rejected' => 0,
    'closed' => 0
];

if ($countsResult->num_rows > 0) {
    while ($row = $countsResult->fetch_assoc()) {
        $counts[$row['status']] = $row['count'];
        $counts['total'] += $row['count'];
    }
}

// Get notifications
$notificationsSql = "SELECT n.*, r.reference_no, r.subject
                     FROM notifications n
                     LEFT JOIN requests r ON n.request_id = r.request_id
                     WHERE n.user_id = $userId AND n.is_read = 0
                     ORDER BY n.created_at DESC
                     LIMIT 5";
$notificationsResult = $conn->query($notificationsSql);
$notifications = [];

if ($notificationsResult->num_rows > 0) {
    while ($row = $notificationsResult->fetch_assoc()) {
        $row['created_at_formatted'] = formatDateThai($row['created_at']);
        $notifications[] = $row;
    }
}

closeDB($conn);

// Set base URL for includes
$base_url = "http://localhost/it-request-system"; // แก้ไขให้ตรงกับ URL ของเว็บไซต์คุณ
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="page-header">
                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                <small class="text-muted">ยินดีต้อนรับ, <?php echo getCurrentUserName(); ?></small>
            </h2>
        </div>
    </div>
    
    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['total']; ?></div>
                    <div class="stats-label">คำขอทั้งหมด</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['pending'] + $counts['in_progress']; ?></div>
                    <div class="stats-label">คำขอที่กำลังดำเนินการ</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['completed']; ?></div>
                    <div class="stats-label">คำขอที่เสร็จสิ้น</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Requests -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history"></i> คำขอล่าสุด</h5>
                    <a href="track-request.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentRequests) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>หมายเลขอ้างอิง</th>
                                        <th>หัวข้อ</th>
                                        <th>ประเภท</th>
                                        <th>วันที่สร้าง</th>
                                        <th>สถานะ</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequests as $request) : ?>
                                        <tr>
                                            <td><?php echo $request['reference_no']; ?></td>
                                            <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($request['type_name']); ?></td>
                                            <td><?php echo $request['created_at_formatted']; ?></td>
                                            <td><?php echo $request['status_badge']; ?></td>
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
                            <i class="fas fa-info-circle"></i> คุณยังไม่มีคำขอในระบบ 
                            <a href="create-request.php" class="alert-link">สร้างคำขอใหม่</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Notifications & Quick Actions -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> ดำเนินการด่วน</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="create-request.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> สร้าง IT Request ใหม่
                        </a>
                        <a href="track-request.php" class="btn btn-outline-secondary">
                            <i class="fas fa-search"></i> ตรวจสอบสถานะคำขอ
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Notifications -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> การแจ้งเตือน</h5>
                </div>
                <div class="card-body">
                    <?php if (count($notifications) > 0) : ?>
                        <ul class="list-group">
                            <?php foreach ($notifications as $notification) : ?>
                                <li class="list-group-item">
                                    <div>
                                        <strong>
                                            <a href="view-request.php?id=<?php echo $notification['request_id']; ?>">
                                                <?php echo $notification['reference_no']; ?>
                                            </a>
                                        </strong>
                                        <small class="text-muted float-end"><?php echo $notification['created_at_formatted']; ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> ไม่มีการแจ้งเตือนใหม่
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
