<?php
// EXCLUSIVE MOBILE VIEW FOR DASHBOARD
?>
<!-- Mobile Top Header -->
<header class="mobile-only-header" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: transparent;">
    <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu' style="font-size: 28px; color: var(--text-dark); cursor: pointer;"></i>
    </div>
    <div class="m-header-brand" style="display: flex; align-items: center; gap: 10px;">
        <img src="../assets/img/logo.png" alt="Logo" style="width: 28px; height: 28px; border-radius: 8px;">
        <span style="font-size: 18px; font-weight: 800; color: var(--text-dark);"><?php echo htmlspecialchars(HOUSE_NAME); ?></span>
    </div>
    <div class="m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="icon-btn" id="themeToggleMobile" style="width: 38px; height: 38px; border-radius: 50%; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; flex-shrink: 0;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}"><i class='bx bx-moon'></i></div>
        <div class="icon-btn m-bell-icon" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';" style="position: relative; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text-dark); cursor: pointer;">
            <i class='bx bx-bell'></i>
            <?php if ($unread_count > 0): ?>
                <span class="m-notif-badge" style="position: absolute; top: 0px; right: 2px; width: 8px; height: 8px; background: #FF4B6B; border-radius: 50%; border: 2px solid var(--bg-main);"></span>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="mobile-only-dashboard animate-up" style="padding: 0 16px 90px 16px;">
    
    <!-- Greeting Banner -->
    <div class="m-greeting-banner" style="position: relative; background: var(--white); border-radius: 24px; padding: 24px 20px; margin-bottom: 24px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
        <!-- Soft purple curved background on the right -->
        <div style="position: absolute; right: -40px; top: -40px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(98,75,255,0.15) 0%, rgba(98,75,255,0) 70%); border-radius: 50%; pointer-events: none;"></div>
        
        <div class="m-greeting-text" style="position: relative; z-index: 2; max-width: 60%;">
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark); margin: 0 0 6px 0; letter-spacing: -0.5px;">Hello, <?php echo htmlspecialchars(trim($display_name ?? $user['name'] ?? 'User')); ?> 👋</h2>
            <p style="font-size: 13px; color: var(--text-gray); margin: 0 0 12px 0; line-height: 1.4;">Welcome back! You are assigned to</p>
            <div class="m-room-pill" style="display: inline-block; background: rgba(98,75,255,0.1); color: #624BFF; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px;">Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? '201'); ?></div>
        </div>
        <div class="m-greeting-img" style="position: absolute; right: -10px; bottom: 0; width: 170px; height: 100%; display: flex; align-items: flex-end; justify-content: flex-end; pointer-events: none; z-index: 1;">
            <img src="../assets/img/login_building.png" alt="Building" style="width: 100%; max-height: 130px; object-fit: contain; object-position: bottom right;">
        </div>
    </div>

    <!-- Payment Reminder Card -->
    <?php if ($show_banner || $total_due > 0): ?>
    <div class="m-reminder-card" style="background: linear-gradient(135deg, #FF6B6B 0%, #FF3D77 50%, #FF5E3A 100%); border-radius: 24px; padding: 24px; color: white; box-shadow: 0 10px 25px rgba(255, 61, 119, 0.3); margin-bottom: 32px; position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 16px;">
        <div style="display: flex; gap: 16px;">
            <div class="m-remind-icon" style="width: 48px; height: 48px; border-radius: 50%; background: white; color: #FF4B6B; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <i class='bx bx-bell'></i>
            </div>
            <div class="m-reminder-body">
                <h4 style="font-size: 16px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.3px;">Payment Reminder!</h4>
                <p style="font-size: 12px; opacity: 0.95; line-height: 1.5; margin: 0;">It's the <?php echo date('jS'); ?> of the month. Your bills for <strong><?php echo date('F Y'); ?></strong> are still pending. Please clear them to avoid service interruptions.</p>
            </div>
        </div>
        <div class="m-reminder-action" style="text-align: right;">
            <button onclick="openPaymentModal(<?php echo max(0, (float)$total_due); ?>, 'Total Outstanding Balance', 'total')" style="background: white; color: #FF4B6B; border: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                Pay Now <i class='bx bx-right-arrow-alt' style="font-size: 16px;"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Overview Section -->
    <div class="m-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="font-size: 18px; font-weight: 800; color: var(--text-dark); margin: 0;">Overview</h3>
        <a href="my-payments.php" style="font-size: 13px; font-weight: 700; color: #624BFF; text-decoration: none;">View All</a>
    </div>

    <div class="m-overview-list" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
        <!-- Total Outstanding Card -->
        <div class="m-overview-card" style="background: var(--white); border-radius: 20px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; position: relative; z-index: 2;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(255,75,107,0.1); color: #FF4B6B; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class='bx bx-credit-card-alt'></i>
                    </div>
                    <div>
                        <p style="font-size: 11px; color: var(--text-gray); font-weight: 600; margin: 0 0 4px 0;">Total Outstanding</p>
                        <h2 style="font-size: 22px; font-weight: 800; color: #FF4B6B; margin: 0; letter-spacing: -0.5px;">₹<?php echo number_format((float)$total_due, 2); ?></h2>
                    </div>
                </div>
                <?php if ($total_due > 0): ?>
                    <span style="background: rgba(255,75,107,0.1); color: #FF4B6B; font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 12px; display: flex; align-items: center; gap: 4px;"><i class='bx bx-time-five'></i> Payment Due</span>
                <?php else: ?>
                    <span style="background: rgba(16,185,129,0.1); color: #10B981; font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 12px; display: flex; align-items: center; gap: 4px;"><i class='bx bx-check-circle'></i> All Clear</span>
                <?php endif; ?>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-gray); font-weight: 600; position: relative; z-index: 2; margin-top: 16px;">
                <i class='bx bx-calendar'></i> Due Date: <?php echo date('27 M Y'); ?>
            </div>
            <!-- Beautiful Background Sparkline -->
            <svg style="position: absolute; right: 0; bottom: 0; width: 60%; height: 50%; opacity: 0.15; pointer-events: none; z-index: 1;" viewBox="0 0 100 40" preserveAspectRatio="none">
                <path d="M0 40 L0 30 Q10 35, 20 25 T40 20 T60 10 T80 20 T100 5 L100 40 Z" fill="#FF4B6B"></path>
                <path d="M0 30 Q10 35, 20 25 T40 20 T60 10 T80 20 T100 5" fill="none" stroke="#FF4B6B" stroke-width="2"></path>
            </svg>
        </div>

        <!-- Electricity Due Card -->
        <div class="m-overview-card" style="background: var(--white); border-radius: 20px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border);">
            <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; position: relative; z-index: 2;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(245,158,11,0.1); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class='bx bx-bolt-circle'></i>
                </div>
                <div>
                    <p style="font-size: 11px; color: var(--text-gray); font-weight: 600; margin: 0 0 4px 0;">Electricity Due</p>
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.5px;">₹<?php echo number_format((float)($elec_due ?? 0), 2); ?></h2>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-gray); font-weight: 600; position: relative; z-index: 2; margin-top: 16px;">
                <i class='bx bx-calendar'></i> Due Date: <?php echo date('10 M Y', strtotime('+1 month')); ?>
            </div>
            <!-- Background Sparkline -->
            <svg style="position: absolute; right: 0; bottom: 0; width: 60%; height: 50%; opacity: 0.15; pointer-events: none; z-index: 1;" viewBox="0 0 100 40" preserveAspectRatio="none">
                <path d="M0 40 L0 35 Q10 40, 20 30 T40 25 T60 30 T80 15 T100 10 L100 40 Z" fill="#F59E0B"></path>
                <path d="M0 35 Q10 40, 20 30 T40 25 T60 30 T80 15 T100 10" fill="none" stroke="#F59E0B" stroke-width="2"></path>
            </svg>
        </div>

        <!-- Rent Due Card -->
        <div class="m-overview-card" style="background: var(--white); border-radius: 20px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid var(--border);">
            <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; position: relative; z-index: 2;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(98,75,255,0.1); color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class='bx bx-home-alt'></i>
                </div>
                <div>
                    <p style="font-size: 11px; color: var(--text-gray); font-weight: 600; margin: 0 0 4px 0;">Rent Due</p>
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.5px;">₹<?php echo number_format((float)($rent_due ?? 0), 2); ?></h2>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-gray); font-weight: 600; position: relative; z-index: 2; margin-top: 16px;">
                <i class='bx bx-calendar'></i> Due Date: <?php echo date('07 M Y'); ?>
            </div>
            <!-- Background Sparkline -->
            <svg style="position: absolute; right: 0; bottom: 0; width: 60%; height: 50%; opacity: 0.15; pointer-events: none; z-index: 1;" viewBox="0 0 100 40" preserveAspectRatio="none">
                <path d="M0 40 L0 38 Q10 32, 20 35 T40 20 T60 25 T80 10 T100 5 L100 40 Z" fill="#624BFF"></path>
                <path d="M0 38 Q10 32, 20 35 T40 20 T60 25 T80 10 T100 5" fill="none" stroke="#624BFF" stroke-width="2"></path>
            </svg>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <h3 style="font-size: 18px; font-weight: 800; color: var(--text-dark); margin: 0 0 16px 0;">Quick Actions</h3>
    <div class="m-qa-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
        
        <a href="my-payments.php" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px 8px; text-decoration: none; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: rgba(98,75,255,1); color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(98,75,255,0.3);">
                <i class='bx bx-credit-card-alt'></i>
            </div>
            <h4 style="font-size: 12px; font-weight: 800; color: var(--text-dark); margin: 0 0 4px 0;">Pay Dues</h4>
            <p style="font-size: 9px; color: var(--text-gray); margin: 0; line-height: 1.3;">Make payments instantly</p>
        </a>

        <a href="payment-history.php" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px 8px; text-decoration: none; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #10B981; color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(16,185,129,0.3);">
                <i class='bx bx-receipt'></i>
            </div>
            <h4 style="font-size: 12px; font-weight: 800; color: var(--text-dark); margin: 0 0 4px 0;">Payment History</h4>
            <p style="font-size: 9px; color: var(--text-gray); margin: 0; line-height: 1.3;">View all transactions</p>
        </a>

        <a href="electricity-record.php" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px 8px; text-decoration: none; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #3B82F6; color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(59,130,246,0.3);">
                <i class='bx bx-shield-quarter'></i>
            </div>
            <h4 style="font-size: 12px; font-weight: 800; color: var(--text-dark); margin: 0 0 4px 0;">Electricity Record</h4>
            <p style="font-size: 9px; color: var(--text-gray); margin: 0; line-height: 1.3;">Meter readings and bills</p>
        </a>

        <a href="my-bills.php" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px 8px; text-decoration: none; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #F97316; color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(249,115,22,0.3);">
                <i class='bx bx-message-rounded-dots'></i>
            </div>
            <h4 style="font-size: 12px; font-weight: 800; color: var(--text-dark); margin: 0 0 4px 0;">My Bills</h4>
            <p style="font-size: 9px; color: var(--text-gray); margin: 0; line-height: 1.3;">View all your bills</p>
        </a>

        <a href="queries.php" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px 8px; text-decoration: none; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #EC4899; color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(236,72,153,0.3);">
                <i class='bx bx-chat'></i>
            </div>
            <h4 style="font-size: 12px; font-weight: 800; color: var(--text-dark); margin: 0 0 4px 0;">Raise Query</h4>
            <p style="font-size: 9px; color: var(--text-gray); margin: 0; line-height: 1.3;">Ask or report any issue</p>
        </a>

        <a href="documents.php" style="background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px 8px; text-decoration: none; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #14B8A6; color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(20,184,166,0.3);">
                <i class='bx bx-folder'></i>
            </div>
            <h4 style="font-size: 12px; font-weight: 800; color: var(--text-dark); margin: 0 0 4px 0;">Documents</h4>
            <p style="font-size: 9px; color: var(--text-gray); margin: 0; line-height: 1.3;">View important documents</p>
        </a>

    </div>
</div>
