<?php
/**
 * Simple Password Tool
 * 
 * เครื่องมืออย่างง่ายสำหรับการจัดการรหัสผ่าน
 * เหมาะสำหรับใช้ในกรณีที่มีปัญหาเกี่ยวกับ
 * การเข้ารหัสรหัสผ่านระหว่าง PHP เวอร์ชันต่างๆ
 * 
 * หมายเหตุ: ควรลบไฟล์นี้หลังใช้งานเสร็จเพื่อความปลอดภัย
 */

// Include database config
require_once 'config/database.php';

// Initialize variables
$message = '';
$encodedPassword = '';
$verifyResult = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hash password
    if (isset($_POST['hash'])) {
        $password = trim($_POST['password']);
        if (!empty($password)) {
            $encodedPassword = password_hash($password, PASSWORD_DEFAULT);
            $message = "รหัสผ่านที่เข้ารหัสแล้ว: " . $encodedPassword;
        }
    }
    
    // Update user password directly
    if (isset($_POST['update'])) {
        $employeeId = trim($_POST['employee_id']);
        $password = trim($_POST['new_password']);
        
        if (empty($employeeId) || empty($password)) {
            $message = "กรุณากรอกรหัสพนักงานและรหัสผ่านใหม่";
        } else {
            $conn = connectDB();
            
            // Check if user exists
            $checkSql = "SELECT user_id, first_name, last_name FROM users WHERE employee_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $employeeId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                $message = "ไม่พบผู้ใช้ที่มีรหัสพนักงาน: " . $employeeId;
            } else {
                $user = $checkResult->fetch_assoc();
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Update password
                $updateSql = "UPDATE users SET password = ? WHERE employee_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ss", $hashedPassword, $employeeId);
                
                if ($updateStmt->execute()) {
                    $message = "อัพเดตรหัสผ่านสำหรับ " . $user['first_name'] . " " . $user['last_name'] . " เรียบร้อยแล้ว";
                    $message .= "<br>รหัสผ่านใหม่: " . $password;
                    $message .= "<br>รหัสผ่านที่เข้ารหัสแล้ว: " . $hashedPassword;
                } else {
                    $message = "เกิดข้อผิดพลาดในการอัพเดตรหัสผ่าน: " . $conn->error;
                }
            }
            
            closeDB($conn);
        }
    }
    
    // Verify password
    if (isset($_POST['verify'])) {
        $hashedPassword = trim($_POST['hashed_password']);
        $plainPassword = trim($_POST['plain_password']);
        
        if (empty($hashedPassword) || empty($plainPassword)) {
            $verifyResult = "กรุณากรอกรหัสผ่านที่เข้ารหัสแล้วและรหัสผ่านธรรมดา";
        } else {
            if (password_verify($plainPassword, $hashedPassword)) {
                $verifyResult = "<div class='alert alert-success'>รหัสผ่านถูกต้อง! สามารถเข้าสู่ระบบได้</div>";
            } else {
                $verifyResult = "<div class='alert alert-danger'>รหัสผ่านไม่ถูกต้อง!</div>";
            }
        }
    }
}

// Get MYSQL version
$mysqlVersion = '';
try {
    $conn = connectDB();
    $result = $conn->query("SELECT VERSION() as version");
    if ($result && $row = $result->fetch_assoc()) {
        $mysqlVersion = $row['version'];
    }
    closeDB($conn);
} catch (Exception $e) {
    $mysqlVersion = 'ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เครื่องมือจัดการรหัสผ่านอย่างง่าย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        .result-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="mb-5">
            <div class="row">
                <div class="col-md-12">
                    <h1>เครื่องมือจัดการรหัสผ่านอย่างง่าย</h1>
                    <div class="alert alert-warning">
                        <strong>คำเตือน:</strong> ไฟล์นี้มีไว้สำหรับการแก้ไขปัญหารหัสผ่านเท่านั้น ควรลบทันทีหลังใช้งานเสร็จ
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">ข้อมูลเซิร์ฟเวอร์</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            <p><strong>MySQL Version:</strong> <?php echo $mysqlVersion; ?></p>
                            <p><strong>Password Hashing Algorithm:</strong> <?php echo PASSWORD_DEFAULT === PASSWORD_BCRYPT ? 'BCRYPT' : PASSWORD_DEFAULT; ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-info">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">1. เข้ารหัสรหัสผ่าน</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="password" class="form-label">รหัสผ่านที่ต้องการเข้ารหัส</label>
                                <input type="text" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" name="hash" class="btn btn-primary">เข้ารหัสรหัสผ่าน</button>
                        </form>
                        
                        <?php if (!empty($encodedPassword)): ?>
                            <div class="result-box mt-3">
                                <p class="mb-0"><strong>รหัสผ่านที่เข้ารหัสแล้ว:</strong></p>
                                <code><?php echo $encodedPassword; ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">2. อัพเดตรหัสผ่านของผู้ใช้โดยตรง</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">รหัสพนักงาน</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                                <input type="text" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <button type="submit" name="update" class="btn btn-success">อัพเดตรหัสผ่าน</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">3. ตรวจสอบรหัสผ่าน</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="hashed_password" class="form-label">รหัสผ่านที่เข้ารหัสแล้ว</label>
                                <input type="text" class="form-control" id="hashed_password" name="hashed_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="plain_password" class="form-label">รหัสผ่านธรรมดา</label>
                                <input type="text" class="form-control" id="plain_password" name="plain_password" required>
                            </div>
                            
                            <button type="submit" name="verify" class="btn btn-warning">ตรวจสอบรหัสผ่าน</button>
                        </form>
                        
                        <?php if (!empty($verifyResult)): ?>
                            <div class="mt-3">
                                <?php echo $verifyResult; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="mt-5 text-center text-muted">
            <p>เครื่องมือจัดการรหัสผ่านอย่างง่าย | <strong>คำเตือน:</strong> ลบไฟล์นี้หลังใช้งานเสร็จเพื่อความปลอดภัย</p>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>