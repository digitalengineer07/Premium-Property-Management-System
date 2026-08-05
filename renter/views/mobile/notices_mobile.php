<?php
// EXCLUSIVE MOBILE VIEW FOR NOTICES.PHP

// Determine current category filter
$current_category = strtolower($_GET['category'] ?? 'all');
$filtered_notices = [];

foreach ($notices as $n) {
    // Determine the badge/category of the notice
    $badge = 'General';
    if (stripos($n['title'], 'Maintenance') !== false || stripos($n['category'], 'Maintenance') !== false) {
        $badge = 'Maintenance';
    }
    if ($n['category'] === 'Important' || stripos($n['title'], 'Rules') !== false) {
        $badge = 'Important';
    }
    if (stripos($n['title'], 'Event') !== false) {
        $badge = 'Events';
    }
    
    if ($current_category === 'all' || strtolower($badge) === $current_category) {
        $n['computed_badge'] = $badge;
        $filtered_notices[] = $n;
    }
}

$total_filtered = count($filtered_notices);
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 7;
$paged_notices = array_slice($filtered_notices, ($page - 1) * $per_page, $per_page);

// Get latest important notice for the bottom banner (from ALL notices, not just filtered)
$latest_important = null;
foreach ($notices as $n) {
    if ($n['category'] === 'Important') {
        $latest_important = $n;
        break;
    }
}
?>
<style>
    .m-notices-container { background: var(--bg-main); padding-bottom: 85px; font-family: 'Outfit', sans-serif; min-height: 100vh; }
    
    /* Header */
    .m-header-custom { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: transparent; position: sticky; top: 0; z-index: 100; }
    
    .m-kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 16px; margin-bottom: 24px; }
    .m-kpi-card { 
        background: var(--white); 
        border: 1px solid var(--border); 
        border-radius: 20px; 
        padding: 16px; 
        display: flex; 
        flex-direction: column; 
        gap: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .m-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
    
    body.dark-theme .m-kpi-card {
        background: linear-gradient(145deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        border: 1px solid rgba(255,255,255,0.05);
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        backdrop-filter: blur(10px);
    }
    body.dark-theme .m-kpi-card:hover {
        border-color: rgba(255,255,255,0.1);
        box-shadow: 0 10px 40px rgba(0,0,0,0.25);
    }

    .m-kpi-top { display: flex; align-items: center; gap: 12px; }
    .m-kpi-icon { width: 36px; height: 36px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-kpi-title { font-size: 11px; font-weight: 600; color: var(--text-gray); margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
    .m-kpi-value { font-size: 22px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.5px; }
    .m-kpi-pill { font-size: 9px; font-weight: 800; padding: 4px 8px; border-radius: 8px; display: inline-block; white-space: nowrap; margin-top: auto; align-self: flex-start; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Tabs */
    .m-tabs-scroll { display: flex; gap: 24px; padding: 0 16px; border-bottom: 1px solid var(--border); margin-bottom: 16px; overflow-x: auto; scrollbar-width: none; }
    .m-tabs-scroll::-webkit-scrollbar { display: none; }
    .m-tab { font-size: 13px; font-weight: 600; color: var(--text-gray); padding-bottom: 8px; cursor: pointer; white-space: nowrap; }
    .m-tab.active { font-weight: 700; color: #624BFF; border-bottom: 2px solid #624BFF; }

    /* Filters */
    .m-filters { display: flex; justify-content: space-between; align-items: center; padding: 0 16px; margin-bottom: 16px; }
    .m-select-wrap { position: relative; }
    .m-select-wrap select { appearance: none; background: var(--white); border: 1px solid var(--border); border-radius: 8px; padding: 8px 32px 8px 12px; font-size: 12px; font-weight: 600; color: var(--text-dark); font-family: 'Outfit', sans-serif; outline: none; }
    .m-select-wrap::after { content: '▼'; font-family: sans-serif; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-gray); pointer-events: none; font-size: 10px; }
    .m-filter-btn { display: flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1px solid rgba(98, 75, 255, 0.2); border-radius: 8px; background: transparent; font-size: 12px; font-weight: 600; color: #624BFF; }

    /* Premium Notice List */
    .m-notice-list { display: flex; flex-direction: column; gap: 14px; padding: 0 16px; margin-bottom: 24px; }
    .m-notice-item { 
        background: var(--white); 
        padding: 18px; 
        display: flex; 
        gap: 14px; 
        position: relative; 
        border-radius: 20px; 
        border: 1px solid var(--border); 
        box-shadow: 0 8px 24px rgba(0,0,0,0.04); 
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        cursor: pointer; 
        overflow: hidden;
    }
    .m-notice-item:active { transform: scale(0.98); }
    
    /* Premium Dark Theme Overrides */
    body.dark-theme .m-notice-item {
        background: linear-gradient(145deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);
        border: 1px solid rgba(255,255,255,0.06);
        box-shadow: 0 10px 40px rgba(0,0,0,0.25);
        backdrop-filter: blur(12px);
    }
    body.dark-theme .m-notice-item.expanded {
        border-color: rgba(98, 75, 255, 0.4);
        box-shadow: 0 10px 40px rgba(98, 75, 255, 0.15);
        background: linear-gradient(145deg, rgba(98,75,255,0.08) 0%, rgba(255,255,255,0.01) 100%);
    }
    
    .m-notice-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; transition: transform 0.3s ease; }
    .m-notice-item:hover .m-notice-icon { transform: scale(1.1) rotate(-5deg); }
    
    .m-notice-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .m-notice-header { display: flex; justify-content: space-between; align-items: flex-start; }
    .m-notice-title-wrap { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .m-notice-title { font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.2px; }
    .m-notice-badge { font-size: 9px; font-weight: 800; padding: 4px 8px; border-radius: 6px; display: inline-block; line-height: 1; text-transform: uppercase; letter-spacing: 0.5px; }
    .m-notice-desc { font-size: 12px; font-weight: 500; color: var(--text-gray); margin: 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: all 0.3s ease; opacity: 0.9; }
    .m-notice-item.expanded .m-notice-desc { -webkit-line-clamp: unset; display: block; opacity: 1; margin-top: 4px; }
    .m-notice-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; flex-shrink: 0; }
    .m-notice-date { font-size: 10px; color: var(--text-dark); font-weight: 600; }
    .m-notice-time { font-size: 10px; color: var(--text-gray); font-weight: 500; }
    .m-new-tag { background: rgba(98, 75, 255, 0.1); color: #624BFF; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin-top: 4px; }

    /* Pagination */
    .m-pagination-info { text-align: center; font-size: 11px; color: var(--text-gray); font-weight: 500; margin-bottom: 12px; }
    .m-pagination { display: flex; justify-content: center; gap: 2px; margin-bottom: 24px; }
    .m-page-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; color: var(--text-dark); background: var(--white); text-decoration: none; }
    .m-page-btn.active { background: #624BFF; color: white; border-color: #624BFF; }
    
    /* Important Banner */
    .m-important-banner { background: rgba(255, 75, 107, 0.03); border: 1px solid rgba(255, 75, 107, 0.1); border-radius: 16px; margin: 0 16px 16px 16px; padding: 16px; position: relative; }
    .m-important-tag { font-size: 11px; font-weight: 700; color: #FF4B6B; display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
    .m-important-tag i { font-size: 13px; }
    .m-important-content { display: flex; align-items: center; gap: 12px; }
    .m-important-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(98, 75, 255, 0.1); color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-important-text { flex: 1; }
    .m-important-title { font-size: 13px; font-weight: 700; color: var(--text-dark); margin: 0 0 4px 0; }
    .m-important-meta { font-size: 11px; font-weight: 500; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 6px; }
    
    /* Floating Notification CTA */
    .m-notify-cta { background: var(--white); border-radius: 12px; margin: 0 16px 16px 16px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(98, 75, 255, 0.2); box-shadow: 0 4px 15px rgba(98, 75, 255, 0.05); }
    .m-notify-text { display: flex; align-items: center; gap: 12px; font-size: 12px; font-weight: 600; color: var(--text-dark); }
    .m-notify-text i { font-size: 18px; color: #624BFF; }
    .m-notify-btn { font-size: 12px; font-weight: 700; color: #624BFF; border: 1px solid rgba(98, 75, 255, 0.2); border-radius: 6px; padding: 6px 12px; background: transparent; cursor: pointer; }

</style>

<div class="m-notices-container animate-up">
    <!-- Header -->
    <header class="premium-header-pill">
    <div class="m-header-left-group" style="display: flex; align-items: center; gap: 12px;">
        <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
            <i class='bx bx-menu-alt-left'></i>
        </div>
        <h1 class="m-page-title" style="font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px; display: flex; align-items: center; gap: 2px;">
            <i class='bx bx-bell' style="font-size: 22px; color: #ffffff; margin-top: 2px;"></i>
            Notices
        </h1>
    </div>
    
    <div class="m-header-module m-header-right" style="display: flex; align-items: center; gap: 2px;">
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>
        <div class="header-icon-btn" onclick="openMobileNotif()">
            <i class='bx bx-bell'></i>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="m-notif-badge"></span>
            <?php endif; ?>
        </div>
        <a href="#" class="header-profile-btn" onclick="openMobileProfile(); return false;" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">
            <?php if (!empty($user['profile_pic']) && file_exists("../" . $user['profile_pic'])): ?>
                <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: #624BFF; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; font-weight: 800;">
                    <?php echo strtoupper(substr(trim($user['name'] ?? ($user['username'] ?? 'U')), 0, 1)); ?>
                </div>
            <?php endif; ?>
        </a>
    </div>
</header>
<div style="height: 90px; width: 100%; display: block; flex-shrink: 0;"></div>


    <!-- KPI Grid -->
    <div class="m-kpi-grid">
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">
                    <i class='bx bx-megaphone'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Total Notices</h4>
                    <h2 class="m-kpi-value"><?php echo $total_notices; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">All time</span>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class='bx bx-file-blank'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">New Notices</h4>
                    <h2 class="m-kpi-value"><?php echo $new_notices; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Unread</span>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                    <i class='bx bx-calendar-event'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">This Month</h4>
                    <h2 class="m-kpi-value"><?php echo $this_month_notices; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(245, 158, 11, 0.1); color: #D97706;"><?php echo $current_month_name; ?></span>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
                    <i class='bx bx-map-pin'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Important</h4>
                    <h2 class="m-kpi-value"><?php echo $important_notices; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">High Priority</span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="m-tabs-scroll">
        <a href="?category=all" class="m-tab <?php echo $current_category === 'all' ? 'active' : ''; ?>" style="text-decoration: none;">All</a>
        <a href="?category=important" class="m-tab <?php echo $current_category === 'important' ? 'active' : ''; ?>" style="text-decoration: none;">Important</a>
        <a href="?category=general" class="m-tab <?php echo $current_category === 'general' ? 'active' : ''; ?>" style="text-decoration: none;">General</a>
        <a href="?category=maintenance" class="m-tab <?php echo $current_category === 'maintenance' ? 'active' : ''; ?>" style="text-decoration: none;">Maintenance</a>
        <a href="?category=events" class="m-tab <?php echo $current_category === 'events' ? 'active' : ''; ?>" style="text-decoration: none;">Events</a>
    </div>

    <!-- Filters -->
    <div class="m-filters">
        <div class="m-select-wrap">
            <select>
                <option>All Categories</option>
            </select>
        </div>
        <button class="m-filter-btn"><i class='bx bx-filter-alt'></i> Filter</button>
    </div>

    <!-- Notices List -->
    <div class="m-notice-list">
        <?php if ($total_filtered === 0): ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                <i class='bx bx-ghost' style="font-size: 48px; margin-bottom: 12px; opacity: 0.5;"></i>
                <p>No notices found in this category.</p>
            </div>
        <?php else: ?>
            <?php foreach($paged_notices as $idx => $n): ?>
                <?php 
                    $icon = 'bx-info-circle'; $icon_bg = 'rgba(98, 75, 255, 0.1)'; $icon_color = '#624BFF';
                    $badge = $n['computed_badge']; $badge_bg = 'rgba(16, 185, 129, 0.1)'; $badge_color = '#10B981';
                    
                    if ($badge === 'Maintenance') {
                        if (stripos($n['title'], 'Water') !== false) {
                            $icon = 'bx-calendar-event';
                            $icon_bg = 'rgba(98, 75, 255, 0.1)';
                            $icon_color = '#624BFF';
                        } else {
                            $icon = 'bx-bolt-circle';
                            $icon_bg = 'rgba(245, 158, 11, 0.1)';
                            $icon_color = '#F59E0B';
                        }
                        $badge_bg = 'rgba(245, 158, 11, 0.1)';
                        $badge_color = '#D97706';
                    }
                    if ($badge === 'Important') {
                        $icon = 'bx-error-circle';
                        $icon_bg = 'rgba(255, 75, 107, 0.1)';
                        $icon_color = '#FF4B6B';
                        $badge_bg = 'rgba(255, 75, 107, 0.1)';
                        $badge_color = '#FF4B6B';
                        
                        if (stripos($n['title'], 'Maintenance') !== false) {
                            $icon = 'bxs-megaphone';
                            $icon_bg = 'rgba(98, 75, 255, 0.1)';
                            $icon_color = '#624BFF';
                        }
                    }
                    if ($badge === 'Events') {
                        $icon = 'bx-gift';
                        $icon_bg = 'rgba(59, 130, 246, 0.1)';
                        $icon_color = '#3B82F6';
                        $badge_bg = 'rgba(59, 130, 246, 0.1)';
                        $badge_color = '#3B82F6';
                    }
                    if ($badge === 'General') {
                        $icon = stripos($n['title'], 'Garbage') !== false ? 'bx-trash' : 'bx-building-house';
                        $icon_bg = stripos($n['title'], 'Garbage') !== false ? 'rgba(245, 158, 11, 0.1)' : 'rgba(16, 185, 129, 0.1)';
                        $icon_color = stripos($n['title'], 'Garbage') !== false ? '#F59E0B' : '#10B981';
                        $badge_bg = 'rgba(16, 185, 129, 0.1)';
                        $badge_color = '#10B981';
                    }
                ?>
            <div class="m-notice-item" style="cursor: pointer;" onclick="this.classList.toggle('expanded'); const b = this.querySelector('.m-new-tag'); if(b) b.style.display='none';">
                <div class="m-notice-icon" style="background: <?php echo $icon_bg; ?>; color: <?php echo $icon_color; ?>;">
                    <i class='bx <?php echo $icon; ?>'></i>
                </div>
                <div class="m-notice-content">
                    <div class="m-notice-header">
                        <div class="m-notice-title-wrap">
                            <h4 class="m-notice-title"><?php echo htmlspecialchars($n['title']); ?></h4>
                            <span class="m-notice-badge" style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;"><?php echo $badge; ?></span>
                        </div>
                        <div class="m-notice-meta">
                            <span class="m-notice-date"><?php echo htmlspecialchars($n['date']); ?></span>
                            <span class="m-notice-time"><?php echo htmlspecialchars($n['time']); ?></span>
                            <?php if ($idx < 2): ?>
                                <span class="m-new-tag">New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="m-notice-desc"><?php echo htmlspecialchars($n['full_desc']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_filtered > 0): ?>
    <div class="m-pagination-info">Showing <?php echo min(($page - 1) * 7 + 1, $total_filtered); ?> to <?php echo min($page * 7, $total_filtered); ?> of <?php echo $total_filtered; ?> notice<?php echo $total_filtered == 1 ? '' : 's'; ?></div>
    <?php if ($total_filtered > 7): ?>
    <div class="m-pagination">
        <a href="?category=<?php echo urlencode($current_category); ?>&page=<?php echo max(1, $page - 1); ?>" class="m-page-btn"><i class='bx bx-chevron-left'></i></a>
        <a href="#" class="m-page-btn active"><?php echo $page; ?></a>
        <?php if ($page * 7 < $total_filtered): ?>
            <a href="?category=<?php echo urlencode($current_category); ?>&page=<?php echo $page + 1; ?>" class="m-page-btn"><?php echo $page + 1; ?></a>
        <?php endif; ?>
        <a href="?category=<?php echo urlencode($current_category); ?>&page=<?php echo min(ceil($total_filtered / 7), $page + 1); ?>" class="m-page-btn"><i class='bx bx-chevron-right'></i></a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Important Notice Widget (Removed to prevent duplication) -->

    <!-- Enable Notifications CTA (Removed per user request) -->
</div>
<?php include 'mobile_notifications.php'; ?>
