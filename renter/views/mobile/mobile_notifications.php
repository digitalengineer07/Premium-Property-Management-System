<?php
// mobile_notifications.php
// A sleek bottom sheet notification panel for mobile view
?>
<style>
.m-notif-sheet { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; display: flex; flex-direction: column; justify-content: flex-end; }
.m-notif-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); opacity: 0; animation: fadeIn 0.3s forwards; }
.m-notif-content { position: relative; background: var(--bg-color, #f8f9fa); width: 100%; border-radius: 24px 24px 0 0; padding-bottom: max(24px, env(safe-area-inset-bottom)); display: flex; flex-direction: column; max-height: 85vh; transform: translateY(100%); animation: slideUpSheet 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; box-shadow: 0 -10px 40px rgba(0,0,0,0.1); }
.dark-theme .m-notif-content { background: #1a1d24; }
.m-notif-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
.m-notif-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }
.m-notif-badge-pill { font-size: 11px; background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 4px 10px; border-radius: 12px; font-weight: 700; margin-left: auto; margin-right: 12px; }
.m-notif-close { background: var(--border); border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px; cursor: pointer; }
.m-notif-body { padding: 16px 24px; overflow-y: auto; flex: 1; }
.m-notif-empty { text-align: center; padding: 40px 0; color: var(--text-gray); }
.m-notif-empty i { font-size: 48px; opacity: 0.3; margin-bottom: 16px; }
.m-notif-empty p { margin: 0; font-size: 15px; font-weight: 600; }
.m-notif-item { display: flex; gap: 16px; padding: 16px; background: var(--white); border-radius: 16px; margin-bottom: 12px; border: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
.m-notif-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #624BFF; border-radius: 4px 0 0 4px; }
.m-notif-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
.m-notif-text { flex: 1; padding-right: 28px; }
.m-notif-text h4 { margin: 0 0 4px 0; font-size: 15px; font-weight: 700; color: var(--text-dark); }
.m-notif-text p { margin: 0 0 8px 0; font-size: 13px; color: var(--text-gray); line-height: 1.4; }
.m-notif-time { font-size: 11px; font-weight: 600; color: #a0aec0; }
.m-notif-dismiss { position: absolute; right: 16px; top: 16px; background: rgba(239, 68, 68, 0.1); border: none; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #EF4444; font-size: 16px; cursor: pointer; }
@keyframes slideUpSheet { to { transform: translateY(0); } }
@keyframes slideDownSheet { to { transform: translateY(100%); } }
@keyframes fadeIn { to { opacity: 1; } }
@keyframes fadeOut { to { opacity: 0; } }
</style>

<div id="mobileNotifSheet" class="m-notif-sheet" style="display: none;">
    <div class="m-notif-overlay" onclick="closeMobileNotif()"></div>
    <div class="m-notif-content">
        <div class="m-notif-header">
            <h3><i class='bx bx-bell'></i> Notifications</h3>
            <?php if(isset($unread_count) && $unread_count > 0): ?>
                <span class="m-notif-badge-pill" id="mNotifCountPill"><?php echo $unread_count; ?> New</span>
            <?php endif; ?>
            <button class="m-notif-close" onclick="closeMobileNotif()"><i class='bx bx-x'></i></button>
        </div>
        <div class="m-notif-body">
            <?php if (empty($unread_notifications)): ?>
                <div class="m-notif-empty">
                    <i class='bx bx-bell-off'></i>
                    <p>You're all caught up!</p>
                </div>
            <?php else: ?>
                <?php foreach ($unread_notifications as $notif): ?>
                    <div class="m-notif-item" id="m-notif-<?php echo $notif['id']; ?>">
                        <div class="m-notif-icon" style="background: <?php echo $notif['color']; ?>15; color: <?php echo $notif['color']; ?>;">
                            <i class='bx <?php echo $notif['icon']; ?>'></i>
                        </div>
                        <div class="m-notif-text">
                            <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                            <p><?php echo htmlspecialchars($notif['message']); ?></p>
                            <span class="m-notif-time"><?php echo date('M d', strtotime($notif['time'])); ?></span>
                        </div>
                        <button class="m-notif-dismiss" onclick="dismissNotificationMobile('<?php echo $notif['id']; ?>')">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function openMobileNotif() {
    let sheet = document.getElementById('mobileNotifSheet');
    if(sheet) {
        sheet.style.display = 'flex';
        let content = sheet.querySelector('.m-notif-content');
        let overlay = sheet.querySelector('.m-notif-overlay');
        content.style.animation = 'slideUpSheet 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards';
        overlay.style.animation = 'fadeIn 0.3s forwards';
    }
}
function closeMobileNotif() {
    let sheet = document.getElementById('mobileNotifSheet');
    if(sheet) {
        let content = sheet.querySelector('.m-notif-content');
        let overlay = sheet.querySelector('.m-notif-overlay');
        content.style.animation = 'slideDownSheet 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards';
        overlay.style.animation = 'fadeOut 0.3s forwards';
        setTimeout(() => { sheet.style.display = 'none'; }, 300);
    }
}
function dismissNotificationMobile(id) {
    let item = document.getElementById('m-notif-' + id);
    if(item) {
        item.style.transition = 'all 0.3s ease';
        item.style.transform = 'translateX(100%)';
        item.style.opacity = '0';
        setTimeout(() => { 
            item.remove(); 
            // Decrease count
            let pill = document.getElementById('mNotifCountPill');
            let bellBadge = document.querySelector('.m-notif-badge');
            if (pill) {
                let current = parseInt(pill.textContent);
                if (!isNaN(current) && current > 1) {
                    pill.textContent = (current - 1) + ' New';
                } else {
                    pill.remove();
                    if (bellBadge) bellBadge.remove();
                    
                    let body = document.querySelector('.m-notif-body');
                    if(body) {
                        body.innerHTML = `
                        <div class="m-notif-empty animate-up">
                            <i class='bx bx-bell-off'></i>
                            <p>You're all caught up!</p>
                        </div>
                        `;
                    }
                }
            }
        }, 300);
    }
    
    // Attempt to call desktop dismiss if it exists (so both are synced)
    if(typeof dismissNotification === 'function') {
        dismissNotification(id, null); 
    } else {
        // Fallback AJAX if the desktop function isn't around
        fetch('api/dismiss_notification.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        }).catch(err => console.log(err));
    }
}
</script>
