<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ IT Request</title>
    
    <!-- ดาวน์โหลด Bootstrap CSS จาก CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <!-- เพิ่มง่ายๆ แบบ inline เพื่อแก้ไขปัญหา -->
    <style>
.navbar {
    display: flex !important;
    background-color: #0d6efd !important;
    padding: 0.5rem 1rem;
}

.navbar-dark .navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
}

.navbar-dark .navbar-brand {
    color: white !important;
}

.dropdown-menu {
    background-color: white;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.25rem;
    color: #212529;
}

.dropdown-item {
    color: #212529;
}
    </style>
    
</head>
<body>