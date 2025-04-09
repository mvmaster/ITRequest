<?php
// Include necessary files
require_once 'config/database.php';
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

// Set base URL for includes
$base_url = "http://192.168.0.4/request"; // แก้ไขให้ตรงกับ URL ของเว็บไซต์คุณ
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ IT Request</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <style>
        /* Custom styles for landing page */
        .hero-section {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            padding: 100px 0;
            margin-bottom: 40px;
        }
        
        .feature-card {
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #007bff;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">คุณสมบัติ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">วิธีการใช้งาน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#faq">คำถามที่พบบ่อย</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-3 px-4" href="auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">ระบบจัดการ IT Request</h1>
            <p class="lead mb-5">
                แพลตฟอร์มสำหรับการจัดการคำขอเกี่ยวกับ IT ที่ช่วยให้คุณสามารถแจ้งปัญหา ติดตามสถานะ 
                และจัดการงานได้อย่างมีประสิทธิภาพ
            </p>
            <div class="d-flex justify-content-center">
                <a href="auth/login.php" class="btn btn-light btn-lg px-5 me-3">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </a>
                <a href="#how-it-works" class="btn btn-outline-light btn-lg px-5">
                    <i class="fas fa-info-circle"></i> เรียนรู้เพิ่มเติม
                </a>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5">คุณสมบัติหลัก</h2>
                <p class="lead text-muted">ระบบของเราออกแบบมาเพื่อตอบโจทย์ความต้องการของทั้งผู้ใช้งานและทีม IT</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h4>แจ้งปัญหาได้ง่าย</h4>
                            <p class="text-muted">
                                ผู้ใช้งานสามารถแจ้งปัญหาหรือคำขอได้อย่างง่ายดาย พร้อมแนบไฟล์ที่เกี่ยวข้องเพื่อให้ข้อมูลที่ครบถ้วน
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h4>ติดตามสถานะ</h4>
                            <p class="text-muted">
                                ติดตามสถานะของคำขอแบบเรียลไทม์ เห็นความคืบหน้าและการอัพเดตล่าสุดจากทีม IT ได้ตลอดเวลา
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h4>จัดการคำขอ</h4>
                            <p class="text-muted">
                                ทีม IT สามารถจัดการคำขอ มอบหมายงาน และอัพเดตสถานะได้อย่างมีประสิทธิภาพผ่านแดชบอร์ดที่ใช้งานง่าย
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h4>การแจ้งเตือน</h4>
                            <p class="text-muted">
                                รับการแจ้งเตือนเมื่อมีการอัพเดตสถานะคำขอหรือมีความคืบหน้าใหม่ เพื่อให้ไม่พลาดการติดตาม
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h4>รายงานและสถิติ</h4>
                            <p class="text-muted">
                                ดูรายงานและสถิติเพื่อวิเคราะห์ประสิทธิภาพการทำงาน ระยะเวลาในการแก้ไขปัญหา และแนวโน้มของปัญหาที่พบบ่อย
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4>รองรับทุกอุปกรณ์</h4>
                            <p class="text-muted">
                                ใช้งานได้ทั้งบนคอมพิวเตอร์ แท็บเล็ต และสมาร์ทโฟน ด้วยการออกแบบที่ตอบสนองทุกขนาดหน้าจอ
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5">วิธีการใช้งาน</h2>
                <p class="lead text-muted">ขั้นตอนการใช้งานระบบแจ้งปัญหาและติดตามสถานะคำขอ</p>
            </div>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="timeline">
                        <div class="row g-0">
                            <div class="col-md-6 offset-md-6">
                                <div class="timeline-panel">
                                    <div class="timeline-badge bg-primary">1</div>
                                    <div class="card">
                                        <div class="card-body">
                                            <h4>เข้าสู่ระบบ</h4>
                                            <p>เข้าสู่ระบบด้วยรหัสพนักงานและรหัสผ่านของคุณ</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-0">
                            <div class="col-md-6">
                                <div class="timeline-panel">
                                    <div class="timeline-badge bg-primary">2</div>
                                    <div class="card">
                                        <div class="card-body">
                                            <h4>สร้างคำขอใหม่</h4>
                                            <p>เลือกประเภทคำขอ กรอกรายละเอียด และแนบไฟล์ที่เกี่ยวข้อง (ถ้ามี)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-0">
                            <div class="col-md-6 offset-md-6">
                                <div class="timeline-panel">
                                    <div class="timeline-badge bg-primary">3</div>
                                    <div class="card">
                                        <div class="card-body">
                                            <h4>ทีม IT รับเรื่อง</h4>
                                            <p>ทีม IT จะตรวจสอบคำขอและมอบหมายให้ผู้รับผิดชอบดำเนินการต่อไป</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-0">
                            <div class="col-md-6">
                                <div class="timeline-panel">
                                    <div class="timeline-badge bg-primary">4</div>
                                    <div class="card">
                                        <div class="card-body">
                                            <h4>ติดตามสถานะ</h4>
                                            <p>ตรวจสอบความคืบหน้าของคำขอได้ตลอดเวลาผ่านระบบติดตามสถานะ</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-0">
                            <div class="col-md-6 offset-md-6">
                                <div class="timeline-panel">
                                    <div class="timeline-badge bg-success">5</div>
                                    <div class="card">
                                        <div class="card-body">
                                            <h4>ดำเนินการเสร็จสิ้น</h4>
                                            <p>เมื่อปัญหาได้รับการแก้ไขแล้ว ทีม IT จะอัพเดตสถานะเป็น "เสร็จสิ้น"</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section id="faq" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5">คำถามที่พบบ่อย</h2>
                <p class="lead text-muted">คำตอบสำหรับคำถามที่พบบ่อยเกี่ยวกับระบบของเรา</p>
            </div>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    ฉันจะเข้าสู่ระบบได้อย่างไร?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    คุณสามารถเข้าสู่ระบบได้ด้วยรหัสพนักงานและรหัสผ่านที่ได้รับจากฝ่ายบุคคลหรือฝ่าย IT ของบริษัท หากคุณยังไม่มีรหัสผ่านหรือลืมรหัสผ่าน โปรดติดต่อฝ่าย IT เพื่อขอรับรหัสผ่านใหม่
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    ฉันควรเลือกประเภทคำขอใด?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    เลือกประเภทคำขอที่ตรงกับลักษณะปัญหาของคุณมากที่สุด เช่น ปัญหาฮาร์ดแวร์ ปัญหาซอฟต์แวร์ ปัญหาเครือข่าย เป็นต้น หากไม่แน่ใจ คุณสามารถเลือก "บริการ IT อื่นๆ" และระบุรายละเอียดเพิ่มเติมในคำอธิบาย
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    ฉันจะติดตามสถานะคำขอของฉันได้อย่างไร?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    หลังจากเข้าสู่ระบบ คุณสามารถดูสถานะคำขอทั้งหมดของคุณได้ที่หน้า "ตรวจสอบสถานะ" นอกจากนี้ คุณยังสามารถใช้หมายเลขอ้างอิงที่ได้รับหลังจากสร้างคำขอเพื่อค้นหาสถานะของคำขอนั้นโดยเฉพาะได้
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    ฉันสามารถแก้ไขคำขอที่ส่งไปแล้วได้หรือไม่?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    คุณสามารถแก้ไขคำขอได้เฉพาะในกรณีที่คำขอนั้นยังอยู่ในสถานะ "รอดำเนินการ" เท่านั้น หากคำขอถูกมอบหมายหรืออยู่ระหว่างดำเนินการแล้ว คุณจะไม่สามารถแก้ไขได้ แต่สามารถเพิ่มความคิดเห็นหรือข้อมูลเพิ่มเติมผ่านการติดต่อกับทีม IT โดยตรง
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    ระบบรองรับไฟล์แนบประเภทใดบ้าง?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    ระบบรองรับการอัปโหลดไฟล์ประเภท JPG, JPEG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP และ RAR โดยขนาดไฟล์แต่ละไฟล์ต้องไม่เกิน 5MB และสามารถแนบได้สูงสุด 5 ไฟล์ต่อหนึ่งคำขอ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5><i class="fas fa-headset"></i> ระบบจัดการ IT Request</h5>
                    <p class="text-muted">
                        ระบบจัดการคำขอและแจ้งปัญหาด้าน IT ที่ช่วยให้การทำงานมีประสิทธิภาพมากยิ่งขึ้น
                    </p>
                </div>
                
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>ลิงก์ด่วน</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-muted">หน้าหลัก</a></li>
                        <li><a href="#features" class="text-decoration-none text-muted">คุณสมบัติ</a></li>
                        <li><a href="#how-it-works" class="text-decoration-none text-muted">วิธีการใช้งาน</a></li>
                        <li><a href="#faq" class="text-decoration-none text-muted">คำถามที่พบบ่อย</a></li>
                        <li><a href="auth/login.php" class="text-decoration-none text-muted">เข้าสู่ระบบ</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h5>ติดต่อเรา</h5>
                    <address class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> บริษัท ตัวอย่าง จำกัด<br>
                        123 ถนนสุขุมวิท แขวงคลองตัน<br>
                        เขตคลองเตย กรุงเทพฯ 10110<br>
                        <i class="fas fa-phone"></i> 02-123-4567<br>
                        <i class="fas fa-envelope"></i> support@example.com
                    </address>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> ระบบจัดการ IT Request. สงวนลิขสิทธิ์.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="#" class="text-muted">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-muted">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-muted">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-muted">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to top button -->
    <a href="#" class="btn btn-primary btn-lg back-to-top" role="button" style="position: fixed; bottom: 20px; right: 20px; display: none;">
        <i class="fas fa-arrow-up"></i>
    </a>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="<?php echo $base_url; ?>/assets/js/bootstrap.min.js"></script>
    
    <script>
        // Back to top button
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.back-to-top').fadeIn();
            } else {
                $('.back-to-top').fadeOut();
            }
        });
        
        $('.back-to-top').click(function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, 800);
            return false;
        });
        
        // Smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            
            var target = this.hash;
            var $target = $(target);
            
            $('html, body').animate({
                'scrollTop': $target.offset().top - 70
            }, 800, 'swing');
        });
    </script>
</body>
</html>