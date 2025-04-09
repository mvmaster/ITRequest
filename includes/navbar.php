<?php
// Make sure session has been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) ? true : false;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$user_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $base_url; ?>">
            <i class="fas fa-headset"></i> ระบบจัดการ IT Request
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
                            <a class="nav-link" href="<?php echo $base_url; ?>/user/index.php">
                                <i class="fas fa-home"></i> หน้าหลัก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>/user/create-request.php">
                                <i class="fas fa-plus-circle"></i> สร้าง IT Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>/user/track-request.php">
                                <i class="fas fa-search"></i> ตรวจสอบสถานะ
                            </a>
                        </li>
                    <?php elseif ($user_role === 'it_staff' || $user_role === 'admin') : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>/admin/index.php">
                                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>/admin/manage-requests.php">
                                <i class="fas fa-tasks"></i> จัดการคำขอ
                            </a>
                        </li>
                        <?php if ($user_role === 'admin') : ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $base_url; ?>/admin/users.php">
                                    <i class="fas fa-users"></i> จัดการผู้ใช้
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $base_url; ?>/admin/reports.php">
                                    <i class="fas fa-chart-bar"></i> รายงาน
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>/index.php">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($is_logged_in) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>/user/profile.php">
                                    <i class="fas fa-id-card"></i> โปรไฟล์
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $base_url; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
