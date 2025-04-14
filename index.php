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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS - Animate On Scroll Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .hero-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, #3a0ca3 0%, #4361ee 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQ0MCIgaGVpZ2h0PSI1MjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMzg2LjU0NmM0OS4yMiAzMy41MSAxMzQgNjIuNDU1IDI1MC45NTYgNjIuNDU1IDI0MC4wNzMgMCAzNTkuMzEzLTEzOCAxNTkuMTEyLTMyMi4zNDZDMTk1LjA1NC0xMDEuNzg3IDMyMi45OTcgNTY5LjcxOCA1MjcgMjY5LjExMiA3MzEuMDAzLTMxLjQ5NSA4MTIgMjU5LjczMyAxMTM5IDMyMC44MzdjOTUgMTcuNzIxIDE5NS43NSAxOC4wMyAzMDEgMHYxOTkuMTYzSDBWMzg2LjU0NnoiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4xKSIgZmlsbC1ydWxlPSJldmVub2RkIi8+PC9zdmc+');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }
        
        .feature-card {
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }
        
        .icon-primary {
            background: rgba(67, 97, 238, 0.1);
            color: #4361ee;
        }
        
        .icon-success {
            background: rgba(72, 187, 120, 0.1);
            color: #48bb78;
        }
        
        .icon-warning {
            background: rgba(237, 137, 54, 0.1);
            color: #ed8936;
        }
        
        .icon-info {
            background: rgba(56, 178, 172, 0.1);
            color: #38b2ac;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            margin-top: 2rem;
        }
        
        .testimonial-card::before {
            content: """;
            position: absolute;
            top: -15px;
            left: 20px;
            font-size: 4rem;
            color: #4361ee;
            font-family: serif;
            line-height: 1;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #4361ee;
        }
        
        .btn-primary {
            background: #4361ee;
            border-color: #4361ee;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #3a0ca3;
            border-color: #3a0ca3;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline-primary {
            border-color: #4361ee;
            color: #4361ee;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: #4361ee;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
        }
        
        .icon-block {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #4361ee;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #3a0ca3 0%, #4361ee 100%);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQ0MCIgaGVpZ2h0PSIzMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMjA1YzU4IDI1IDE0OC4zIDY1IDI1NSA3NXMyMjUtMjkgMzY1LTEwNCAyODMgMS4zMzMgNDMwIDBjOTgtLjg4OSAxOTMuMzMzLTkuMzMzIDI5MC0yNS4zMzNWMzAwSDBWMjA1eiIgZmlsbD0icmdiYSgyNTUsIDI1NSwgMjU1LCAwLjEpIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }
        
        .section-padding {
            padding: 5rem 0;
        }
        
        .section-title {
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100px;
            height: 4px;
            background: #4361ee;
            border-radius: 2px;
        }
        
        footer {
            margin-top: auto;
            padding: 2rem 0;
            background-color: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 4rem 0;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #3a0ca3;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-headset me-2"></i>
                <span>ระบบจัดการ IT Request</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </li>
                </ul>
                <div>
                    <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-light px-4">
                        <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="display-4 fw-bold mb-4">ระบบจัดการ IT Request แบบครบวงจร</h1>
                    <p class="lead mb-4">แจ้งปัญหา ติดตามสถานะ และสื่อสารกับทีม IT ได้อย่างมีประสิทธิภาพ ด้วยระบบที่ใช้งานง่าย รวดเร็ว และตอบโจทย์ทุกความต้องการ</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-info-circle me-2"></i> เรียนรู้เพิ่มเติม
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left">
                    <img src="https://cdn.pixabay.com/photo/2017/07/31/11/44/laptop-2557576_960_720.jpg" alt="IT Support" class="img-fluid rounded-3 shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-padding bg-light" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold" data-aos="fade-up">ฟีเจอร์หลัก</h2>
                <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">ระบบของเราออกแบบมาเพื่อให้การติดต่อกับฝ่าย IT เป็นเรื่องง่าย</p>
            </div>
            
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="150">
                    <div class="feature-card">
                        <div class="feature-icon icon-primary mx-auto">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h4 class="mb-3 text-center">แจ้งปัญหา</h4>
                        <p class="text-muted mb-0">แจ้งปัญหาหรือความต้องการได้อย่างรวดเร็ว พร้อมแนบไฟล์ได้สูงสุด 5 ไฟล์</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon icon-success mx-auto">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="mb-3 text-center">ติดตามสถานะ</h4>
                        <p class="text-muted mb-0">ติดตามสถานะคำขอแบบเรียลไทม์ ทราบความคืบหน้าและเวลาดำเนินการ</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="450">
                    <div class="feature-card">
                        <div class="feature-icon icon-warning mx-auto">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4 class="mb-3 text-center">สื่อสาร</h4>
                        <p class="text-muted mb-0">สื่อสารกับทีม IT ได้โดยตรงผ่านระบบ ไม่ต้องใช้ช่องทางอื่นให้สับสน</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon icon-info mx-auto">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="mb-3 text-center">รายงาน</h4>
                        <p class="text-muted mb-0">ดูรายงานสรุปและสถิติการแจ้งปัญหา เพื่อวางแผนการพัฒนาระบบ IT</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Working Process Section -->
    <section class="section-padding">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-6 offset-lg-3 text-center">
                    <h2 class="fw-bold" data-aos="fade-up">ขั้นตอนการใช้งาน</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">ง่ายเพียง 4 ขั้นตอน</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 col-lg-3 text-center mb-4" data-aos="fade-up" data-aos-delay="150">
                    <div class="icon-block">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4>1. เข้าสู่ระบบ</h4>
                    <p class="text-muted">ใช้รหัสพนักงานและรหัสผ่านเพื่อเข้าสู่ระบบ</p>
                </div>
                
                <div class="col-md-6 col-lg-3 text-center mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="icon-block">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h4>2. สร้างคำขอ</h4>
                    <p class="text-muted">กรอกรายละเอียดปัญหาหรือความต้องการให้ชัดเจน</p>
                </div>
                
                <div class="col-md-6 col-lg-3 text-center mb-4" data-aos="fade-up" data-aos-delay="450">
                    <div class="icon-block">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>3. รอรับเรื่อง</h4>
                    <p class="text-muted">ทีม IT จะรับเรื่องและประสานงานกับคุณ</p>
                </div>
                
                <div class="col-md-6 col-lg-3 text-center mb-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="icon-block">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>4. ปัญหาได้รับการแก้ไข</h4>
                    <p class="text-muted">การดำเนินการจะถูกบันทึกและแจ้งให้คุณทราบ</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2 class="fw-bold" data-aos="fade-right">พร้อมใช้งานแล้ววันนี้</h2>
                    <p class="lead mb-0" data-aos="fade-right" data-aos-delay="100">เริ่มต้นใช้งานระบบ IT Request เพื่อประสบการณ์การแจ้งปัญหาที่ดีขึ้น</p>
                </div>
                <div class="col-lg-4 text-lg-end" data-aos="fade-left">
                    <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-light btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-md-0">ระบบจัดการ IT Request &copy; <?php echo date('Y'); ?> พัฒนาโดยฝ่ายเทคโนโลยีสารสนเทศ</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">เวอร์ชัน <?php echo defined('APP_VERSION') ? APP_VERSION : '1.0.0'; ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- AOS - Animate On Scroll Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS animation
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 70,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>