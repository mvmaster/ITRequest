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
$oldPasswordErr = '';
$newPasswordErr = '';
$confirmPasswordErr = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    $hasError = false;
    
    if (empty($oldPassword)) {
        $oldPasswordErr = 'กรุณากรอกรหัสผ่านปัจจุบัน';
        $hasError = true;
    }
    
    if (empty($newPassword)) {
        $newPasswordErr = 'กรุณากรอกรหัสผ่านใหม่';
        $hasError = true;
    } elseif (strlen($newPassword) < 8) {
        $newPasswordErr = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        $hasError = true;
    }
    
    if (empty($confirmPassword)) {
        $confirmPasswordErr = 'กรุณายืนยันรหัสผ่านใหม่';
        $hasError = true;
    } elseif ($newPassword !== $confirmPassword) {
        $confirmPasswordErr = 'รหัสผ่านยืนยันไม่ตรงกับรหัสผ่านใหม่';
        $hasError = true;
    }
    
    // If no validation errors, proceed to change password
    if (!$hasError) {
        $conn = connectDB();
        
        // Get current password from database
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $currentHashedPassword = $user['password'];
            
            // Verify old password
            if (password_verify($oldPassword, $currentHashedPassword)) {
                // Hash new password
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password in database
                $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $hashedNewPassword, $userId);
                
                if ($updateStmt->execute()) {
                    $message = 'รหัสผ่านของคุณถูกเปลี่ยนเรียบร้อยแล้ว';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน: ' . $conn->error;
                    $messageType = 'danger';
                }
                
                $updateStmt->close();
            } else {
                $oldPasswordErr = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
                $messageType = 'danger';
            }
        } else {
            $message = 'ไม่พบข้อมูลผู้ใช้';
            $messageType = 'danger';
        }
        
        $stmt->close();
        closeDB($conn);
    }
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="page-header">
                <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
            </h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <?php if (!empty($message)) : ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">เปลี่ยนรหัสผ่านของคุณ</h5>
                </div>
                <div class="card-body">
                    <form action="change-password.php" method="post">
                        <div class="mb-3">
                            <label for="old_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control <?php echo !empty($oldPasswordErr) ? 'is-invalid' : ''; ?>" 
                                       id="old_password" name="old_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="old_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!empty($oldPasswordErr)) : ?>
                                    <div class="invalid-feedback"><?php echo $oldPasswordErr; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control <?php echo !empty($newPasswordErr) ? 'is-invalid' : ''; ?>" 
                                       id="new_password" name="new_password" required 
                                       placeholder="รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!empty($newPasswordErr)) : ?>
                                    <div class="invalid-feedback"><?php echo $newPasswordErr; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control <?php echo !empty($confirmPasswordErr) ? 'is-invalid' : ''; ?>" 
                                       id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!empty($confirmPasswordErr)) : ?>
                                    <div class="invalid-feedback"><?php echo $confirmPasswordErr; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                            </button>
                            <a href="<?php echo $userRole === 'admin' || $userRole === 'it_staff' ? '../admin/index.php' : 'index.php'; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> ยกเลิก
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt"></i> คำแนะนำการตั้งรหัสผ่าน</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> ความยาวอย่างน้อย 8 ตัวอักษร
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> ประกอบด้วยตัวอักษรพิมพ์ใหญ่และพิมพ์เล็ก
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> มีตัวเลขอย่างน้อย 1 ตัว
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> มีอักขระพิเศษอย่างน้อย 1 ตัว เช่น !@#$%^&*()
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-exclamation-triangle text-warning"></i> ไม่ควรใช้ข้อมูลส่วนตัวที่คาดเดาได้ง่าย เช่น ชื่อ วันเกิด
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const inputField = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (inputField.type === 'password') {
                    inputField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    inputField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>