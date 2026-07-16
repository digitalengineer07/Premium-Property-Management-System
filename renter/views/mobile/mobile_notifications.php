<?php
// mobile_notifications.php
// A sleek dropdown notification panel for mobile view
?>
<style>
.m-notif-dropdown { position: fixed; top: 70px; right: 16px; width: 320px; max-width: calc(100vw - 32px); background: var(--bg-color, #ffffff); border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.15); z-index: 10000; display: flex; flex-direction: column; max-height: 70vh; border: 1px solid var(--border); opacity: 0; transform: translateY(-10px) scale(0.95); transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); pointer-events: none; }
.m-notif-dropdown.active { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.dark-theme .m-notif-dropdown { background: #1a1d24; border-color: rgba(255,255,255,0.1); box-shadow: 0 15px 50px rgba(0,0,0,0.5); }
.m-notif-dropdown::before { content: ''; position: absolute; top: -6px; right: 24px; width: 12px; height: 12px; background: inherit; transform: rotate(45deg); border-left: 1px solid var(--border); border-top: 1px solid var(--border); }

.m-notif-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
.m-notif-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }
.m-notif-badge-pill { font-size: 10px; background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 4px 8px; border-radius: 8px; font-weight: 700; margin-left: auto; }
.m-notif-body { padding: 12px; overflow-y: auto; flex: 1; }

.m-notif-empty { text-align: center; padding: 30px 0; color: var(--text-gray); }
.m-notif-empty i { font-size: 40px; opacity: 0.3; margin-bottom: 12px; }
.m-notif-empty p { margin: 0; font-size: 14px; font-weight: 600; }

.m-notif-item { display: flex; gap: 12px; padding: 12px; background: transparent; border-radius: 12px; margin-bottom: 8px; position: relative; overflow: hidden; transition: background 0.2s ease; }
.m-notif-item:active { background: rgba(0,0,0,0.03); }
.dark-theme .m-notif-item:active { background: rgba(255,255,255,0.05); }

.m-notif-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.m-notif-text { flex: 1; padding-right: 24px; }
.m-notif-text h4 { margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: var(--text-dark); }
.m-notif-text p { margin: 0 0 6px 0; font-size: 12px; color: var(--text-gray); line-height: 1.4; }
.m-notif-time { font-size: 10px; font-weight: 600; color: #a0aec0; }
.m-notif-dismiss { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--text-gray); font-size: 18px; cursor: pointer; opacity: 0.6; }
.m-notif-dismiss:active { background: rgba(239, 68, 68, 0.1); color: #EF4444; opacity: 1; }

/* Global overlay invisible just to catch outside clicks */
.m-notif-click-catcher { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9998; display: none; }
</style>

<div class="m-notif-click-catcher" id="mNotifClickCatcher" onclick="closeMobileNotif()"></div>
<div id="mobileNotifDropdown" class="m-notif-dropdown">
    <div class="m-notif-header">
        <h3><i class='bx bx-bell'></i> Notifications</h3>
        <?php if(isset($unread_count) && $unread_count > 0): ?>
            <span class="m-notif-badge-pill" id="mNotifCountPill"><?php echo $unread_count; ?> New</span>
        <?php endif; ?>
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
                    <button class="m-notif-dismiss" onclick="dismissNotificationMobile('<?php echo $notif['id']; ?>'); event.stopPropagation();">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function openMobileNotif() {
    let dropdown = document.getElementById('mobileNotifDropdown');
    let catcher = document.getElementById('mNotifClickCatcher');
    if(dropdown && catcher) {
        if(dropdown.classList.contains('active')) {
            closeMobileNotif();
        } else {
            dropdown.classList.add('active');
            catcher.style.display = 'block';
        }
    }
}
function closeMobileNotif() {
    let dropdown = document.getElementById('mobileNotifDropdown');
    let catcher = document.getElementById('mNotifClickCatcher');
    if(dropdown && catcher) {
        dropdown.classList.remove('active');
        catcher.style.display = 'none';
    }
}
function dismissNotificationMobile(id) {
    let item = document.getElementById('m-notif-' + id);
    if(item) {
        item.style.transition = 'all 0.3s ease';
        item.style.transform = 'translateX(10px)';
        item.style.opacity = '0';
        item.style.maxHeight = '0px';
        item.style.padding = '0px';
        item.style.margin = '0px';
        item.style.border = 'none';
        
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
                        <div class="m-notif-empty" style="animation: fadeIn 0.3s forwards;">
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
