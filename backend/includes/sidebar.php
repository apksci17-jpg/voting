<?php
// includes/sidebar.php — Shared Navigation Sidebar for Admin and Voter
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = getCurrentUser();
$activePage = $activePage ?? 'dashboard';
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-header" style="position: relative;">
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation sidebar" title="Close menu">
            &times;
        </button>
        <div class="sidebar-logo" style="width: 56px !important; height: 56px !important; min-width: 56px !important; min-height: 56px !important; max-width: 56px !important; max-height: 56px !important; background: transparent !important; border: none !important; box-shadow: none !important; margin: 0 auto 8px auto !important; display: flex !important; align-items: center !important; justify-content: center !important; overflow: visible !important; padding: 0 !important;">
            <img src="../assets/images/logo.png" alt="Tomorrow Vote Logo" class="sidebar-logo-img" onerror="this.src='../assets/uploads/logo.png'" style="width: 56px !important; height: 56px !important; max-width: 56px !important; max-height: 56px !important; object-fit: contain !important; display: block !important; filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.3)) !important;">
        </div>
        <div class="sidebar-app-name">TOMORROW VOTE</div>
        <div class="sidebar-app-sub">SECURE ONLINE ELECTION</div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo $isAdmin ? 'Admin' : htmlspecialchars($currentUser['full_name'] ?? 'Voter'); ?></div>
            <?php if (!$isAdmin): ?>
                <div class="user-role">Voter</div>
            <?php endif; ?>
        </div>
    </div>

    <ul class="nav-menu">
        <?php if ($isAdmin): ?>
            <li class="nav-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <a href="../admin/dashboard.php">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    Dashboard
                </a>
            </li>
            <li class="nav-item <?php echo $activePage === 'candidates' ? 'active' : ''; ?>">
                <a href="../admin/candidates.php">
                    <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    Candidate Management
                </a>
            </li>
            <li class="nav-item <?php echo $activePage === 'voting_control' ? 'active' : ''; ?>">
                <a href="../admin/voting_control.php">
                    <svg viewBox="0 0 24 24"><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>
                    Voting Control
                </a>
            </li>
            <li class="nav-item <?php echo $activePage === 'results' ? 'active' : ''; ?>">
                <a href="../admin/results.php">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                    Results
                </a>
            </li>
        <?php else: ?>
            <li class="nav-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <a href="../voter/dashboard.php">
                    <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z"/></svg>
                    Dashboard
                </a>
            </li>
            <li class="nav-item <?php echo $activePage === 'candidates' ? 'active' : ''; ?>">
                <a href="../voter/candidates.php">
                    <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    View Candidate
                </a>
            </li>
            <li class="nav-item <?php echo $activePage === 'ballot' ? 'active' : ''; ?>">
                <a href="../voter/ballot.php">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    Cast Vote
                </a>
            </li>
            <li class="nav-item <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                <a href="../voter/profile.php">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    Profile
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>
