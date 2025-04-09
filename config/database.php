<?php
/**
 * Database Connection Configuration
 * 
 * ไฟล์นี้ใช้สำหรับการเชื่อมต่อกับฐานข้อมูล MySQL
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // เปลี่ยนเป็น username ของคุณ
define('DB_PASS', 'P@ssw0rd');         // เปลี่ยนเป็น password ของคุณ
define('DB_NAME', 'it_request_system'); // เปลี่ยนเป็นชื่อฐานข้อมูล
$base_url = "http://192.168.0.4/request";
// Connection function
function connectDB() {
    // Create connection using mysqli
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
    // Check connection
    if ($conn->connect_error) {
        die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }
    
    return $conn;
}

// PDO Connection function (alternative connection method)
function connectPDO() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
    }
}

// Close connection function
function closeDB($conn) {
    $conn->close();
}

// Sanitize input data to prevent SQL injection
function sanitizeInput($data, $conn) {
    return $conn->real_escape_string($data);
}
