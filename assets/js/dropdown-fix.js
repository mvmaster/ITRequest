/**
 * Bootstrap Dropdown Fix - เพิ่มเติมสำหรับแก้ไขปัญหา dropdown
 */

document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม Event Listener สำหรับการคลิกปุ่ม dropdown
    document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            
            // ตรวจสอบว่ามี Bootstrap JavaScript แล้วหรือไม่
            if (typeof bootstrap !== 'undefined') {
                // ใช้ API ของ Bootstrap 5 เพื่อแสดง dropdown
                var dropdownInstance = new bootstrap.Dropdown(this);
                dropdownInstance.toggle();
            } else {
                // ทางเลือกสำรอง: เพิ่ม/ลบคลาส show ด้วยตัวเอง
                var dropdownMenu = this.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    dropdownMenu.classList.toggle('show');
                    this.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
                }
                
                // ปิด dropdown อื่นๆ
                document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                    if (openMenu !== dropdownMenu) {
                        openMenu.classList.remove('show');
                        openMenu.previousElementSibling.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // ปิด dropdown เมื่อคลิกนอกพื้นที่
                document.addEventListener('click', function closeDropdown(event) {
                    if (!event.target.closest('.dropdown')) {
                        document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                            openMenu.classList.remove('show');
                            if (openMenu.previousElementSibling) {
                                openMenu.previousElementSibling.setAttribute('aria-expanded', 'false');
                            }
                        });
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            }
        });
    });
});