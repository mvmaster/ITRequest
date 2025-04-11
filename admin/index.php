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

// Calculate progress percentage for each staff
foreach ($staffList as &$staff) {
    $staff['progress_percent'] = $counts['total'] > 0 ? round(($staff['active_requests'] / $counts['total']) * 100) : 0;
}

closeDB($conn);

?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <!-- Welcome Section with Greeting and Summary -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-gradient-primary text-black shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1">
                                <i class="fas fa-tachometer-alt"></i> สวัสดี, <?php echo getCurrentUserName(); ?>
                            </h2>
                            <p class="mb-0">ยินดีต้อนรับสู่แดชบอร์ดผู้ดูแลระบบ IT Request</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <p class="mb-0"><i class="far fa-calendar-alt"></i> <?php echo formatDateThai(date('Y-m-d H:i:s')); ?></p>
                            <h5>ปีงบประมาณ <?php echo $currentYear + 543; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards Row - Responsive Layout with Improved Design -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card border-left-primary shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-3 text-center text-primary">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                        <div class="col-9">
                            <div class="text-xs text-uppercase font-weight-bold text-muted">คำขอทั้งหมด</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $counts['total']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card border-left-warning shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-3 text-center text-warning">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <div class="col-9">
                            <div class="text-xs text-uppercase font-weight-bold text-muted">รอดำเนินการ</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $counts['pending']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card border-left-info shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-3 text-center text-info">
                            <i class="fas fa-spinner fa-2x"></i>
                        </div>
                        <div class="col-9">
                            <div class="text-xs text-uppercase font-weight-bold text-muted">กำลังดำเนินการ</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $counts['in_progress']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card border-left-success shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-3 text-center text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <div class="col-9">
                            <div class="text-xs text-uppercase font-weight-bold text-muted">เสร็จสิ้น</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $counts['completed']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card border-left-danger shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-3 text-center text-danger">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                        <div class="col-9">
                            <div class="text-xs text-uppercase font-weight-bold text-muted">ปฏิเสธ</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $counts['rejected']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card border-left-secondary shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-3 text-center text-secondary">
                            <i class="fas fa-archive fa-2x"></i>
                        </div>
                        <div class="col-9">
                            <div class="text-xs text-uppercase font-weight-bold text-muted">ปิดงาน</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $counts['closed']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Main Content: Charts and Recent Requests -->
        <div class="col-lg-8">
            <!-- Monthly Statistics Chart - More Compact Design -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> สถิติคำขอรายเดือน ปี <?php echo $currentYear + 543; ?>
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="chartOptions" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="chartOptions">
                            <a class="dropdown-item" href="#" id="downloadChart">
                                <i class="fas fa-download"></i> ดาวน์โหลดกราฟ
                            </a>
                            <a class="dropdown-item" href="reports.php">
                                <i class="fas fa-chart-line"></i> ดูรายงานเพิ่มเติม
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:260px;">
                        <canvas id="requestsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Requests - Modern Table Design -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> คำขอล่าสุด
                    </h5>
                    <a href="manage-requests.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i> ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($recentRequests) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="pl-3">หมายเลขอ้างอิง</th>
                                        <th>หัวข้อ</th>
                                        <th>ผู้แจ้ง</th>
                                        <th>ประเภท</th>
                                        <th class="text-center">สถานะ</th>
                                        <th class="text-right pr-3">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequests as $request) : ?>
                                        <tr>
                                            <td class="pl-3">
                                                <span class="font-weight-bold"><?php echo $request['reference_no']; ?></span>
                                                <small class="d-block text-muted"><?php echo $request['created_at_formatted']; ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars(mb_strimwidth($request['subject'], 0, 30, "...")); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($request['type_name']); ?></span></td>
                                            <td class="text-center"><?php echo $request['status_badge']; ?></td>
                                            <td class="text-right pr-3">
                                                <a href="request-details.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle"></i> ไม่มีคำขอในระบบ
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar: IT Staff, Request Types, Quick Links -->
        <div class="col-lg-4">
            <!-- Request Types Distribution - More Visualized Design -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="fas fa-tags"></i> สถิติตามประเภทคำขอ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:220px;">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- IT Support Team - Card Based Design -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="fas fa-users"></i> ทีม IT Support
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($staffList as $staff) : ?>
                            <div class="col-xl-6 col-lg-12 col-md-6 mb-3">
                                <div class="card border-left-<?php echo $staff['user_role'] === 'admin' ? 'danger' : 'info'; ?> shadow-sm h-100">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-2">
                                                <div class="avatar bg-<?php echo $staff['user_role'] === 'admin' ? 'danger' : 'info'; ?> text-white rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                    <?php echo strtoupper(substr($staff['first_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></div>
                                                <div class="small text-muted">
                                                    <?php if ($staff['user_role'] === 'admin') : ?>
                                                        <span class="badge badge-danger">ผู้ดูแลระบบ</span>
                                                    <?php else : ?>
                                                        <span class="badge badge-info">เจ้าหน้าที่ IT</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small">งานที่รับผิดชอบ</span>
                                                <span class="small font-weight-bold"><?php echo $staff['active_requests']; ?> งาน</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-<?php echo $staff['user_role'] === 'admin' ? 'danger' : 'info'; ?>" role="progressbar" 
                                                    style="width: <?php echo min($staff['progress_percent'], 100); ?>%" 
                                                    aria-valuenow="<?php echo $staff['active_requests']; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links - Action-Based Card Design -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="fas fa-link"></i> ทางลัด
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <a href="manage-requests.php?status=pending" class="btn btn-warning btn-block d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-clock fa-2x mr-2"></i>
                                <div class="text-left">
                                    <small class="d-block">รอดำเนินการ</small>
                                    <span class="font-weight-bold"><?php echo $counts['pending']; ?> คำขอ</span>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 mb-2">
                            <a href="manage-requests.php?status=in_progress" class="btn btn-info btn-block d-flex align-items-center justify-content-center py-3">
                                <i class="fas fa-spinner fa-2x mr-2"></i>
                                <div class="text-left">
                                    <small class="d-block">กำลังดำเนินการ</small>
                                    <span class="font-weight-bold"><?php echo $counts['in_progress']; ?> คำขอ</span>
                                </div>
                            </a>
                        </div>
                        <?php if (isAdmin()) : ?>
                            <div class="col-6 mb-2">
                                <a href="reports.php" class="btn btn-success btn-block d-flex align-items-center justify-content-center py-3">
                                    <i class="fas fa-chart-line fa-2x mr-2"></i>
                                    <div class="text-left">
                                        <small class="d-block">รายงาน</small>
                                        <span class="font-weight-bold">ดูรายงานสรุป</span>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js - Latest version for better visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart Configuration with Better Colors and Responsive Design
        Chart.defaults.font.family = "'Sarabun', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.color = '#666';
        
        // Monthly Chart - More Modern Design
        const monthlyCtx = document.getElementById('requestsChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($chartData, 'month')); ?>,
                datasets: [
                    {
                        label: 'คำขอทั้งหมด',
                        data: <?php echo json_encode(array_column($chartData, 'total')); ?>,
                        backgroundColor: 'rgba(78, 115, 223, 0.6)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'เสร็จสิ้น',
                        data: <?php echo json_encode(array_column($chartData, 'completed')); ?>,
                        backgroundColor: 'rgba(28, 200, 138, 0.6)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        titleColor: '#fff',
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: { weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 10,
                        cornerRadius: 4
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        },
                        grid: {
                            borderDash: [2],
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
        
        // Type Chart - Modern Donut Design
        const typeData = <?php echo json_encode($typeStats); ?>;
        const typeNames = typeData.map(item => item.type_name);
        const typeCounts = typeData.map(item => item.count);
        
        // Generate a nice color palette
        const typeColors = [
            'rgba(78, 115, 223, 0.8)',
            'rgba(28, 200, 138, 0.8)',
            'rgba(246, 194, 62, 0.8)',
            'rgba(231, 74, 59, 0.8)',
            'rgba(54, 185, 204, 0.8)',
            'rgba(133, 135, 150, 0.8)'
        ];
        
        const typeBorderColors = typeColors.map(color => color.replace('0.8', '1'));
        
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: typeNames,
                datasets: [{
                    data: typeCounts,
                    backgroundColor: typeColors,
                    borderColor: typeBorderColors,
                    borderWidth: 1,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleColor: '#fff',
                        titleFont: { weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 10,
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                let value = context.raw;
                                let sum = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = Math.round((value / sum) * 100);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Download Chart Functionality
        document.getElementById('downloadChart').addEventListener('click', function(e) {
            e.preventDefault();
            
            const canvas = document.getElementById('requestsChart');
            const image = canvas.toDataURL('image/png', 1.0);
            
            const downloadLink = document.createElement('a');
            downloadLink.href = image;
            downloadLink.download = 'สถิติคำขอรายเดือน_<?php echo date('Y-m-d'); ?>.png';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        });
    });
</script>

<?php include '../includes/footer.php'; ?>