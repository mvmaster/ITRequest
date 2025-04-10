<?php
// Include necessary files
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../auth/session.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit;
}

// Get current user info
$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Initialize variables
$message = '';
$messageType = '';

// Connect to database
$conn = connectDB();

// Get user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    // User not found
    closeDB($conn);
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department = $_POST['department'] ?? '';
    
    // Validate input
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = 'กรุณากรอกชื่อ';
    }
    
    if (empty($lastName)) {
        $errors[] = 'กรุณากรอกนามสกุล';
    }
    
    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    
    // Check if email is already used by another user
    if (!empty($email) && $email !== $user['email']) {
        $checkEmailSql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $checkEmailStmt = $conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param("si", $email, $userId);
        $checkEmailStmt->execute();
        $checkEmailResult = $checkEmailStmt->get_result();
        
        if ($checkEmailResult->num_rows > 0) {
            $errors[] = 'อีเมลนี้ถูกใช้งานโดยผู้ใช้อื่นแล้ว';
        }
        
        $checkEmailStmt->close();
    }
    
    // Process update if no errors
    if (empty($errors)) {
        // Sanitize input
        $firstName = sanitizeInput($firstName, $conn);
        $lastName = sanitizeInput($lastName, $conn);
        $email = sanitizeInput($email, $conn);
        $phone = sanitizeInput($phone, $conn);
        $department = sanitizeInput($department, $conn);
        
        // Update user in database
        $updateSql = "UPDATE users SET 
                      first_name = ?, 
                      last_name = ?, 
                      email = ?, 
                      phone = ?, 
                      department = ?, 
                      updated_at = CURRENT_TIMESTAMP 
                      WHERE user_id = ?";
        
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sssssi", $firstName, $lastName, $email, $phone, $department, $userId);
        
        if ($updateStmt->execute()) {
            // Update session variables
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            
            $message = 'ข้อมูลโปรไฟล์ของคุณถูกอัปเดตเรียบร้อยแล้ว';
            $messageType = 'success';
            
            // Refresh user data
            $sql = "SELECT * FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $updateStmt->close();
    } else {
        $message = 'พบข้อผิดพลาด: ' . implode(', ', $errors);
        $messageType = 'danger';
    }
}

closeDB($conn);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="page-header">
                <i class="fas fa-user-circle"></i> โปรไฟล์ของฉัน
            </h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4">
            <!-- User Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-id-card"></i> ข้อมูลผู้ใช้งาน</h5>
                </div>
                <div class="card-body text-center">
                    <div class="avatar bg-primary text-white rounded-circle mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem; display: flex; align-items: center; justify-content: center;">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($user['employee_id']); ?></p>
                    <p class="text-muted mb-2">
                        <span class="badge bg-info"><?php echo htmlspecialchars($userRole === 'admin' ? 'ผู้ดูแลระบบ' : ($userRole === 'it_staff' ? 'เจ้าหน้าที่ IT' : 'ผู้ใช้งาน')); ?></span>
                    </p>
                    <div class="d-flex justify-content-center">
                        <a href="change-password.php" class="btn btn-outline-primary">
                            <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                        </a>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-muted">
                        <small>วันที่ลงทะเบียน: <?php echo formatDateThai($user['created_at']); ?></small><br>
                        <small>เข้าสู่ระบบล่าสุด: <?php echo formatDateThai($user['last_login']); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Profile Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> แก้ไขข้อมูลส่วนตัว</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)) : ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form action="profile.php" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="employee_id" class="form-label">รหัสพนักงาน</label>
                                <input type="text" class="form-control" id="employee_id" value="<?php echo htmlspecialchars($user['employee_id']); ?>" readonly disabled>
                                <small class="form-text text-muted">รหัสพนักงานไม่สามารถแก้ไขได้</small>
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                                <small class="form-text text-muted">ชื่อผู้ใช้ไม่สามารถแก้ไขได้</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="department" class="form-label">แผนก</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($user['department']); ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> บันทึกข้อมูล
                            </button>
                            <a href="<?php echo $userRole === 'admin' || $userRole === 'it_staff' ? '../admin/index.php' : 'index.php'; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> ยกเลิก
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>