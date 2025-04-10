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

<!-- เพิ่ม CSS สำหรับ Navbar ใหม่ -->
<style>
    .modern-navbar {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 10px 0;
    }
    
    .modern-navbar .navbar-brand {
        font-weight: 700;
        font-size: 1.4rem;
    }
    
    .modern-navbar .nav-item {
        margin: 0 5px;
    }
    
    .modern-navbar .nav-link {
        position: relative;
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .modern-navbar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }
    
    .modern-navbar .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
        font-weight: 600;
    }
    
    .modern-navbar .nav-link.active:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 30px;
        height: 3px;
        background-color: white;
        border-radius: 3px;
    }
    
    .modern-navbar .dropdown-menu {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }
    
    .modern-navbar .dropdown-item {
        padding: 10px 20px;
        transition: all 0.2s ease;
    }
    
    .modern-navbar .dropdown-item:hover {
        background-color: #f8f9fa;
        padding-left: 23px;
    }
    
    .modern-navbar .dropdown-item i {
        width: 20px;
        text-align: center;
        margin-right: 8px;
    }
    
    .notification-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        min-width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: #ff4757;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2px;
    }
    
    /* Custom user avatar */
    .user-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background-color: #fff;
        color: #4361ee;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 8px;
        font-size: 1rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .user-dropdown:hover .user-avatar {
        transform: scale(1.1);
    }
    
    /* Notification styling */
    .notifications-dropdown {
        width: 350px;
        border-radius: 10px;
        padding: 0;
        margin-top: 10px;
    }
    
    .notifications-header {
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
    }
    
    .notification-item {
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
    }
    
    .notification-item.bg-light {
        border-left-color: #4361ee;
    }
    
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    
    .empty-notifications {
        padding: 30px;
        text-align: center;
        color: #6c757d;
    }
    
    .empty-notifications i {
        font-size: 3rem;
        color: #e9ecef;
        margin-bottom: 15px;
    }
    
    .notifications-footer {
        padding: 10px;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
    }
    
    /* Manually show dropdown menus */
    .dropdown-menu.show {
        display: block;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark modern-navbar" style="background: linear-gradient(135deg, #4361ee, #3a0ca3);">
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
            <?php if ($is_logged_in) : ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($user_role === 'user') : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/user/index.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/index.php">
                                <i class="fas fa-home me-1"></i> หน้าหลัก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/user/create-request.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/create-request.php">
                                <i class="fas fa-plus-circle me-1"></i> สร้าง IT Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/user/track-request.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/user/track-request.php">
                                <i class="fas fa-search me-1"></i> ตรวจสอบสถานะ
                            </a>
                        </li>
                    <?php elseif ($user_role === 'it_staff' || $user_role === 'admin') : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/index.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/admin/index.php">
                                <i class="fas fa-tachometer-alt me-1"></i> แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/manage-requests.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/admin/manage-requests.php">
                                <i class="fas fa-tasks me-1"></i> จัดการคำขอ
                            </a>
                        </li>
                        <?php if ($user_role === 'admin') : ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/users.php') !== false ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/admin/users.php">
                                    <i class="fas fa-users me-1"></i> จัดการผู้ใช้
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/reports.php') !== false ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>/admin/reports.php">
                                    <i class="fas fa-chart-bar me-1"></i> รายงาน
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Notifications dropdown -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative p-2 js-dropdown-toggle" href="#" id="notificationsDropdown"
                           onclick="return false;">
                            <div class="bg-white bg-opacity-10 rounded-circle p-2">
                                <i class="fas fa-bell"></i>
                                <?php if ($notification_count > 0) : ?>
                                    <span class="notification-badge"><?php echo $notification_count; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notifications-dropdown" aria-labelledby="notificationsDropdown">
                            <div class="notifications-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-bell me-2"></i> การแจ้งเตือน</span>
                                <?php if ($notification_count > 0) : ?>
                                    <span class="badge bg-light text-primary rounded-pill"><?php echo $notification_count; ?> ใหม่</span>
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
                                        if ($isUnread) {
                                            echo '<i class="fas fa-bell text-primary"></i>';
                                        } else {
                                            echo '<i class="far fa-bell text-muted"></i>';
                                        }
                                        echo '</div>';
                                        echo '<div class="ms-3 flex-grow-1">';
                                        echo '<p class="notification-content mb-0 ' . ($isUnread ? 'fw-semibold' : '') . '">' . htmlspecialchars($notif['message']) . '</p>';
                                        echo '<small class="notification-time text-muted">' . $timeAgo . '</small>';
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
                                <a href="<?php echo BASE_URL; ?>/user/notifications.php" class="text-decoration-none">
                                    <i class="fas fa-eye me-1"></i> ดูการแจ้งเตือนทั้งหมด
                                </a>
                            </div>
                        </div>
                    </li>
                    
                    <!-- User dropdown -->
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link d-flex align-items-center js-dropdown-toggle" href="#" id="userDropdown"
                           onclick="return false;">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <div class="d-none d-md-block">
                                <span class="fw-semibold"><?php echo htmlspecialchars($user_name); ?></span>
                                <small class="d-block text-white-50"><?php echo getRoleNameThai($user_role); ?></small>
                            </div>
                            <i class="fas fa-chevron-down ms-1 d-none d-md-inline-block"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div class="dropdown-item text-center py-3">
                                    <div class="user-avatar mx-auto mb-2" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                    </div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user_name); ?></h6>
                                    <small class="text-muted"><?php echo getRoleNameThai($user_role); ?></small>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile.php">
                                    <i class="fas fa-user-circle"></i> โปรไฟล์
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/change-password.php">
                                    <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else : ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/index.php') !== false && strpos($_SERVER['PHP_SELF'], '/user/index.php') === false && strpos($_SERVER['PHP_SELF'], '/admin/index.php') === false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/index.php">
                            <i class="fas fa-home me-1"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-2 px-3" href="<?php echo BASE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> เข้าสู่ระบบ
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- เพิ่ม JavaScript เพื่อแก้ปัญหา Dropdown ไม่ทำงาน -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม event listeners สำหรับปุ่ม dropdown ทั้งหมด
    document.querySelectorAll('.js-dropdown-toggle').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // หา dropdown-menu ที่เกี่ยวข้อง
            const dropdown = this.nextElementSibling;
            
            // สลับการแสดง/ซ่อน dropdown
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                this.setAttribute('aria-expanded', 'false');
            } else {
                // ซ่อน dropdown อื่นๆ ทั้งหมดก่อน
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    menu.classList.remove('show');
                    if (menu.previousElementSibling) {
                        menu.previousElementSibling.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // แสดง dropdown ที่เลือก
                dropdown.classList.add('show');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });
    
    // ปิด dropdown เมื่อคลิกที่ส่วนอื่นของหน้าเว็บ
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                menu.classList.remove('show');
                if (menu.previousElementSibling) {
                    menu.previousElementSibling.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
});
</script>

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