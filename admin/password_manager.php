<?php
/**
 * Password Manager Tool
 * 
 * ไฟล์นี้ใช้สำหรับจัดการรหัสผ่านในระบบ
 * - สร้างผู้ใช้ใหม่
 * - รีเซ็ตรหัสผ่านผู้ใช้
 * - ทดสอบรหัสผ่าน
 * 
 * หมายเหตุ: นี่เป็นเครื่องมือสำหรับผู้ดูแลระบบเท่านั้น
 * ควรลบหรือเปลี่ยนชื่อไฟล์นี้หลังจากใช้งานเสร็จเพื่อความปลอดภัย
 */

// Include necessary files
require_once '../config/database.php';
require_once '../config/app.php';

// Initialize variables
$message = '';
$messageType = '';
$users = [];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Connect to database
$conn = connectDB();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new user
    if (isset($_POST['create_user'])) {
        $employeeId = trim($_POST['employee_id']);
        $username = trim($_POST['username']);
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $userRole = trim($_POST['user_role']);
        
        // Validate input
        if (empty($employeeId) || empty($username) || empty($firstName) || 
            empty($lastName) || empty($email) || empty($password) || empty($userRole)) {
            $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            $messageType = 'danger';
        } else {
            // Check if employee ID or username already exists
            $checkSql = "SELECT COUNT(*) as count FROM users WHERE employee_id = ? OR username = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ss", $employeeId, $username);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $count = $checkResult->fetch_assoc()['count'];
            
            if ($count > 0) {
                $message = 'รหัสพนักงานหรือชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว';
                $messageType = 'danger';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insertSql = "INSERT INTO users (employee_id, username, password, first_name, last_name, email, user_role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("sssssss", $employeeId, $username, $hashedPassword, $firstName, $lastName, $email, $userRole);
                
                if ($insertStmt->execute()) {
                    $message = 'สร้างผู้ใช้ใหม่เรียบร้อยแล้ว';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการสร้างผู้ใช้: ' . $conn->error;
                    $messageType = 'danger';
                }
            }
        }
    }
    
    // Reset password
    if (isset($_POST['reset_password'])) {
        $userId = (int)$_POST['user_id'];
        $newPassword = trim($_POST['new_password']);
        
        if (empty($newPassword)) {
            $message = 'กรุณากรอกรหัสผ่านใหม่';
            $messageType = 'danger';
        } else if ($userId <= 0) {
            $message = 'กรุณาเลือกผู้ใช้';
            $messageType = 'danger';
        } else {
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user password
            $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $hashedPassword, $userId);
            
            if ($updateStmt->execute()) {
                $message = 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว รหัสผ่านใหม่คือ: ' . $newPassword;
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน: ' . $conn->error;
                $messageType = 'danger';
            }
        }
    }
    
    // Test password
    if (isset($_POST['test_login'])) {
        $employeeId = trim($_POST['test_employee_id']);
        $testPassword = trim($_POST['test_password']);
        
        if (empty($employeeId) || empty($testPassword)) {
            $message = 'กรุณากรอกรหัสพนักงานและรหัสผ่านเพื่อทดสอบ';
            $messageType = 'danger';
        } else {
            // Get user by employee ID
            $userSql = "SELECT user_id, employee_id, username, password, first_name, last_name FROM users WHERE employee_id = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("s", $employeeId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            
            if ($userResult->num_rows === 0) {
                $message = 'ไม่พบผู้ใช้ที่มีรหัสพนักงาน: ' . $employeeId;
                $messageType = 'danger';
            } else {
                $user = $userResult->fetch_assoc();
                
                // Verify password
                if (password_verify($testPassword, $user['password'])) {
                    $message = 'รหัสผ่านถูกต้อง! สามารถเข้าสู่ระบบได้ ผู้ใช้: ' . $user['first_name'] . ' ' . $user['last_name'];
                    $messageType = 'success';
                } else {
                    $message = 'รหัสผ่านไม่ถูกต้อง';
                    $messageType = 'danger';
                    
                    // For debugging: Show stored hashed password
                    $message .= '<br>รหัสผ่านที่เข้ารหัสในฐานข้อมูล: ' . $user['password'];
                    $message .= '<br>รหัสผ่านที่เข้ารหัสโดย password_hash ปัจจุบัน: ' . password_hash($testPassword, PASSWORD_DEFAULT);
                }
            }
        }
    }
}

// Get users list for dropdown
$usersSql = "SELECT user_id, employee_id, username, first_name, last_name, user_role FROM users ORDER BY first_name";
$usersResult = $conn->query($usersSql);

if ($usersResult->num_rows > 0) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get user details if user_id is provided
$userData = null;
if ($userId > 0) {
    $userDetailSql = "SELECT * FROM users WHERE user_id = ?";
    $userDetailStmt = $conn->prepare($userDetailSql);
    $userDetailStmt->bind_param("i", $userId);
    $userDetailStmt->execute();
    $userDetailResult = $userDetailStmt->get_result();
    
    if ($userDetailResult->num_rows > 0) {
        $userData = $userDetailResult->fetch_assoc();
    }
}

closeDB($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Manager - ระบบจัดการ IT Request</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <style>
        .password-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fas fa-key"></i> Password Manager
                    </h1>
                    <div>
                        <a href="<?php echo $base_url; ?>/admin/index.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> กลับไปยังหน้าแดชบอร์ด
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($message)) : ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> คำเตือน</h5>
                    <p>
                        ไฟล์นี้ใช้สำหรับจัดการรหัสผ่านในระบบ และควรใช้โดยผู้ดูแลระบบเท่านั้น
                        ควรลบหรือเปลี่ยนชื่อไฟล์นี้หลังจากใช้งานเสร็จเพื่อความปลอดภัย
                    </p>
                </div>
                
                <ul class="nav nav-tabs mb-4" id="passwordManagerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo empty($action) || $action === 'create' ? 'active' : ''; ?>" 
                                id="create-tab" data-bs-toggle="tab" data-bs-target="#create" 
                                type="button" role="tab" aria-controls="create" aria-selected="true">
                            <i class="fas fa-user-plus"></i> สร้างผู้ใช้ใหม่
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $action === 'reset' ? 'active' : ''; ?>" 
                                id="reset-tab" data-bs-toggle="tab" data-bs-target="#reset" 
                                type="button" role="tab" aria-controls="reset" aria-selected="false">
                            <i class="fas fa-sync"></i> รีเซ็ตรหัสผ่าน
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $action === 'test' ? 'active' : ''; ?>" 
                                id="test-tab" data-bs-toggle="tab" data-bs-target="#test" 
                                type="button" role="tab" aria-controls="test" aria-selected="false">
                            <i class="fas fa-check-circle"></i> ทดสอบรหัสผ่าน
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="passwordManagerTabsContent">
                    <!-- Create User Tab -->
                    <div class="tab-pane fade <?php echo empty($action) || $action === 'create' ? 'show active' : ''; ?>" 
                         id="create" role="tabpanel" aria-labelledby="create-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-plus"></i> สร้างผู้ใช้ใหม่</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="password_manager.php?action=create">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="employee_id" class="form-label">รหัสพนักงาน</label>
                                                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">ชื่อ</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">นามสกุล</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">อีเมล</label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="user_role" class="form-label">บทบาท</label>
                                                <select class="form-select" id="user_role" name="user_role" required>
                                                    <option value="user">ผู้ใช้งานทั่วไป</option>
                                                    <option value="it_staff">เจ้าหน้าที่ IT</option>
                                                    <option value="admin">ผู้ดูแลระบบ</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">รหัสผ่าน</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('password')">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('password')">
                                                สร้างรหัสผ่าน
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" name="create_user" class="btn btn-primary">
                                            <i class="fas fa-user-plus"></i> สร้างผู้ใช้
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reset Password Tab -->
                    <div class="tab-pane fade <?php echo $action === 'reset' ? 'show active' : ''; ?>" 
                         id="reset" role="tabpanel" aria-labelledby="reset-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-sync"></i> รีเซ็ตรหัสผ่าน</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="password_manager.php?action=reset">
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">เลือกผู้ใช้</label>
                                        <select class="form-select" id="user_id" name="user_id" required onchange="redirectToUser(this.value)">
                                            <option value="">-- เลือกผู้ใช้ --</option>
                                            <?php foreach ($users as $user) : ?>
                                                <option value="<?php echo $user['user_id']; ?>" <?php echo $userId == $user['user_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['employee_id'] . ' - ' . $user['first_name'] . ' ' . $user['last_name'] . ' (' . getRoleNameThai($user['user_role']) . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <?php if ($userData) : ?>
                                        <div class="alert alert-info">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>รหัสพนักงาน:</strong> <?php echo htmlspecialchars($userData['employee_id']); ?></p>
                                                    <p><strong>ชื่อผู้ใช้:</strong> <?php echo htmlspecialchars($userData['username']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></p>
                                                    <p><strong>บทบาท:</strong> <?php echo getRoleNameThai($userData['user_role']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('new_password')">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword('new_password')">
                                                    สร้างรหัสผ่าน
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" name="reset_password" class="btn btn-warning">
                                                <i class="fas fa-sync"></i> รีเซ็ตรหัสผ่าน
                                            </button>
                                        </div>
                                    <?php elseif ($userId > 0) : ?>
                                        <div class="alert alert-danger">
                                            ไม่พบข้อมูลผู้ใช้
                                        </div>
                                    <?php else : ?>
                                        <div class="alert alert-info">
                                            กรุณาเลือกผู้ใช้เพื่อรีเซ็ตรหัสผ่าน
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test Password Tab -->
                    <div class="tab-pane fade <?php echo $action === 'test' ? 'show active' : ''; ?>" 
                         id="test" role="tabpanel" aria-labelledby="test-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-check-circle"></i> ทดสอบรหัสผ่าน</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="password_manager.php?action=test">
                                    <div class="mb-3">
                                        <label for="test_employee_id" class="form-label">รหัสพนักงาน</label>
                                        <input type="text" class="form-control" id="test_employee_id" name="test_employee_id" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="test_password" class="form-label">รหัสผ่านที่ต้องการทดสอบ</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="test_password" name="test_password" required>
                                            <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('test_password')">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" name="test_login" class="btn btn-info">
                                            <i class="fas fa-check-circle"></i> ทดสอบรหัสผ่าน
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 mb-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลเพิ่มเติม</h5>
                </div>
                <div class="card-body">
                    <h6>วิธีการจัดการรหัสผ่าน</h6>
                    <ol>
                        <li>ใช้ <strong>สร้างผู้ใช้ใหม่</strong> เพื่อเพิ่มผู้ใช้งานใหม่ในระบบ</li>
                        <li>ใช้ <strong>รีเซ็ตรหัสผ่าน</strong> เมื่อผู้ใช้ลืมรหัสผ่านหรือมีปัญหาในการเข้าสู่ระบบ</li>
                        <li>ใช้ <strong>ทดสอบรหัสผ่าน</strong> เพื่อตรวจสอบว่ารหัสผ่านถูกต้องหรือไม่</li>
                    </ol>
                    
                    <h6>หมายเหตุเกี่ยวกับความปลอดภัย</h6>
                    <ul>
                        <li>รหัสผ่านถูกเข้ารหัสด้วย <code>password_hash()</code> ซึ่งใช้อัลกอริทึม BCrypt ที่ปลอดภัย</li>
                        <li>รหัสผ่านที่ดีควรมีความยาวอย่างน้อย 8 ตัวอักษร และประกอบด้วยตัวอักษรพิมพ์ใหญ่ พิมพ์เล็ก ตัวเลข และอักขระพิเศษ</li>
                        <li>ควรเปลี่ยนรหัสผ่านเป็นประจำเพื่อความปลอดภัย</li>
                        <li><strong>คำเตือน:</strong> ลบหรือเปลี่ยนชื่อไฟล์นี้หลังจากใช้งานเสร็จเพื่อความปลอดภัยของระบบ</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="<?php echo $base_url; ?>/assets/js/bootstrap.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Generate random password
        function generatePassword(inputId) {
            const length = 12;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
            let password = "";
            
            // Ensure at least one character from each category
            password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)]; // Uppercase
            password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)]; // Lowercase
            password += "0123456789"[Math.floor(Math.random() * 10)]; // Number
            password += "!@#$%^&*()_+"[Math.floor(Math.random() * 12)]; // Special
            
            // Fill the rest randomly
            for (let i = 4; i < length; i++) {
                password += charset[Math.floor(Math.random() * charset.length)];
            }
            
            // Shuffle the password
            password = password.split('').sort(() => 0.5 - Math.random()).join('');
            
            document.getElementById(inputId).value = password;
            document.getElementById(inputId).type = 'text';
            const icon = document.getElementById(inputId).nextElementSibling.querySelector('i');
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
        
        // Redirect to user for reset password
        function redirectToUser(userId) {
            if (userId) {
                window.location.href = 'password_manager.php?action=reset&user_id=' + userId;
            }
        }
    </script>
</body>
</html>

<?php
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