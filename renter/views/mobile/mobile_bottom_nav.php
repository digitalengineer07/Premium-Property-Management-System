<?php
// Unified Mobile Bottom Navigation Bar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Universal Mobile Bottom Navigation Bar (Visible only on mobile <= 768px) -->
<nav class="mobile-bottom-nav">
    <a href="dashboard.php" class="mb-nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
        <i class='bx bx-home'></i><span>Dashboard</span>
    </a>
    <a href="my-payments.php" class="mb-nav-item <?php echo $current_page == 'my-payments.php' ? 'active' : ''; ?>">
        <i class='bx bx-credit-card'></i><span>Payments</span>
    </a>
    <div class="mb-nav-center" onclick="if(typeof openPaymentModal === 'function') openPaymentModal(0, 'Quick Payment', 'general'); else window.location.href='my-payments.php';">
        <i class='bx bx-plus'></i>
    </div>
    <a href="payment-history.php" class="mb-nav-item <?php echo $current_page == 'payment-history.php' ? 'active' : ''; ?>">
        <i class='bx bx-history'></i><span>History</span>
    </a>
    <a href="profile.php" class="mb-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
        <i class='bx bx-user'></i><span>Profile</span>
    </a>
</nav>
