<?php
// Replace the includes/footer.php with this updated version
?>
</div><!-- End container-fluid -->
    
    <footer class="footer bg-light mt-5 py-3">
        <div class="container text-center">
            <span class="text-muted">ระบบจัดการ IT Request &copy; <?php echo date('Y'); ?></span>
        </div>
    </footer>
    <script>
    const BASE_URL = "<?php echo BASE_URL; ?>";
</script>
   <!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (ถ้าจำเป็น) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

<!-- JavaScript สำหรับช่วยแก้ไขปัญหา Modal ชั่วคราว -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // อีกทางเลือกหนึ่งคือ initialize ทุก Modal ในหน้าโดยตรง
    var modals = document.querySelectorAll('.modal');
    modals.forEach(function(modalElement) {
        try {
            var modal = new bootstrap.Modal(modalElement);
            // เก็บ instance ไว้ให้ใช้งานได้ทั่วไป
            modalElement._bsModal = modal;
        } catch (e) {
            console.error('Error initializing modal:', e);
        }
    });
    
    // เพิ่ม event listener สำหรับปุ่มที่เปิด Modal
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var targetSelector = button.getAttribute('data-bs-target') || button.getAttribute('href');
            if (targetSelector) {
                var modalElement = document.querySelector(targetSelector);
                if (modalElement && modalElement._bsModal) {
                    modalElement._bsModal.show();
                } else if (modalElement) {
                    try {
                        var modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    } catch (e) {
                        console.error('Failed to create modal on-demand:', e);
                    }
                }
            }
        });
    });
});
</script>
</body>
</html>