<?php
// EXCLUSIVE MOBILE VIEW FOR NOTICES.PHP

// Get latest important notice for the bottom banner
$latest_important = null;
foreach ($notices as $n) {
    if ($n['category'] === 'Important') {
        $latest_important = $n;
        break;
    }
}
?>
<style>
    .m-notices-container { background: var(--bg-main); padding-bottom: 90px; font-family: 'Outfit', sans-serif; min-height: 100vh; }
    
    /* Header */
    .m-header-custom { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: transparent; position: sticky; top: 0; z-index: 100; }
    
    /* KPI Grid */
    .m-kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 16px; margin-bottom: 24px; }
    .m-kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
    .m-kpi-top { display: flex; align-items: center; gap: 12px; }
    .m-kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-kpi-title { font-size: 11px; font-weight: 600; color: var(--text-gray); margin: 0; }
    .m-kpi-value { font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.5px; }
    .m-kpi-pill { font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 12px; display: inline-block; white-space: nowrap; margin-top: auto; align-self: flex-start; }

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

    /* Notice List */
    .m-notice-list { display: flex; flex-direction: column; gap: 1px; background: var(--border); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin-bottom: 16px; }
    .m-notice-item { background: var(--white); padding: 16px; display: flex; gap: 12px; position: relative; }
    .m-notice-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-notice-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
    .m-notice-header { display: flex; justify-content: space-between; align-items: flex-start; }
    .m-notice-title-wrap { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .m-notice-title { font-size: 13px; font-weight: 700; color: var(--text-dark); margin: 0; }
    .m-notice-badge { font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px; display: inline-block; line-height: 1; }
    .m-notice-desc { font-size: 11px; font-weight: 500; color: var(--text-gray); margin: 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: all 0.2s ease; }
    .m-notice-item.expanded .m-notice-desc { -webkit-line-clamp: unset; display: block; }
    .m-notice-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; flex-shrink: 0; }
    .m-notice-date { font-size: 10px; color: var(--text-dark); font-weight: 600; }
    .m-notice-time { font-size: 10px; color: var(--text-gray); font-weight: 500; }
    .m-new-tag { background: rgba(98, 75, 255, 0.1); color: #624BFF; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin-top: 4px; }

    /* Pagination */
    .m-pagination-info { text-align: center; font-size: 11px; color: var(--text-gray); font-weight: 500; margin-bottom: 12px; }
    .m-pagination { display: flex; justify-content: center; gap: 8px; margin-bottom: 24px; }
    .m-page-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; color: var(--text-dark); background: var(--white); text-decoration: none; }
    .m-page-btn.active { background: #624BFF; color: white; border-color: #624BFF; }
    
    /* Important Banner */
    .m-important-banner { background: rgba(255, 75, 107, 0.03); border: 1px solid rgba(255, 75, 107, 0.1); border-radius: 16px; margin: 0 16px 16px 16px; padding: 16px; position: relative; }
    .m-important-tag { font-size: 11px; font-weight: 700; color: #FF4B6B; display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
    .m-important-tag i { font-size: 14px; }
    .m-important-content { display: flex; align-items: center; gap: 12px; }
    .m-important-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(98, 75, 255, 0.1); color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-important-text { flex: 1; }
    .m-important-title { font-size: 13px; font-weight: 700; color: var(--text-dark); margin: 0 0 4px 0; }
    .m-important-meta { font-size: 11px; font-weight: 500; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 6px; }
    
    /* Floating Notification CTA */
    .m-notify-cta { background: var(--white); border-radius: 12px; margin: 0 16px 16px 16px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(98, 75, 255, 0.2); box-shadow: 0 4px 15px rgba(98, 75, 255, 0.05); }
    .m-notify-text { display: flex; align-items: center; gap: 12px; font-size: 12px; font-weight: 600; color: var(--text-dark); }
    .m-notify-text i { font-size: 18px; color: #624BFF; }
    .m-notify-btn { font-size: 12px; font-weight: 700; color: #624BFF; border: 1px solid rgba(98, 75, 255, 0.2); border-radius: 6px; padding: 6px 12px; background: transparent; cursor: pointer; }

</style>

<div class="m-notices-container animate-up">
    <!-- Header -->
    <header class="m-header-custom">
        <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }" style="cursor: pointer;">
            <i class='bx bx-menu-alt-left' style="font-size: 28px; color: var(--text-dark);"></i>
        </div>
        <div class="m-header-brand" style="flex: 1; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; line-height: 1.2; margin-left: 16px;">
            <span style="font-size: 18px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.3px;">Notices</span>
            <span style="font-size: 12px; font-weight: 500; color: var(--text-gray);">Stay informed about important updates</span>
        </div>
        <div class="m-header-right" style="display: flex; align-items: center; gap: 12px;">
            <div class="icon-btn" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';" style="position: relative; font-size: 22px; color: var(--text-dark); cursor: pointer;">
                <i class='bx bx-bell'></i>
                <?php if (isset($unread_count) && $unread_count > 0): ?>
                    <span style="position: absolute; top: -2px; right: -2px; width: 14px; height: 14px; background: #FF4B6B; color: white; border-radius: 50%; font-size: 9px; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 2px solid var(--bg-main);"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </div>
            <div class="user-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: #624BFF; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                <?php echo strtoupper(substr($display_name ?? 'U', 0, 2)); ?>
            </div>
        </div>
    </header>

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
        <div class="m-tab active">All</div>
        <div class="m-tab">Important</div>
        <div class="m-tab">General</div>
        <div class="m-tab">Maintenance</div>
        <div class="m-tab">Events</div>
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
        <?php foreach(array_slice($notices, 0, 7) as $idx => $n): ?>
            <?php 
                $icon = 'bx-info-circle'; $icon_bg = 'rgba(98, 75, 255, 0.1)'; $icon_color = '#624BFF';
                $badge = 'General'; $badge_bg = 'rgba(16, 185, 129, 0.1)'; $badge_color = '#10B981';
                
                if (stripos($n['title'], 'Maintenance') !== false || stripos($n['category'], 'Maintenance') !== false) {
                    if (stripos($n['title'], 'Water') !== false) {
                        $icon = 'bx-calendar-event';
                        $icon_bg = 'rgba(98, 75, 255, 0.1)';
                        $icon_color = '#624BFF';
                    } else {
                        $icon = 'bx-bolt-circle';
                        $icon_bg = 'rgba(245, 158, 11, 0.1)';
                        $icon_color = '#F59E0B';
                    }
                    $badge = 'Maintenance';
                    $badge_bg = 'rgba(245, 158, 11, 0.1)';
                    $badge_color = '#D97706';
                }
                if ($n['category'] === 'Important' || stripos($n['title'], 'Rules') !== false) {
                    $icon = 'bx-error-circle';
                    $icon_bg = 'rgba(255, 75, 107, 0.1)';
                    $icon_color = '#FF4B6B';
                    $badge = 'Important';
                    $badge_bg = 'rgba(255, 75, 107, 0.1)';
                    $badge_color = '#FF4B6B';
                    
                    if (stripos($n['title'], 'Maintenance') !== false) {
                        $icon = 'bxs-megaphone';
                        $icon_bg = 'rgba(98, 75, 255, 0.1)';
                        $icon_color = '#624BFF';
                    }
                }
                if (stripos($n['title'], 'Event') !== false) {
                    $icon = 'bx-gift';
                    $icon_bg = 'rgba(59, 130, 246, 0.1)';
                    $icon_color = '#3B82F6';
                    $badge = 'Events';
                    $badge_bg = 'rgba(59, 130, 246, 0.1)';
                    $badge_color = '#3B82F6';
                }
                if (stripos($n['title'], 'Society') !== false || stripos($n['title'], 'Garbage') !== false) {
                    $icon = stripos($n['title'], 'Garbage') !== false ? 'bx-trash' : 'bx-building-house';
                    $icon_bg = stripos($n['title'], 'Garbage') !== false ? 'rgba(245, 158, 11, 0.1)' : 'rgba(16, 185, 129, 0.1)';
                    $icon_color = stripos($n['title'], 'Garbage') !== false ? '#F59E0B' : '#10B981';
                    $badge = 'General';
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
    </div>

    <!-- Pagination -->
    <?php if ($total_notices > 0): ?>
    <div class="m-pagination-info">Showing <?php echo min(($page - 1) * 7 + 1, $total_notices); ?> to <?php echo min($page * 7, $total_notices); ?> of <?php echo $total_notices; ?> notice<?php echo $total_notices == 1 ? '' : 's'; ?></div>
    <?php if ($total_notices > 7): ?>
    <div class="m-pagination">
        <a href="?page=<?php echo max(1, $page - 1); ?>" class="m-page-btn"><i class='bx bx-chevron-left'></i></a>
        <a href="#" class="m-page-btn active"><?php echo $page; ?></a>
        <?php if ($page * 7 < $total_notices): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="m-page-btn"><?php echo $page + 1; ?></a>
        <?php endif; ?>
        <a href="?page=<?php echo min(ceil($total_notices / 7), $page + 1); ?>" class="m-page-btn"><i class='bx bx-chevron-right'></i></a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Important Notice Widget (Removed to prevent duplication) -->

    <!-- Enable Notifications CTA -->
    <div class="m-notify-cta">
        <div class="m-notify-text">
            <i class='bx bx-bell'></i> Don't miss any important updates!
        </div>
        <button class="m-notify-btn">Enable Notifications</button>
        <i class='bx bx-x' style="color: var(--text-gray); font-size: 18px; cursor: pointer;"></i>
    </div>
</div>