<?php
// Unified Mobile Sidebar Drawer
// This file should be included at the bottom of all mobile views.
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside id="unified-mobile-sidebar" class="mobile-sidebar-drawer">
    <div class="m-sidebar-header">
        <div class="m-sidebar-logo">
            <i class='bx bx-home-heart'></i>
        </div>
        <div class="m-sidebar-brand">
            <h2><?php echo htmlspecialchars(HOUSE_NAME); ?></h2>
            <p>Resident Dashboard</p>
        </div>
    </div>
    
    <nav class="m-nav-menu">
        <a href="dashboard.php" class="m-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bx-grid-alt'></i>
            <span>Dashboard</span>
        </a>
        <a href="my-payments.php" class="m-nav-item <?php echo $current_page == 'my-payments.php' ? 'active' : ''; ?>">
            <i class='bx bx-wallet'></i>
            <span>My Payments</span>
        </a>
        <a href="payment-approvals.php" class="m-nav-item <?php echo $current_page == 'payment-approvals.php' ? 'active' : ''; ?>">
            <i class='bx bx-check-shield'></i>
            <span>Approvals</span>
        </a>
        <a href="electricity-record.php" class="m-nav-item <?php echo $current_page == 'electricity-record.php' ? 'active' : ''; ?>">
            <i class='bx bx-bolt-circle'></i>
            <span>Electricity Record</span>
        </a>
        <a href="my-bills.php" class="m-nav-item <?php echo $current_page == 'my-bills.php' ? 'active' : ''; ?>">
            <i class='bx bx-receipt'></i>
            <span>My Bills</span>
        </a>
        <a href="queries.php" class="m-nav-item <?php echo $current_page == 'queries.php' ? 'active' : ''; ?>">
            <i class='bx bx-message-square-dots'></i>
            <span>Raise Query</span>
        </a>
        <a href="notices.php" class="m-nav-item <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
            <i class='bx bx-bell'></i>
            <span>Notices</span>
        </a>
        <a href="documents.php" class="m-nav-item <?php echo $current_page == 'documents.php' ? 'active' : ''; ?>">
            <i class='bx bx-folder'></i>
            <span>Documents</span>
        </a>
        <a href="profile.php" class="m-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class='bx bx-user'></i>
            <span>Profile Settings</span>
        </a>
    </nav>

    <!-- Stay Updated Widget shifted slightly UP as requested -->
    <div class="m-go-mobile-widget" style="background: linear-gradient(145deg, rgba(74,111,255,0.05) 0%, rgba(74,111,255,0.02) 100%); border: 1px solid rgba(74,111,255,0.1); border-radius: 16px; padding: 12px; margin: 4px 16px 12px 16px; text-align: center; position: relative; overflow: hidden; margin-top: auto;">
        <div style="position: absolute; top: -10px; right: -10px; width: 40px; height: 40px; background: rgba(74,111,255,0.1); border-radius: 50%; filter: blur(10px);"></div>
        <i class='bx bx-bell' style="font-size: 20px; color: var(--primary); margin-bottom: 6px;"></i>
        <h4 style="font-size: 13px; font-weight: 700; color: var(--text-dark); margin-bottom: 2px;">Stay Updated</h4>
        <p style="font-size: 11px; color: var(--text-gray); margin-bottom: 8px; line-height: 1.4;">Turn on notifications for rent updates.</p>
        <button style="background: var(--primary); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; width: 100%; cursor: pointer; transition: all 0.2s ease;">Enable Now</button>
    </div>

    <!-- Fix Logout Button (shifted UP slightly as requested by user) -->
    <div style="padding: 10px 16px 12px 16px; border-top: 1px solid var(--border, #E2E8F0); margin-bottom: 12px;">
        <a href="../logout.php" class="m-nav-item" style="color: #FF4B6B;">
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
</script>
