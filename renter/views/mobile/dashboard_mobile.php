<?php
// EXCLUSIVE MOBILE VIEW FOR DASHBOARD.PHP
?>
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->
<header class="mobile-only-header">
    <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu'></i>
    </div>
    <div class="m-header-brand">
        <img src="../assets/img/logo.png" alt="Logo">
        <span>Madhav Kunj</span>
    </div>
    <div class="m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="icon-btn" id="themeToggle" style="width: 38px; height: 38px; border-radius: 50%; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; flex-shrink: 0;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}"><i class='bx bx-moon'></i></div>
        
        <div class="icon-btn m-bell-icon" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';">
            <i class='bx bx-bell'></i>
            <?php if ($unread_count > 0): ?>
                <span class="m-notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- EXCLUSIVE MOBILE-ONLY DASHBOARD CONTENT (<= 768px) -->
<div class="mobile-only-dashboard animate-up">
    <!-- Greeting Banner -->
    <div class="m-greeting-banner">
        <div class="m-greeting-text">
            <h2>Hello, <?php echo htmlspecialchars(trim($display_name ?? $user['name'] ?? 'User')); ?> Jii 👋</h2>
            <p>Welcome back! You are assigned to</p>
            <div class="m-room-pill">Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? '201'); ?></div>
        </div>
        <div class="m-greeting-img">
            <img src="../assets/img/login_building.png" alt="Building">
        </div>
    </div>

    <!-- Payment Reminder Card -->
    <?php if ($show_banner || $total_due > 0): ?>
    <div class="m-reminder-card">
        <div class="m-reminder-left">
            <div class="m-remind-icon">
                <i class='bx bxs-bell-ring'></i>
            </div>
        </div>
        <div class="m-reminder-body">
            <h4>Payment Reminder!</h4>
            <p>It's the <?php echo date('jS'); ?> of the month. Your bills for <strong><?php echo date('F Y'); ?></strong> are still pending. Please clear them to avoid service interruptions.</p>
        </div>
        <div class="m-reminder-action">
            <button onclick="openPaymentModal(<?php echo max(0, (float)$total_due); ?>, 'Total Outstanding Balance', 'total')" class="m-pay-btn">
                Pay Now <i class='bx bx-right-arrow-alt'></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Overview Section -->
    <div class="m-section-header">
        <h3>Overview</h3>
        <a href="my-bills.php">View All</a>
    </div>

    <div class="m-overview-list">
        <!-- Total Outstanding Card -->
        <div class="m-overview-card">
            <div class="m-oc-top">
                <div class="m-oc-icon red"><i class='bx bx-credit-card-alt'></i></div>
                <div class="m-oc-title">
                    <span>Total Outstanding</span>
                    <h4>₹<?php echo number_format((float)$total_due, 2); ?></h4>
                </div>
                <div class="m-oc-badge red"><i class='bx bx-error-circle'></i> Payment Due</div>
            </div>
            <div class="m-oc-bottom">
                <div class="m-oc-date"><i class='bx bx-calendar'></i> Due Date: <?php echo date('d M Y'); ?></div>
                <div class="m-oc-sparkline">
                    <svg viewBox="0 0 100 30" width="80" height="24">
                        <path d="M0 25 Q15 28, 25 20 T50 15 T75 8 T100 4" fill="none" stroke="#FF4B6B" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M0 25 Q15 28, 25 20 T50 15 T75 8 T100 4 L100 30 L0 30 Z" fill="rgba(255, 75, 107, 0.15)"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Electricity Due Card -->
        <div class="m-overview-card">
            <div class="m-oc-top">
                <div class="m-oc-icon yellow"><i class='bx bx-bolt-circle'></i></div>
                <div class="m-oc-title">
                    <span>Electricity Due</span>
                    <h4>₹<?php echo number_format((float)($electricity_due ?? 8.00), 2); ?></h4>
                </div>
            </div>
            <div class="m-oc-bottom">
                <div class="m-oc-date"><i class='bx bx-calendar'></i> Due Date: <?php echo date('t M Y'); ?></div>
                <div class="m-oc-sparkline">
                    <svg viewBox="0 0 100 30" width="80" height="24">
                        <path d="M0 26 Q20 22, 35 20 T65 14 T100 5" fill="none" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M0 26 Q20 22, 35 20 T65 14 T100 5 L100 30 L0 30 Z" fill="rgba(245, 158, 11, 0.15)"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Rent Due Card -->
        <div class="m-overview-card">
            <div class="m-oc-top">
                <div class="m-oc-icon purple"><i class='bx bx-home-alt'></i></div>
                <div class="m-oc-title">
                    <span>Rent Due</span>
                    <h4>₹<?php echo number_format((float)($rent_due ?? 8000.00), 2); ?></h4>
                </div>
            </div>
            <div class="m-oc-bottom">
                <div class="m-oc-date"><i class='bx bx-calendar'></i> Due Date: 05 <?php echo date('M Y', strtotime('+1 month')); ?></div>
                <div class="m-oc-sparkline">
                    <svg viewBox="0 0 100 30" width="80" height="24">
                        <path d="M0 27 Q25 25, 45 18 T80 12 T100 6" fill="none" stroke="#624BFF" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M0 27 Q25 25, 45 18 T80 12 T100 6 L100 30 L0 30 Z" fill="rgba(98, 75, 255, 0.15)"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="m-section-header" style="margin-top: 24px;">
        <h3>Quick Actions</h3>
    </div>

    <div class="m-quick-actions-grid">
        <div class="m-qa-card" onclick="openPaymentModal(<?php echo max(0, (float)$total_due); ?>, 'Total Outstanding Balance', 'total')">
            <div class="m-qa-icon purple"><i class='bx bx-credit-card'></i></div>
            <h4>Pay Dues</h4>
            <p>Make payments instantly</p>
        </div>
        <a href="payment-history.php" class="m-qa-card">
            <div class="m-qa-icon green"><i class='bx bx-file'></i></div>
            <h4>Payment History</h4>
            <p>View all transactions</p>
        </a>
        <a href="electricity-record.php" class="m-qa-card">
            <div class="m-qa-icon blue"><i class='bx bx-shield-quarter'></i></div>
            <h4>Electricity Record</h4>
            <p>Meter readings and bills</p>
        </a>
        <a href="my-bills.php" class="m-qa-card">
            <div class="m-qa-icon orange"><i class='bx bx-message-square-dots'></i></div>
            <h4>My Bills</h4>
            <p>View all your bills</p>
        </a>
        <a href="queries.php" class="m-qa-card">
            <div class="m-qa-icon pink"><i class='bx bx-support'></i></div>
            <h4>Raise Query</h4>
            <p>Ask or report any issue</p>
        </a>
        <a href="documents.php" class="m-qa-card">
            <div class="m-qa-icon teal"><i class='bx bx-folder'></i></div>
            <h4>Documents</h4>
            <p>View important documents</p>
        </a>
    </div>
</div>