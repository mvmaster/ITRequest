// modal-fix.js
document.addEventListener('DOMContentLoaded', function() {
    // ค้นหาทุก elements ที่มี data-bs-toggle="modal"
    var modalButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
    
    // เพิ่ม event listener สำหรับแต่ละปุ่ม
    modalButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            // รับค่า target ที่ปุ่มนี้จะเปิด
            var targetId = button.getAttribute('data-bs-target');
            
            // ถ้าไม่มี target, ลองหา href แทน (กรณีใช้ <a> tag)
            if (!targetId) {
                targetId = button.getAttribute('href');
            }
            
            // ถ้ามี target หรือ href
            if (targetId) {
                // หา modal element จาก selector
                var modalElement = document.querySelector(targetId);
                
                if (modalElement) {
                    try {
                        // พยายามสร้าง Modal instance และเปิด
                        var modalInstance = new bootstrap.Modal(modalElement);
                        modalInstance.show();
                        
                        console.log('Modal opened:', targetId);
                    } catch (error) {
                        console.error('Error opening modal:', error);
                    }
                } else {
                    console.error('Modal element not found:', targetId);
                }
            }
        });
    });
    
    // Debug: แสดงข้อมูลเกี่ยวกับ Modal ที่พบในหน้า
    var modalElements = document.querySelectorAll('.modal');
    console.log('Found', modalElements.length, 'modal elements on the page');
    
    // Debug: ทดสอบว่า bootstrap object มีอยู่จริงหรือไม่
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap JS is loaded correctly');
        console.log('Bootstrap version:', bootstrap.Dropdown ? 'v5+' : 'v4 or lower');
    } else {
        console.error('Bootstrap JS is not loaded!');
    }
});