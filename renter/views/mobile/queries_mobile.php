<?php
// EXCLUSIVE MOBILE VIEW FOR QUERIES.PHP
?>
<style>
    .m-queries-container { background: var(--bg-main); padding-bottom: 90px; font-family: 'Outfit', sans-serif; min-height: 100vh; }
    .m-header-custom { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: transparent; position: sticky; top: 0; z-index: 100; }
    
    .m-kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 16px; margin-bottom: 24px; }
    .m-kpi-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
    .m-kpi-top { display: flex; align-items: center; gap: 12px; }
    .m-kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-kpi-title { font-size: 11px; font-weight: 600; color: var(--text-gray); margin: 0; }
    .m-kpi-value { font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.5px; }
    .m-kpi-pill { font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 12px; display: inline-block; white-space: nowrap; margin-top: auto; align-self: center; }

    .m-tabs { display: flex; gap: 24px; padding: 0 16px; border-bottom: 1px solid var(--border); margin-bottom: 16px; }
    .m-tab { font-size: 14px; font-weight: 600; color: var(--text-gray); padding-bottom: 12px; cursor: pointer; white-space: nowrap; flex: 1; text-align: center; }
    .m-tab.active { color: #624BFF; border-bottom: 2px solid #624BFF; }

    .m-form-container { padding: 0 16px; display: none; }
    .m-form-container.active { display: block; animation: fadeIn 0.3s ease; }
    
    .m-form-group { margin-bottom: 12px; }
    .m-form-label { display: block; font-size: 12px; font-weight: 600; color: var(--text-dark); margin-bottom: 6px; }
    .m-form-control { width: 100%; background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; font-size: 13px; color: var(--text-dark); outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif; }
    .m-form-control:focus { border-color: #624BFF; box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.1); }
    .m-select-wrapper { position: relative; }
    .m-select-wrapper::after { content: '\ea3a'; font-family: boxicons; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--text-gray); pointer-events: none; }
    .m-select { appearance: none; padding-right: 36px; }
    
    .m-upload-box { background: rgba(98, 75, 255, 0.03); border: 1px dashed rgba(98, 75, 255, 0.3); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; }
    .m-upload-box i { font-size: 22px; color: #624BFF; margin-bottom: 6px; }
    .m-upload-box h5 { font-size: 13px; font-weight: 600; color: var(--text-dark); margin: 0 0 4px 0; }
    .m-upload-box p { font-size: 11px; color: var(--text-gray); margin: 0; }
    
    .m-btn-submit { width: 100%; background: #624BFF; color: white; border: none; border-radius: 10px; padding: 14px; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; box-shadow: 0 4px 15px rgba(98, 75, 255, 0.2); cursor: pointer; }

    .m-list-container { padding: 0 16px; display: none; }
    .m-list-container.active { display: block; animation: fadeIn 0.3s ease; }
    
    .m-query-item { background: var(--white); border: 1px solid var(--border); border-radius: 16px; margin-bottom: 12px; overflow: hidden; }
    .m-query-header { padding: 16px; display: flex; gap: 12px; align-items: flex-start; cursor: pointer; }
    .m-query-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .m-query-info { flex: 1; min-width: 0; }
    .m-query-info h4 { font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .m-query-info p { font-size: 12px; color: var(--text-gray); margin: 0 0 8px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .m-query-meta { display: flex; justify-content: space-between; align-items: center; }
    .m-query-date { font-size: 11px; font-weight: 600; color: var(--text-gray); }
    .m-query-status { font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 10px; border-top-right-radius: 15px; border-bottom-left-radius: 12px; border-top-left-radius: 0; border-bottom-right-radius: 0; }
    .m-query-details { padding: 0 16px 16px 16px; display: none; border-top: 1px solid var(--border); margin-top: 8px; padding-top: 16px; }
    .m-query-details.open { display: block; animation: fadeIn 0.3s ease; }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="m-queries-container">
    <header class="m-header-custom" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }" style="cursor: pointer; flex-shrink: 0;">
                <i class='bx bx-menu' style="font-size: 28px; color: var(--text-dark);"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text-dark);">Queries</h2>
                <p style="margin: 0; font-size: 12px; font-weight: 500; color: var(--text-gray);">Help & Support</p>
            </div>
        </div>
        <div class="m-header-right" style="display: flex; align-items: center; gap: 8px;">
            <div class="icon-btn" id="themeToggle" style="width: 38px; height: 38px; border-radius: 50%; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; flex-shrink: 0;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}"><i class='bx bx-moon'></i></div>
            
            <div class="icon-btn" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';" style="position: relative; width: 38px; height: 38px; border-radius: 50%; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text-dark); cursor: pointer; flex-shrink: 0;">
                <i class='bx bx-bell'></i>
                <?php if (isset($unread_count) && $unread_count > 0): ?>
                    <span style="position: absolute; top: 0px; right: 2px; width: 8px; height: 8px; background: #FF4B6B; border-radius: 50%; border: 2px solid var(--bg-main);"></span>
                <?php endif; ?>
            </div>
            
            <div onclick="const pd = document.getElementById('profileDropdown'); if(pd) pd.style.display = pd.style.display === 'none' ? 'block' : 'none'; event.stopPropagation();" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; background: #E0E7FF; color: #624BFF; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 1px solid var(--border); flex-shrink: 0;">
                <?php 
                    $real_pic = '';
                    if (isset($user['profile_pic']) && !empty($user['profile_pic'])) $real_pic = $user['profile_pic'];
                    elseif (isset($usr['profile_pic']) && !empty($usr['profile_pic'])) $real_pic = $usr['profile_pic'];
                    elseif (isset($profile_pic) && $profile_pic !== 'assets/img/default-avatar.png' && !empty($profile_pic)) $real_pic = $profile_pic;
                    
                    $d_name = $display_name ?? $user['name'] ?? $usr['name'] ?? 'User';
                ?>
                <?php if (!empty($real_pic)): ?>
                    <img src="../<?php echo htmlspecialchars($real_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 700; font-size: 14px;"><?php echo strtoupper(substr(trim($d_name), 0, 2)); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if(!empty($success)): ?>
        <div style="margin: 0 16px 16px 16px; padding: 12px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 12px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-check-circle'></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div style="margin: 0 16px 16px 16px; padding: 12px; background: rgba(239, 68, 68, 0.1); color: #EF4444; border-radius: 12px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-error-circle'></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- KPI Grid -->
    <div class="m-kpi-grid">
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">
                    <i class='bx bx-message-rounded-dots'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Total Queries</h4>
                    <h2 class="m-kpi-value"><?php echo $total_queries; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(98, 75, 255, 0.1); color: #624BFF;">All time</span>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                    <i class='bx bx-time'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Open Queries</h4>
                    <h2 class="m-kpi-value"><?php echo $open_queries; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">Awaiting response</span>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                    <i class='bx bx-check-circle'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Resolved</h4>
                    <h2 class="m-kpi-value"><?php echo $resolved_queries; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Successfully fixed</span>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-top">
                <div class="m-kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
                    <i class='bx bx-x-circle'></i>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <h4 class="m-kpi-title">Closed</h4>
                    <h2 class="m-kpi-value"><?php echo $closed_queries; ?></h2>
                </div>
            </div>
            <span class="m-kpi-pill" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">Closed by you</span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="m-tabs">
        <div class="m-tab active" onclick="switchQueryTab('new', this)">New Query</div>
        <div class="m-tab" onclick="switchQueryTab('history', this)">History</div>
    </div>

    <!-- New Query Form -->
    <div id="tab-new" class="m-form-container active">
        <form method="POST">
            <div class="m-form-group">
                <label class="m-form-label">Query Category</label>
                <div class="m-select-wrapper">
                    <select name="category" class="m-form-control m-select" required>
                        <option value="">Select Category</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Electricity">Electricity</option>
                        <option value="Housekeeping">Housekeeping</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="General">General</option>
                    </select>
                </div>
            </div>
            <div class="m-form-group">
                <label class="m-form-label">Subject</label>
                <input type="text" name="subject" class="m-form-control" placeholder="Enter a short subject" required>
            </div>
            <div class="m-form-group">
                <label class="m-form-label">Description</label>
                <textarea name="message" class="m-form-control" rows="4" placeholder="Describe your issue or request in detail..." required style="resize: none;"></textarea>
            </div>
            <div class="m-form-group">
                <label class="m-form-label">Upload Image (Optional)</label>
                <div class="m-upload-box" onclick="document.getElementById('m-fileUpload').click();">
                    <i class='bx bx-upload'></i>
                    <h5>Click to upload</h5>
                    <p>PNG, JPG up to 5MB</p>
                </div>
                <input type="file" id="m-fileUpload" style="display: none;" accept="image/png, image/jpeg, image/jpg">
            </div>
            <button type="submit" name="submit_query" class="m-btn-submit">
                <i class='bx bx-send'></i> Submit Query
            </button>
        </form>
    </div>

    <!-- Query History -->
    <div id="tab-history" class="m-list-container">
        <?php if(empty($queries)): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <i class='bx bx-message-square-dots' style="font-size: 48px; color: var(--border); margin-bottom: 16px;"></i>
                <h4 style="margin: 0 0 8px 0; color: var(--text-dark);">No Queries Yet</h4>
                <p style="margin: 0; color: var(--text-gray); font-size: 13px;">Your submitted queries will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach($queries as $q): 
                $cat = strtolower($q['category']);
                if (strpos($cat, 'plumbing') !== false) {
                    $icon = 'bx-water'; $bg = 'rgba(245, 158, 11, 0.1)'; $col = '#F59E0B';
                } elseif (strpos($cat, 'elect') !== false) {
                    $icon = 'bx-bolt-circle'; $bg = 'rgba(59, 130, 246, 0.1)'; $col = '#3B82F6';
                } elseif (strpos($cat, 'housekeep') !== false || strpos($cat, 'clean') !== false) {
                    $icon = 'bx-brush'; $bg = 'rgba(16, 185, 129, 0.1)'; $col = '#10B981';
                } elseif (strpos($cat, 'maintain') !== false || strpos($cat, 'maintenance') !== false) {
                    $icon = 'bx-wrench'; $bg = 'rgba(98, 75, 255, 0.1)'; $col = '#624BFF';
                } elseif (strpos($cat, 'parking') !== false) {
                    $icon = 'bx-car'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                } else {
                    $icon = 'bx-category'; $bg = 'rgba(139, 92, 246, 0.1)'; $col = '#8B5CF6';
                }

                $st = strtolower($q['ui_status']);
                if ($st == 'open') {
                    $s_bg = 'rgba(245, 158, 11, 0.1)'; $s_col = '#F59E0B';
                } elseif ($st == 'in progress') {
                    $s_bg = 'rgba(59, 130, 246, 0.1)'; $s_col = '#3B82F6';
                } elseif ($st == 'resolved') {
                    $s_bg = 'rgba(16, 185, 129, 0.1)'; $s_col = '#10B981';
                } else {
                    $s_bg = 'rgba(239, 68, 68, 0.1)'; $s_col = '#EF4444';
                }
            ?>
            <div class="m-query-item">
                <div class="m-query-header" onclick="toggleQueryDetails(this)">
                    <div class="m-query-icon" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>;">
                        <i class='bx <?php echo $icon; ?>'></i>
                    </div>
                    <div class="m-query-info">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px;">
                            <h4><?php echo htmlspecialchars($q['subject']); ?></h4>
                            <span class="m-query-status" style="background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>;"><?php echo htmlspecialchars($q['ui_status']); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($q['category']); ?></p>
                        <div class="m-query-meta">
                            <span class="m-query-date"><?php echo date('d M Y', strtotime($q['created_at'])); ?></span>
                            <i class='bx bx-chevron-down' style="color: var(--text-gray); font-size: 18px; transition: 0.3s;"></i>
                        </div>
                    </div>
                </div>
                <div class="m-query-details">
                    <p style="font-size: 13px; color: var(--text-dark); margin: 0 0 12px 0; line-height: 1.5;"><strong>Message:</strong><br><span style="color: var(--text-gray);"><?php echo nl2br(htmlspecialchars($q['message'])); ?></span></p>
                    
                    <?php if(!empty($q['admin_remark'])): ?>
                        <div style="padding: 12px; background: rgba(98, 75, 255, 0.05); border-left: 3px solid #624BFF; border-radius: 8px; margin-bottom: 12px;">
                            <p style="font-size: 12px; color: #624BFF; margin: 0; line-height: 1.5;"><strong>Admin Reply:</strong><br><?php echo nl2br(htmlspecialchars($q['admin_remark'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <a href="?action=delete&id=<?php echo $q['id']; ?>" onclick="return confirm('Delete this query?');" style="display: inline-flex; align-items: center; gap: 6px; color: #EF4444; background: rgba(239, 68, 68, 0.05); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none;">
                        <i class='bx bx-trash'></i> Delete Query
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function switchQueryTab(tabId, el) {
    document.querySelectorAll('.m-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    
    document.querySelectorAll('.m-form-container, .m-list-container').forEach(c => {
        c.classList.remove('active');
    });
    document.getElementById('tab-' + tabId).classList.add('active');
}

function toggleQueryDetails(headerEl) {
    const details = headerEl.nextElementSibling;
    const icon = headerEl.querySelector('.bx-chevron-down');
    
    if (details.classList.contains('open')) {
        details.classList.remove('open');
        icon.style.transform = 'rotate(0deg)';
    } else {
        details.classList.add('open');
        icon.style.transform = 'rotate(180deg)';
    }
}
</script>