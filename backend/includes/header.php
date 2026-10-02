<?php
// includes/header.php — Shared Header Navbar & Logout Modal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = getCurrentUser();
$logoutPath = ($currentUser['role'] ?? '') === 'admin' ? '../admin/logout.php' : '../voter/logout.php';
?>
<header class="top-navbar">
    <button class="menu-toggle" id="menuToggleBtn" title="Toggle Sidebar">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
        </svg>
    </button>

    <div class="header-user-dropdown">
        <button class="user-btn" id="userMenuBtn">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
            </svg>
            <span><?php echo ($currentUser['role'] ?? '') === 'admin' ? 'Admin' : htmlspecialchars($currentUser['full_name'] ?? 'Voter'); ?></span>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                <path d="M7 10l5 5 5-5z"/>
            </svg>
        </button>

        <div class="dropdown-menu" id="userDropdownMenu">
            <a href="#" class="dropdown-item-logout" id="triggerLogoutBtn">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
                Log out
            </a>
        </div>
    </div>
</header>

<!-- Mobile Sidebar Backdrop Overlay -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Logout Confirmation Modal (Documented Admin/Voter Logout Flow) -->
<div class="modal-overlay" id="logoutModal" style="display: none;">
    <div class="modal-content">
        <h3 class="modal-title">Confirm Logout</h3>
        <p class="modal-text">Are you sure you want to log out of Tomorrow Vote?</p>
        <div class="modal-actions">
            <a href="<?php echo $logoutPath; ?>?confirm=1" class="btn-modal-confirm">Log out</a>
            <button type="button" class="btn-modal-cancel" id="cancelLogoutBtn">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggleBtn = document.getElementById('menuToggleBtn');
    const appContainer = document.querySelector('.app-container');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

    // Restore desktop sidebar state from local storage
    if (window.innerWidth >= 992 && localStorage.getItem('sidebar_collapsed') === 'true' && appContainer) {
        appContainer.classList.add('sidebar-collapsed');
    }

    function closeMobileSidebar() {
        if (appSidebar) appSidebar.classList.remove('mobile-open');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
        document.body.classList.remove('sidebar-drawer-active');
    }

    function openMobileSidebar() {
        if (appSidebar) appSidebar.classList.add('mobile-open');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
        document.body.classList.add('sidebar-drawer-active');
    }

    if (menuToggleBtn) {
        menuToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth < 992) {
                // Mobile Drawer Toggle
                if (appSidebar && appSidebar.classList.contains('mobile-open')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            } else if (appContainer) {
                // Desktop Collapse Toggle
                appContainer.classList.toggle('sidebar-collapsed');
                const isCollapsed = appContainer.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeMobileSidebar);
    }

    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeMobileSidebar);
    }

    // Auto-close mobile drawer upon screen resize to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            closeMobileSidebar();
        }
    });

    const userBtn = document.getElementById('userMenuBtn');
    const dropdown = document.getElementById('userDropdownMenu');
    const logoutModal = document.getElementById('logoutModal');
    const triggerLogout = document.getElementById('triggerLogoutBtn');
    const cancelLogout = document.getElementById('cancelLogoutBtn');

    if (userBtn && dropdown) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', function() {
            dropdown.classList.remove('show');
        });
    }

    if (triggerLogout && logoutModal) {
        triggerLogout.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.style.display = 'flex';
        });
    }

    if (cancelLogout && logoutModal) {
        cancelLogout.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });
    }
});
</script>
