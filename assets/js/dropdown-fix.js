/**
 * dropdown-fix.js
 * ไฟล์นี้ใช้แก้ไขปัญหา dropdown ใน Bootstrap 5
 */

document.addEventListener('DOMContentLoaded', function() {
    // ฟังก์ชันตรวจสอบว่า bootstrap loaded หรือไม่
    function isBootstrapLoaded() {
        return (typeof bootstrap !== 'undefined');
    }

    // ฟังก์ชันแก้ไข dropdown แบบ manual ถ้า bootstrap ไม่ถูกโหลด
    function fixDropdowns() {
        // Check if Bootstrap is loaded
        if (!isBootstrapLoaded()) {
            console.log('Bootstrap JS ไม่ถูกโหลด กำลังใช้วิธีแก้ไขแบบ manual');
            
            // Initialize dropdowns manually
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
            dropdownToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // สลับสถานะ .show ของ dropdown-menu
                    const menu = this.nextElementSibling;
                    if (menu && menu.classList.contains('dropdown-menu')) {
                        // ปิด dropdowns อื่นๆ ก่อน
                        document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                            if (openMenu !== menu) {
                                openMenu.classList.remove('show');
                            }
                        });
                        
                        // สลับสถานะ menu ปัจจุบัน
                        menu.classList.toggle('show');
                    }
                });
            });
            
            // ปิด dropdown เมื่อคลิกที่อื่นๆ
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        menu.classList.remove('show');
                    });
                }
            });
            
            // เพิ่ม CSS ที่จำเป็น
            addDropdownStyles();
        } else {
            console.log('Bootstrap JS โหลดสมบูรณ์ กำลังเริ่มต้น dropdowns...');
            
            // ใช้ Bootstrap API เพื่อเริ่มต้น dropdowns
            document.querySelectorAll('.dropdown-toggle').forEach(function(dropdownToggle) {
                try {
                    new bootstrap.Dropdown(dropdownToggle);
                } catch (e) {
                    console.error('Error initializing dropdown:', e);
                }
            });
        }
    }
    
    // ฟังก์ชันเพิ่ม CSS styles สำหรับ dropdown
    function addDropdownStyles() {
        // สร้าง style element
        const style = document.createElement('style');
        style.textContent = `
            .dropdown-menu.show {
                display: block;
            }
            .dropdown-menu {
                position: absolute;
                top: 100%;
                left: 0;
                z-index: 1000;
                display: none;
                min-width: 10rem;
                padding: 0.5rem 0;
                margin: 0.125rem 0 0;
                font-size: 1rem;
                color: #212529;
                text-align: left;
                list-style: none;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid rgba(0, 0, 0, 0.15);
                border-radius: 0.25rem;
            }
            .dropdown-menu-end {
                right: 0;
                left: auto;
            }
            .dropdown-item {
                display: block;
                width: 100%;
                padding: 0.25rem 1.5rem;
                clear: both;
                font-weight: 400;
                color: #212529;
                text-align: inherit;
                white-space: nowrap;
                background-color: transparent;
                border: 0;
            }
            .dropdown-item:hover, .dropdown-item:focus {
                color: #16181b;
                text-decoration: none;
                background-color: #f8f9fa;
            }
            .dropdown-item.active, .dropdown-item:active {
                color: #fff;
                text-decoration: none;
                background-color: #0d6efd;
            }
        `;
        document.head.appendChild(style);
    }
    
    // ตรวจสอบสถานะ active ของเมนู
    function checkActiveMenuItems() {
        const currentPath = window.location.pathname;
        
        // ตรวจสอบลิงก์ทั้งหมดในเมนู
        document.querySelectorAll('.navbar-nav .nav-link').forEach(function(link) {
            // ดึง href และแปลงเป็น path เปรียบเทียบ
            const href = link.getAttribute('href');
            if (href) {
                // ตัดโปรโตคอล และโดเมนออก
                const url = new URL(href, window.location.origin);
                const path = url.pathname;
                
                // ตรวจสอบความยาวของ path และทำการเปรียบเทียบ
                if (path && path.length > 1 && currentPath.includes(path)) {
                    link.classList.add('active');
                    const parent = link.closest('.nav-item');
                    if (parent) {
                        parent.classList.add('active');
                    }
                }
            }
        });
    }
    
    // รอให้ DOM โหลดเสร็จแล้วทำงาน
    fixDropdowns();
    
    // ตรวจสอบเมนู active
    checkActiveMenuItems();
});