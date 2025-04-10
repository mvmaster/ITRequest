<?php
// Include necessary files
require_once 'config/database.php';
require_once 'config/app.php';
require_once 'auth/session.php';

// Check if user is logged in
$loggedIn = isLoggedIn();
$userRole = getCurrentUserRole();

// Redirect if already logged in
if ($loggedIn) {
    if ($userRole === 'admin' || $userRole === 'it_staff') {
        header("Location: admin/index.php");
        exit;
    } else {
        header("Location: user/index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ IT Request</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #4361ee;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #4361ee;
            background-color: rgba(67, 97, 238, 0.1);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_url; ?>">
                <i class="fas fa-headset"></i> ระบบจัดการ IT Request
            </a>
            <div class="d-flex">
                <a class="btn btn-light" href="auth/login.php">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container login-section py-5">
        <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-info-circle"></i> เกี่ยวกับระบบ</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">ระบบจัดการ IT Request สำหรับองค์กร</h5>
                        <p class="lead text-center mb-5">
                            ระบบแจ้งปัญหาและร้องขอบริการด้าน IT ที่ช่วยให้การทำงานระหว่างผู้ใช้งานและทีม IT มีประสิทธิภาพมากขึ้น
                        </p>
                        
                        <div class="row text-center">
                            <div class="col-md-4 mb-4">
                                <div class="feature-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h6>แจ้งปัญหา</h6>
                                <p class="small text-muted">แจ้งปัญหาการใช้งานคอมพิวเตอร์และระบบ IT</p>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="feature-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h6>ติดตามสถานะ</h6>
                                <p class="small text-muted">ติดตามความคืบหน้าของคำขอแบบเรียลไทม์</p>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h6>ติดต่อสื่อสาร</h6>
                                <p class="small text-muted">สื่อสารโดยตรงกับทีม IT ผ่านระบบ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</h4>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="mb-4">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="mb-4">กรุณาเข้าสู่ระบบเพื่อใช้งาน</h5>
                        <div class="d-grid gap-2 mb-3">
                            <a href="auth/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> ไปยังหน้าเข้าสู่ระบบ
                            </a>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">
                                หากไม่สามารถเข้าสู่ระบบได้ กรุณาติดต่อฝ่าย IT<br>
                                โทร: 1234 | อีเมล: it@example.com
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 text-primary me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">ยังไม่มีบัญชีผู้ใช้?</h6>
                                <p class="mb-0 small">สำหรับพนักงานใหม่หรือผู้ที่ยังไม่มีบัญชี กรุณาติดต่อฝ่าย IT เพื่อขอสิทธิ์การเข้าใช้งานระบบ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-light py-3 mt-auto">
        <div class="container">
            <div class="text-center">
                <p class="mb-0 text-muted">ระบบจัดการ IT Request &copy; <?php echo date('Y'); ?></p>
                <small class="text-muted">พัฒนาโดยฝ่ายเทคโนโลยีสารสนเทศ</small>
            </div>
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.min.js"></script>
</body>
</html>