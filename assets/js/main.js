/**
 * Talent Institute — School Management System
 * Core JavaScript: Sidebar, Theme Toggle, Responsive Helpers
 */

document.addEventListener("DOMContentLoaded", function () {

    /* ── SIDEBAR TOGGLE ───────────────────────────────────── */
    const sidebarToggle  = document.getElementById("sidebarCollapse");
    const sidebar        = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add("active");
        if (sidebarOverlay) sidebarOverlay.classList.add("active");
        document.body.style.overflow = "hidden";
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove("active");
        if (sidebarOverlay) sidebarOverlay.classList.remove("active");
        document.body.style.overflow = "";
    }

    function toggleSidebar() {
        if (sidebar && sidebar.classList.contains("active")) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", closeSidebar);
    }

    // Auto-close sidebar on mobile when a nav link is clicked
    if (sidebar) {
        const navLinks = sidebar.querySelectorAll("ul li a");
        navLinks.forEach(function (link) {
            link.addEventListener("click", function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });
    }

    // On resize: if going back to desktop, reset sidebar state
    window.addEventListener("resize", function () {
        if (window.innerWidth >= 992) {
            closeSidebar();
            document.body.style.overflow = "";
        }
    });


    /* ── DARK / LIGHT THEME TOGGLE ────────────────────────── */
    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        document.body.setAttribute("data-theme", theme);
        localStorage.setItem("theme", theme);

        const btn  = document.getElementById("themeToggleBtn");
        const icon = document.getElementById("themeIcon");

        if (theme === "dark") {
            if (icon) { icon.className = "fas fa-sun"; icon.style.color = "#fbbf24"; }
            if (btn)  { btn.title = "Switch to Light Mode"; btn.style.background = "#1c263f"; }
        } else {
            if (icon) { icon.className = "fas fa-moon"; icon.style.color = "#6b7280"; }
            if (btn)  { btn.title = "Switch to Dark Mode"; btn.style.background = ""; }
        }
    }

    // Restore saved preference
    var savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);

    // Wire up button
    var themeBtn = document.getElementById("themeToggleBtn");
    if (themeBtn) {
        themeBtn.addEventListener("click", function () {
            var current = document.documentElement.getAttribute("data-theme") || "light";
            applyTheme(current === "dark" ? "light" : "dark");
        });
    }


    /* ── PASSWORD VISIBILITY TOGGLE ───────────────────────── */
    document.querySelectorAll(".toggle-password-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var targetId = this.getAttribute("data-target");
            var input    = document.getElementById(targetId);
            if (!input) return;
            var icon = this.querySelector("i");
            if (input.type === "password") {
                input.type = "text";
                if (icon) icon.className = "fas fa-eye-slash";
            } else {
                input.type = "password";
                if (icon) icon.className = "fas fa-eye";
            }
        });
    });


    /* ── AUTO-DISMISS FLASH ALERTS (5s) ───────────────────── */
    setTimeout(function () {
        document.querySelectorAll(".alert.alert-dismissible").forEach(function (el) {
            try {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
                if (bsAlert) bsAlert.close();
            } catch (e) { /* ignore if Bootstrap not loaded yet */ }
        });
    }, 5000);


    /* ── MAKE ALL TABLES RESPONSIVE ───────────────────────── */
    // Wrap any bare table that isn't already inside .table-responsive
    document.querySelectorAll("table.table").forEach(function (tbl) {
        if (!tbl.closest(".table-responsive")) {
            var wrapper = document.createElement("div");
            wrapper.className = "table-responsive";
            tbl.parentNode.insertBefore(wrapper, tbl);
            wrapper.appendChild(tbl);
        }
    });


    /* ── TOOLTIP INIT ─────────────────────────────────────── */
    if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }


    /* ── CONFIRM DELETE DIALOGS ───────────────────────────── */
    document.querySelectorAll("[data-confirm]").forEach(function (el) {
        el.addEventListener("click", function (e) {
            var msg = this.getAttribute("data-confirm") || "Are you sure?";
            if (!confirm(msg)) {
                e.preventDefault();
                return false;
            }
        });
    });

});
