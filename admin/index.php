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

// Get statistics
$conn = connectDB();

// Get request counts by status
$countsSql = "SELECT status, COUNT(*) as count FROM requests GROUP BY status";
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

// Get request counts by type
$typesSql = "SELECT t.type_name, COUNT(r.request_id) as count 
             FROM request_types t
             LEFT JOIN requests r ON t.type_id = r.type_id
             GROUP BY t.type_id
             ORDER BY count DESC";
$typesResult = $conn->query($typesSql);
$typeStats = [];

if ($typesResult->num_rows > 0) {
    while ($row = $typesResult->fetch_assoc()) {
        $typeStats[] = $row;
    }
}

// Get recent requests
$recentSql = "SELECT r.*, 
              t.type_name,
              u.first_name as requester_first_name, u.last_name as requester_last_name,
              a.first_name as assigned_first_name, a.last_name as assigned_last_name
              FROM requests r
              LEFT JOIN request_types t ON r.type_id = t.type_id
              LEFT JOIN users u ON r.user_id = u.user_id
              LEFT JOIN users a ON r.assigned_to = a.user_id
              ORDER BY r.created_at DESC
              LIMIT 10";
$recentResult = $conn->query($recentSql);
$recentRequests = [];

if ($recentResult->num_rows > 0) {
    while ($row = $recentResult->fetch_assoc()) {
        $row['status_badge'] = getStatusBadge($row['status']);
        $row['priority_badge'] = getPriorityBadge($row['priority']);
        $row['created_at_formatted'] = formatDateThai($row['created_at']);
        $row['requester_name'] = $row['requester_first_name'] . ' ' . $row['requester_last_name'];
        $row['assigned_to_name'] = $row['assigned_to'] ? 
                                  $row['assigned_first_name'] . ' ' . $row['assigned_last_name'] : '-';
        $recentRequests[] = $row;
    }
}

// Get IT staff
$staffSql = "SELECT user_id, first_name, last_name, employee_id, user_role,
             (SELECT COUNT(*) FROM requests WHERE assigned_to = u.user_id AND status = 'in_progress') as active_requests
             FROM users u
             WHERE user_role IN ('admin', 'it_staff')
             ORDER BY user_role DESC, active_requests DESC";
$staffResult = $conn->query($staffSql);
$staffList = [];

if ($staffResult->num_rows > 0) {
    while ($row = $staffResult->fetch_assoc()) {
        $staffList[] = $row;
    }
}

// Get request statistics by month for current year
$currentYear = date('Y');
$monthlySql = "SELECT 
               MONTH(created_at) as month,
               COUNT(*) as total,
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
               FROM requests
               WHERE YEAR(created_at) = $currentYear
               GROUP BY MONTH(created_at)
               ORDER BY month";
$monthlyResult = $conn->query($monthlySql);
$monthlyStats = array_fill(1, 12, ['month' => 0, 'total' => 0, 'completed' => 0]);

if ($monthlyResult->num_rows > 0) {
    while ($row = $monthlyResult->fetch_assoc()) {
        $monthlyStats[$row['month']] = $row;
    }
}

// Format monthly stats for chart
$monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
$chartData = [];
foreach ($monthlyStats as $month => $data) {
    $chartData[] = [
        'month' => $monthNames[$month - 1],
        'total' => (int)$data['total'],
        'completed' => (int)$data['completed']
    ];
}

closeDB($conn);

?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="page-header">
                <i class="fas fa-tachometer-alt"></i> แดชบอร์ดผู้ดูแลระบบ
                <small class="text-muted">ยินดีต้อนรับ, <?php echo getCurrentUserName(); ?></small>
            </h2>
        </div>
    </div>
    
    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-2">
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
        <div class="col-md-2">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['pending']; ?></div>
                    <div class="stats-label">รอดำเนินการ</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-info">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['in_progress']; ?></div>
                    <div class="stats-label">กำลังดำเนินการ</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['completed']; ?></div>
                    <div class="stats-label">เสร็จสิ้น</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['rejected']; ?></div>
                    <div class="stats-label">ปฏิเสธ</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body stats-card">
                    <div class="stats-icon text-secondary">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div class="stats-number"><?php echo $counts['closed']; ?></div>
                    <div class="stats-label">ปิดงาน</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Chart -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> สถิติคำขอรายเดือน ปี <?php echo $currentYear + 543; ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="requestsChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Recent Requests -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history"></i> คำขอล่าสุด</h5>
                    <a href="manage-requests.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentRequests) > 0) : ?>
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
                                        <th>สถานะ</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequests as $request) : ?>
                                        <tr>
                                            <td><?php echo $request['reference_no']; ?></td>
                                            <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['type_name']); ?></td>
                                            <td><?php echo $request['priority_badge']; ?></td>
                                            <td><?php echo $request['created_at_formatted']; ?></td>
                                            <td><?php echo $request['status_badge']; ?></td>
                                            <td>
                                                <a href="request-details.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-info">
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
                            <i class="fas fa-info-circle"></i> ไม่มีคำขอในระบบ
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Request Types -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags"></i> สถิติตามประเภทคำขอ</h5>
                </div>
                <div class="card-body">
                    <canvas id="typeChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- IT Staff -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> ทีม IT Support</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($staffList as $staff) : ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                    <?php if ($staff['user_role'] === 'admin') : ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else : ?>
                                        <span class="badge bg-info">Staff</span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-primary rounded-pill">
                                    <i class="fas fa-tasks"></i> <?php echo $staff['active_requests']; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link"></i> ทางลัด</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="manage-requests.php?status=pending" class="btn btn-warning">
                            <i class="fas fa-clock"></i> จัดการคำขอที่รอดำเนินการ
                        </a>
                        <a href="manage-requests.php?status=in_progress" class="btn btn-info">
                            <i class="fas fa-spinner"></i> ติดตามคำขอที่กำลังดำเนินการ
                        </a>
                        <?php if (isAdmin()) : ?>
                            <a href="reports.php" class="btn btn-success">
                                <i class="fas fa-chart-line"></i> ดูรายงานสรุป
                            </a>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-user-cog"></i> จัดการผู้ใช้งาน
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart Data
        const monthlyData = <?php echo json_encode($chartData); ?>;
        const typeData = <?php echo json_encode($typeStats); ?>;
        
        // Monthly Chart
        const monthlyCtx = document.getElementById('requestsChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [
                    {
                        label: 'คำขอทั้งหมด',
                        data: monthlyData.map(item => item.total),
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'เสร็จสิ้น',
                        data: monthlyData.map(item => item.completed),
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'จำนวนคำขอรายเดือน'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Type Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: typeData.map(item => item.type_name),
                datasets: [{
                    data: typeData.map(item => item.count),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'จำนวนคำขอตามประเภท'
                    }
                }
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>