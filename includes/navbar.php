<?php
// ตรวจสอบว่า session เริ่มต้นแล้ว
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ฟังก์ชันตรวจสอบว่าหน้าปัจจุบันตรงกับ URL ที่ระบุหรือไม่
function isActive($path) {
    $current_page = $_SERVER['PHP_SELF'];
    return (strpos($current_page, $path) !== false);
}

// ตรวจสอบสถานะการเข้าสู่ระบบ
$is_logged_in = isset($_SESSION['user_id']) ? true : false;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$user_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';

// นับการแจ้งเตือนที่ยังไม่ได้อ่าน
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
<!-- เพิ่ม CSS inline เพื่อแก้ไขปัญหาเร่งด่วน -->
<style>
.navbar {
    display: flex !important;
}
.navbar-dark {
    background-color: #0d6efd !important;
}
.navbar-dark .navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
}
.navbar-dark .navbar-nav .nav-link:hover {
    color: #fff !important;
}
.navbar-dark .navbar-nav .nav-link.active {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.2) !important;
    font-weight: bold !important;
    border-radius: 0.25rem !important;
}
.dropdown-menu {
    background-color: #fff !important;
    color: #212529 !important;
}
.dropdown-item {
    color: #212529 !important;
}
.dropdown-item:hover {
    background-color: #f8f9fa !important;
}
.dropdown-item.active {
    background-color: #0d6efd !important;
    color: white !important;
}
</style>

<!-- Navbar สำหรับ Bootstrap 5 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-headset me-2"></i> ระบบจัดการ IT Request
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($is_logged_in) : ?>
                    <?php if ($user_role === 'user') : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('/user/index.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/index.php">
                                <i class="fas fa-home"></i> หน้าหลัก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('/user/create-request.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/create-request.php">
                                <i class="fas fa-plus-circle"></i> สร้าง IT Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('/user/track-request.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/track-request.php">
                                <i class="fas fa-search"></i> ตรวจสอบสถานะ
                            </a>
                        </li>
                    <?php elseif ($user_role === 'it_staff' || $user_role === 'admin') : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('/admin/index.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/admin/index.php">
                                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('/admin/manage-requests.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/admin/manage-requests.php">
                                <i class="fas fa-tasks"></i> จัดการคำขอ
                            </a>
                        </li>
                        <?php if ($user_role === 'admin') : ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('/admin/reports.php') ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/admin/reports.php">
                                    <i class="fas fa-chart-bar"></i> รายงาน
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('/index.php') && !isActive('/user/') && !isActive('/admin/') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/index.php">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <?php if ($is_logged_in) : ?>
                    <!-- Notifications dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0) : ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $notification_count; ?>
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                            <li><h6 class="dropdown-header">การแจ้งเตือน</h6></li>
                            
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
                                        
                                        echo '<li><a class="dropdown-item ' . ($isUnread ? 'fw-bold' : '') . '" href="' . BASE_URL . '/user/view-request.php?id=' . $notif['request_id'] . '">';
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<i class="fas fa-bell me-2 ' . ($isUnread ? 'text-primary' : 'text-muted') . '"></i>';
                                        echo '<div>';
                                        echo '<div>' . htmlspecialchars($notif['message']) . '</div>';
                                        echo '<small class="text-muted">' . $timeAgo . '</small>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</a></li>';
                                    }
                                } else {
                                    echo '<li><span class="dropdown-item-text text-center py-3">ไม่มีการแจ้งเตือน</span></li>';
                                }
                                
                                closeDB($conn);
                            }
                            ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-center" href="<?php echo BASE_URL; ?>/user/notifications.php">
                                    ดูการแจ้งเตือนทั้งหมด
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- User dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                 style="width: 32px; height: 32px; font-size: 14px;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($user_name); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <span class="dropdown-item-text text-muted">
                                    <small>เข้าสู่ระบบในฐานะ</small>
                                    <div class="fw-bold"><?php echo getRoleNameThai($user_role); ?></div>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item <?php echo isActive('/user/profile.php') ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/user/profile.php">
                                    <i class="fas fa-user-circle me-2"></i> โปรไฟล์
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo isActive('/user/change-password.php') ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/user/change-password.php">
                                    <i class="fas fa-key me-2"></i> เปลี่ยนรหัสผ่าน
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="btn btn-light text-primary ms-2" href="<?php echo BASE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php
// ฟังก์ชั่นคำนวณเวลาที่ผ่านไป เช่น "2 นาทีที่แล้ว"
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

// ฟังก์ชันแปลงค่าบทบาทเป็นภาษาไทย
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

<!-- เพิ่ม Script สำหรับแก้ไขปัญหา dropdown -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามี Bootstrap ใน global scope หรือไม่
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JS ไม่ถูกโหลด กำลังใช้วิธีแก้ไขแบบ manual');
        
        // แก้ไข dropdown แบบ manual
        document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const dropdownMenu = this.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    dropdownMenu.classList.toggle('show');
                }
            });
        });
        
        // ปิด dropdown เมื่อคลิกที่อื่น
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                if (!menu.parentElement.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        });
    } else {
        console.log('Bootstrap JS โหลดสมบูรณ์ กำลังเริ่มต้น dropdowns...');
        // เริ่มต้น dropdown ด้วย Bootstrap API
        document.querySelectorAll('.dropdown-toggle').forEach(function(dropdownToggle) {
            new bootstrap.Dropdown(dropdownToggle);
        });
    }
});
</script>