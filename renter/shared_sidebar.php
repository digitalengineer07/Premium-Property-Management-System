<?php
// Renter Shared Sidebar (used on both Desktop and Mobile Drawer)
$current_page = basename($_SERVER['PHP_SELF']);

// Mid-session revocation check
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid_chk = (int)$_SESSION['user_id'];
    $chk_status = mysqli_query($conn, "SELECT status FROM users WHERE id = $uid_chk");
    if ($chk_status && $row_chk = mysqli_fetch_assoc($chk_status)) {
        if ($row_chk['status'] === 'moved_out') {
            session_destroy();
            echo "<script>alert('Your account has been archived. You are being logged out.'); window.location.href='../login.php';</script>";
            exit;
        }
    }
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class='bx bx-home-heart'></i>
        </div>
        <div class="sidebar-brand">
            <h2><?php echo htmlspecialchars(defined('HOUSE_NAME') ? HOUSE_NAME : 'Premium House'); ?></h2>
            <p>Resident Dashboard</p>
        </div>
    </div>
    
    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bx-grid-alt'></i>
            <span>Dashboard</span>
        </a>
        <a href="my-payments.php" class="nav-item <?php echo ($current_page == 'my-payments.php' || $current_page == 'payment-history.php') ? 'active' : ''; ?>">
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
            <i class='bx bx-user-circle'></i>
            <span>Profile Settings</span>
        </a>
    </nav>
    <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border, #E2E8F0);">
        <a href="../logout.php" class="nav-item" style=" color: #FF4B6B; ">
            <i class='bx bx-log-out'></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
