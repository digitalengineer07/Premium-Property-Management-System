<?php
// Unified Mobile Sidebar Drawer
// This file should be included at the bottom of all mobile views.
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside id="unified-mobile-sidebar" class="mobile-sidebar-drawer">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class='bx bx-home-heart'></i>
        </div>
        <div class="sidebar-brand">
            <h2><?php echo htmlspecialchars(HOUSE_NAME); ?></h2>
            <p>Resident Dashboard</p>
        </div>
    </div>
    
    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bx-grid-alt'></i>
            <span>Dashboard</span>
        </a>
        <a href="my-payments.php" class="nav-item <?php echo $current_page == 'my-payments.php' ? 'active' : ''; ?>">
            <i class='bx bx-wallet'></i>
            <span>My Payments</span>
        </a>
        <a href="payment-approvals.php" class="nav-item <?php echo $current_page == 'payment-approvals.php' ? 'active' : ''; ?>">
            <i class='bx bx-check-shield'></i>
            <span>Approvals</span>
        </a>
        <a href="electricity-record.php" class="nav-item <?php echo $current_page == 'electricity-record.php' ? 'active' : ''; ?>">
            <i class='bx bx-bolt-circle'></i>
            <span>Electricity Record</span>
        </a>
        <a href="my-bills.php" class="nav-item <?php echo $current_page == 'my-bills.php' ? 'active' : ''; ?>">
            <i class='bx bx-receipt'></i>
            <span>My Bills</span>
        </a>
        <a href="queries.php" class="nav-item <?php echo $current_page == 'queries.php' ? 'active' : ''; ?>">
            <i class='bx bx-message-square-dots'></i>
            <span>Raise Query</span>
        </a>
        <a href="notices.php" class="nav-item <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
            <i class='bx bx-bell'></i>
            <span>Notices</span>
        </a>
        <a href="documents.php" class="nav-item <?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
            <i class='bx bx-folder'></i>
            <span>Documents</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class='bx bx-user'></i>
            <span>Profile Settings</span>
        </a>
    </nav>
    <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border, #E2E8F0);">
        <a href="../logout.php" class="nav-item" style="color: #FF4B6B;">
            <i class='bx bx-log-out'></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<!-- Overlay for mobile sidebar -->
<div id="unified-mobile-overlay" class="mobile-sidebar-overlay" onclick="closeMobileSidebar(event)"></div>

<script>
// Override the inline onclick handlers existing in mobile headers
function openMobileSidebar(e) {
    if(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const msb = document.getElementById('unified-mobile-sidebar');
    const overlay = document.getElementById('unified-mobile-overlay');
    if(msb && overlay) {
        msb.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

function closeMobileSidebar(e) {
    if(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const msb = document.getElementById('unified-mobile-sidebar');
    const overlay = document.getElementById('unified-mobile-overlay');
    if(msb && overlay) {
        msb.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// Close sidebar on swipe left
let touchStartX = 0;
let touchEndX = 0;
const drawer = document.getElementById('unified-mobile-sidebar');
if (drawer) {
    drawer.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
    }, {passive: true});

    drawer.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        if (touchStartX - touchEndX > 50) {
            closeMobileSidebar();
        }
    }, {passive: true});
}
</script>
