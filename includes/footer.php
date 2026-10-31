        </div> <!-- End of Main Content Container -->

        <footer class="bg-white text-center text-muted py-3 border-top mt-auto shadow-sm no-print px-3">
            <small>&copy; <?= date('Y'); ?> <span class="text-success fw-semibold">GA Office Manulife LKG MW Medan</span> - Portal Sistem Informasi Agen</small>
        </footer>
    </div> <!-- End of Page Content -->
</div> <!-- End of Wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function isMobile() {
            return window.innerWidth < 992;
        }

        function openMobileSidebar() {
            if (sidebar) sidebar.classList.add('active');
            if (sidebarOverlay) sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileSidebar() {
            if (sidebar) sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        function toggleSidebar() {
            if (isMobile()) {
                if (sidebar && sidebar.classList.contains('active')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            } else {
                if (sidebar) {
                    sidebar.classList.toggle('desktop-collapsed');
                    const isCollapsed = sidebar.classList.contains('desktop-collapsed');
                    try {
                        localStorage.setItem('sidebar_desktop_collapsed', isCollapsed ? '1' : '0');
                    } catch(e) {}
                    // Trigger window resize event so charts or maps recalculate their width
                    window.dispatchEvent(new Event('resize'));
                }
            }
        }

        // Restore user desktop preference
        if (!isMobile() && sidebar) {
            try {
                if (localStorage.getItem('sidebar_desktop_collapsed') === '1') {
                    sidebar.classList.add('desktop-collapsed');
                }
            } catch(e) {}
        }

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
        }

        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeMobileSidebar();
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeMobileSidebar);
        }

        // Close drawer when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isMobile() && sidebar && sidebar.classList.contains('active')) {
                closeMobileSidebar();
            }
        });

        // Reset state on window resize across breakpoint
        window.addEventListener('resize', function() {
            if (!isMobile()) {
                closeMobileSidebar();
                try {
                    if (localStorage.getItem('sidebar_desktop_collapsed') === '1') {
                        sidebar.classList.add('desktop-collapsed');
                    } else {
                        sidebar.classList.remove('desktop-collapsed');
                    }
                } catch(e) {}
            }
        });

        // Close mobile sidebar when clicking on any navigation link inside sidebar
        if (sidebar) {
            const navLinks = sidebar.querySelectorAll('ul.components a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (isMobile()) {
                        closeMobileSidebar();
                    }
                });
            });
        }
    });
</script>
</body>
</html>