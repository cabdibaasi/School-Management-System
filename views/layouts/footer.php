<?php
/**
 * Global Footer Layout
 */
?>
        </div> <!-- End container-fluid -->
    </div> <!-- End content -->
</div> <!-- End wrapper -->

<!-- Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- FontAwesome for fallback support -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
<!-- Custom Main Script -->
<script src="<?= asset('js/main.js') ?>"></script>

<!-- Theme Toggle Script -->
<script>
(function() {
    function applyTheme(theme) {
        // Set on BOTH html and body for maximum compatibility
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        const btn = document.getElementById('themeToggleBtn');
        const icon = document.getElementById('themeIcon');

        if (theme === 'dark') {
            if (icon) { icon.className = 'fas fa-sun'; icon.style.color = '#fbbf24'; }
            if (btn) { btn.title = 'Switch to Light Mode'; btn.style.background = '#1c263f'; btn.style.color = '#fbbf24'; }
        } else {
            if (icon) { icon.className = 'fas fa-moon'; icon.style.color = '#64748b'; }
            if (btn) { btn.title = 'Switch to Dark Mode'; btn.style.background = ''; btn.style.color = ''; }
        }
    }

    // Apply saved theme immediately when DOM is ready
    const saved = localStorage.getItem('theme') || 'light';
    applyTheme(saved);

    // Wire up button
    const btn = document.getElementById('themeToggleBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
})();
</script>

</body>
</html>
