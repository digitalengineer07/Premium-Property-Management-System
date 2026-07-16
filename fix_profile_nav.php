<?php
$content = file_get_contents('renter/profile.php');

// Remove my previous hack
$content = preg_replace('/<style>\s*\/\* Explicit override to guarantee visibility on mobile view \*\/\s*@media screen and \(max-width: 768px\) \{\s*#profile-bottom-nav \{\s*display: flex !important;\s*visibility: visible !important;\s*opacity: 1 !important;\s*pointer-events: auto !important;\s*z-index: 99999 !important;\s*\}\s*\}\s*<\/style>\s*/', '', $content);

// The correct CSS and HTML to append
$navBlock = <<<EOT
<style>
/* Universal Mobile Bottom Navigation Bar CSS */
@media screen and (max-width: 768px) {
    .mobile-bottom-nav { display: flex !important; }
    .main-content {
        padding-bottom: calc(86px + env(safe-area-inset-bottom)) !important;
    }
}

.mobile-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(68px + env(safe-area-inset-bottom));
    padding-bottom: env(safe-area-inset-bottom);
    background: var(--white, #FFFFFF);
    border-top: 1px solid var(--border, #F1F5F9);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
    z-index: 9999;
    justify-content: space-around;
    align-items: center;
    padding: 0 8px;
}
.dark-theme .mobile-bottom-nav {
    background: #111827;
    border-top-color: #1E293B;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
}
.mb-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: var(--text-gray, #64748B);
    font-size: 11px;
    font-weight: 600;
    gap: 4px;
    transition: all 0.2s ease;
    padding: 6px 12px;
    border-radius: 12px;
}
.mb-nav-item i { font-size: 22px; transition: transform 0.2s ease, color 0.2s ease; }
.mb-nav-item.active {
    color: var(--primary-purple, #624BFF);
}
.mb-nav-item.active i {
    transform: translateY(-2px);
    color: var(--primary-purple, #624BFF);
}
.mb-nav-center {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #624BFF, #8B5CF6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 8px 20px rgba(98, 75, 255, 0.4);
    margin-top: -24px;
    margin-bottom: 4px;
    border: 4px solid var(--white, #FFFFFF);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.dark-theme .mb-nav-center {
    border-color: #111827;
}
</style>

<!-- Universal Mobile Bottom Navigation Bar (Visible only on mobile <= 768px) -->
<nav class="mobile-bottom-nav">
    <a href="dashboard.php" class="mb-nav-item "><i class='bx bx-home'></i><span>Dashboard</span></a>
    <a href="my-payments.php" class="mb-nav-item "><i class='bx bx-credit-card'></i><span>Payments</span></a>
    <div class="mb-nav-center" onclick="if(typeof openPaymentModal === 'function') openPaymentModal(0, 'Quick Payment', 'general'); else window.location.href='my-payments.php';">
        <i class='bx bx-plus'></i>
    </div>
    <a href="payment-history.php" class="mb-nav-item "><i class='bx bx-history'></i><span>History</span></a>
    <a href="profile.php" class="mb-nav-item active"><i class='bx bx-user'></i><span>Profile</span></a>
</nav>
EOT;

// Replace the existing nav with the new nav block
$content = preg_replace('/<!-- Universal Mobile Bottom Navigation Bar.*?<\/nav>/s', $navBlock, $content);

file_put_contents('renter/profile.php', $content);
echo "Fixed profile bottom nav successfully!\n";
?>
