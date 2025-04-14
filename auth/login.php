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
require_once '../config/app.php';

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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจัดการ IT Request</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Font - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }
        
        .login-card {
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: white;
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            background: linear-gradient(135deg, #3a0ca3 0%, #4361ee 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQ0MCIgaGVpZ2h0PSIyNTAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgNTBjNDkuMjIgMzMuNTEgMTM0IDYyLjQ1NSAyNTAuOTU2IDYyLjQ1NSAyNDAuMDczIDAgMzU5LjMxMy0xMzggMTU5LjExMi0xNzUuMDUzQzE5NS4wNTQtMTI2LjUzIDMyMi45OTcgMjUwLjQ1IDUyNyA4My4zODIgNzMxLjAwMy04My42ODggODEyIDExNiAxMTM5IDE3N2M5NSAxNS4zOTMgMTk1Ljc1IDE1LjY2NyAzMDEgMHYyMTlIMFY1MHoiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4xKSIgZmlsbC1ydWxlPSJldmVub2RkIi8+PC9zdmc+');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }
        
        .login-logo {
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .login-logo i {
            font-size: 4rem;
            background: rgba(255, 255, 255, 0.2);
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 1rem;
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-control {
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 1px solid #e0e0e0;
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .btn-primary {
            background: #4361ee;
            border-color: #4361ee;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #3a0ca3;
            border-color: #3a0ca3;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .login-footer {
            background-color: #f8f9fa;
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-footer a {
            color: #4361ee;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #3a0ca3;
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .invalid-feedback {
            font-size: 0.85rem;
            color: #dc3545;
            margin-top: 0.5rem;
        }
        
        .is-invalid {
            border-color: #dc3545;
        }
        
        .is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 0 1rem;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-headset"></i>
                    <h2 class="mb-0">ระบบจัดการ IT Request</h2>
                </div>
                <p class="mb-0">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($login_err)) : ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $login_err; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-4">
                        <label for="employee_id" class="form-label">รหัสพนักงาน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="employee_id" id="employee_id" 
                                   class="form-control <?php echo (!empty($employee_id_err)) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo $employee_id; ?>" 
                                   placeholder="กรอกรหัสพนักงาน">
                            <?php if (!empty($employee_id_err)) : ?>
                                <div class="invalid-feedback"><?php echo $employee_id_err; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="password" 
                                   class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                   placeholder="กรอกรหัสผ่าน">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if (!empty($password_err)) : ?>
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="login-footer">
                <a href="<?php echo BASE_URL; ?>">
                    <i class="fas fa-arrow-left me-2"></i> กลับไปหน้าหลัก
                </a>
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted small">
            <p>&copy; <?php echo date('Y'); ?> ระบบจัดการ IT Request</p>
            <p>หากมีปัญหาในการเข้าสู่ระบบ กรุณาติดต่อฝ่าย IT</p>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>