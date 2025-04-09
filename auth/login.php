<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'it_staff') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../user/index.php");
    }
    exit;
}

// Include database connection
require_once '../config/database.php';

// Define variables and set to empty values
$employee_id = $password = "";
$employee_id_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate employee ID
    if (empty(trim($_POST["employee_id"]))) {
        $employee_id_err = "กรุณากรอกรหัสพนักงาน";
    } else {
        $employee_id = trim($_POST["employee_id"]);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "กรุณากรอกรหัสผ่าน";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check input errors before authenticating
    if (empty($employee_id_err) && empty($password_err)) {
        // Connect to database
        $conn = connectDB();
        
        // Prepare a select statement
        $sql = "SELECT user_id, employee_id, username, password, first_name, last_name, user_role 
                FROM users WHERE employee_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $param_employee_id);
        $param_employee_id = $employee_id;
        
        // Execute the statement
        if ($stmt->execute()) {
            // Store result
            $stmt->store_result();
            
            // Check if employee ID exists
            if ($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($user_id, $employee_id, $username, $hashed_password, $first_name, $last_name, $user_role);
                
                if ($stmt->fetch()) {
                    // Verify password
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION["user_id"] = $user_id;
                        $_SESSION["employee_id"] = $employee_id;
                        $_SESSION["username"] = $username;
                        $_SESSION["first_name"] = $first_name;
                        $_SESSION["last_name"] = $last_name;
                        $_SESSION["user_role"] = $user_role;
                        
                        // Update last login time
                        $update_sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $user_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Redirect based on role
                        if ($user_role === 'admin' || $user_role === 'it_staff') {
                            header("Location: ../admin/index.php");
                        } else {
                            header("Location: ../user/index.php");
                        }
                        exit;
                    } else {
                        // Invalid password
                        $login_err = "รหัสพนักงานหรือรหัสผ่านไม่ถูกต้อง";
                    }
                }
            } else {
                // Employee ID doesn't exist
                $login_err = "รหัสพนักงานหรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $login_err = "เกิดข้อผิดพลาด กรุณาลองใหม่ภายหลัง";
        }
        
        // Close statement and connection
        $stmt->close();
        closeDB($conn);
    }
}

$base_url = "http://192.168.0.4/request";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจัดการ IT Request</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-body">
                    <div class="login-logo">
                        <i class="fas fa-headset"></i>
                        <h2 class="text-center mb-4">ระบบจัดการ IT Request</h2>
                    </div>
                    
                    <?php if (!empty($login_err)) : ?>
                        <div class="alert alert-danger"><?php echo $login_err; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">รหัสพนักงาน</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="employee_id" id="employee_id" class="form-control <?php echo (!empty($employee_id_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $employee_id; ?>" placeholder="กรอกรหัสพนักงาน">
                                <?php if (!empty($employee_id_err)) : ?>
                                    <div class="invalid-feedback"><?php echo $employee_id_err; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="กรอกรหัสผ่าน">
                                <?php if (!empty($password_err)) : ?>
                                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">เข้าสู่ระบบ</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="<?php echo $base_url; ?>" class="text-decoration-none">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้าหลัก
                </a>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="<?php echo $base_url; ?>/assets/js/bootstrap.min.js"></script>
</body>
</html>
