<?php
// Make sure session has been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) ? true : false;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$user_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';

// Count unread notifications
$notification_count = 0;
if ($is_logged_in) {
    $conn = connectDB();
    $userId = $_SESSION['user_id'];
    $notifCountSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $userId AND is_read = 0";
    $notifResult = $conn->query($notifCountSql);
    if ($notifResult && $notifResult->num_rows > 0) {
        $notification_count = $notifResult->fetch_assoc()['count'];
    }
    closeDB($conn);
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-gradient" style="background: linear-gradient(135deg, #4361ee, #3a0ca3);">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-headset me-2"></i>
            <span>ระบบจัดการ IT Request</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($is_logged_in) : ?>
                    <?php if ($user_role === 'user') : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/user/index.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/index.php">
                                <i class="fas fa-home"></i> หน้าหลัก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/user/create-request.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/create-request.php">
                                <i class="fas fa-plus-circle"></i> สร้าง IT Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/user/track-request.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/track-request.php">
                                <i class="fas fa-search"></i> ตรวจสอบสถานะ
                            </a>
                        </li>
                    <?php elseif ($user_role === 'it_staff' || $user_role === 'admin') : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/index.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/admin/index.php">
                                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/manage-requests.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/admin/manage-requests.php">
                                <i class="fas fa-tasks"></i> จัดการคำขอ
                            </a>
                        </li>
                        <?php if ($user_role === 'admin') : ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/reports.php') !== false ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/admin/reports.php">
                                    <i class="fas fa-chart-bar"></i> รายงาน
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/index.php') !== false && strpos($_SERVER['PHP_SELF'], '/user/index.php') === false && strpos($_SERVER['PHP_SELF'], '/admin/index.php') === false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/index.php">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($is_logged_in) : ?>
                    <!-- Notifications dropdown -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0) : ?>
                                <span class="notification-badge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notifications-dropdown" aria-labelledby="notificationsDropdown">
                            <div class="notifications-header d-flex justify-content-between align-items-center">
                                <span>การแจ้งเตือน</span>
                                <?php if ($notification_count > 0) : ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $notification_count; ?> ใหม่</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php
                            if ($is_logged_in) {
                                $conn = connectDB();
                                $userId = $_SESSION['user_id'];
                                
                                $notifSql = "SELECT n.*, r.reference_no, r.subject 
                                             FROM notifications n 
                                             LEFT JOIN requests r ON n.request_id = r.request_id 
                                             WHERE n.user_id = $userId 
                                             ORDER BY n.created_at DESC LIMIT 5";
                                             
                                $notifResult = $conn->query($notifSql);
                                
                                if ($notifResult && $notifResult->num_rows > 0) {
                                    while ($notif = $notifResult->fetch_assoc()) {
                                        $isUnread = $notif['is_read'] == 0;
                                        $timeAgo = time_elapsed_string($notif['created_at']);
                                        
                                        echo '<a href="' . BASE_URL . '/user/view-request.php?id=' . $notif['request_id'] . '" class="dropdown-item notification-item ' . ($isUnread ? 'bg-light' : '') . '">';
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<div class="flex-shrink-0">';
                                        echo '<i class="fas fa-bell ' . ($isUnread ? 'text-primary' : 'text-muted') . '"></i>';
                                        echo '</div>';
                                        echo '<div class="ms-3 flex-grow-1">';
                                        echo '<p class="notification-content mb-0">' . htmlspecialchars($notif['message']) . '</p>';
                                        echo '<small class="notification-time">' . $timeAgo . '</small>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</a>';
                                    }
                                } else {
                                    echo '<div class="empty-notifications">';
                                    echo '<i class="fas fa-bell-slash"></i>';
                                    echo '<p>ไม่มีการแจ้งเตือน</p>';
                                    echo '</div>';
                                }
                                
                                closeDB($conn);
                            }
                            ?>
                            
                            <div class="notifications-footer">
                                <a href="<?php echo BASE_URL; ?>/user/notifications.php" class="text-decoration-none">ดูการแจ้งเตือนทั้งหมด</a>
                            </div>
                        </div>
                    </li>
                    
                    <!-- User dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-primary text-white rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($user_name); ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div class="dropdown-item text-muted">
                                    <small>เข้าสู่ระบบในฐานะ</small>
                                    <div class="fw-bold"><?php echo getRoleNameThai($user_role); ?></div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile.php">
                                    <i class="fas fa-user-circle me-2"></i> โปรไฟล์
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/change-password.php">
                                    <i class="fas fa-key me-2"></i> เปลี่ยนรหัสผ่าน
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item logout-btn" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-2 px-3" href="<?php echo BASE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php
// Helper function to convert timestamp to "time ago" format
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'ปี',
        'm' => 'เดือน',
        'w' => 'สัปดาห์',
        'd' => 'วัน',
        'h' => 'ชั่วโมง',
        'i' => 'นาที',
        's' => 'วินาที',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . 'ที่แล้ว' : 'เมื่อสักครู่';
}

// Helper function to get Thai role name
function getRoleNameThai($role) {
    switch ($role) {
        case 'admin':
            return 'ผู้ดูแลระบบ';
        case 'it_staff':
            return 'เจ้าหน้าที่ IT';
        case 'user':
            return 'ผู้ใช้งานทั่วไป';
        default:
            return $role;
    }
}
?>