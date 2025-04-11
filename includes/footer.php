</div><!-- End container-fluid -->
    
    <footer class="footer bg-light mt-5 py-3">
        <div class="container text-center">
            <span class="text-muted">ระบบจัดการ IT Request &copy; <?php echo date('Y'); ?></span>
        </div>
    </footer>
    <script>
    const BASE_URL = "<?php echo BASE_URL; ?>";
</script>

    <!-- jQuery (จำเป็นสำหรับ Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dropdown-fix.js"></script>
    
    <!-- Initialize Bootstrap tooltips and popovers -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Enable popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Enable dropdowns explicitly
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
    <script>
        
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded');
    } else {
        console.log('jQuery version: ' + jQuery.fn.jquery);
    }
</script>    
    
</body>
</html>