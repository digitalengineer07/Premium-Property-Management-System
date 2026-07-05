<?php
// EXCLUSIVE MOBILE VIEW FOR DOCUMENTS.PHP
?>
<style>
    .m-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: var(--white, #fff);
        position: sticky;
        top: 0;
        z-index: 50;
        border-bottom: 1px solid var(--border, #F1F5F9);
    }
    .m-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .m-header-menu {
        font-size: 28px;
        color: var(--text-dark, #0F172A);
        cursor: pointer;
    }
    .m-header-title h1 {
        font-size: 18px;
        font-weight: 800;
        margin: 0 0 2px 0;
        color: var(--text-dark, #0F172A);
    }
    .m-header-title p {
        font-size: 12px;
        color: var(--text-gray, #64748B);
        margin: 0;
    }
    .m-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .m-header-bell {
        position: relative;
        font-size: 24px;
        color: var(--text-dark, #0F172A);
        cursor: pointer;
    }
    .m-header-bell .badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #EF4444;
        color: white;
        font-size: 10px;
        font-weight: 700;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--white, #fff);
    }
    .m-header-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary-purple, #624BFF);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
    }

    .m-container {
        padding: 16px;
        padding-bottom: 80px;
        background: var(--bg-main, #FAFBFC);
    }

    /* KPI Grid */
    .m-kpi-scroll {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .m-kpi-card {
        background: var(--white, #fff);
        border: 1px solid var(--border, #F1F5F9);
        border-radius: 16px;
        padding: 16px 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .m-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 12px;
    }
    .m-kpi-title {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-gray, #64748B);
        margin-bottom: 4px;
    }
    .m-kpi-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-dark, #0F172A);
        margin-bottom: 4px;
    }
    .m-kpi-subtitle {
        font-size: 11px;
        color: var(--text-gray, #64748B);
        font-weight: 500;
    }

    /* Filters */
    .m-filters {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
    }
    .m-filters-row {
        display: flex;
        gap: 10px;
    }
    .m-filter-select {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid var(--border, #F1F5F9);
        border-radius: 10px;
        background: var(--white, #fff);
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark, #0F172A);
        appearance: none;
        background-image: url('data:image/svg+xml;utf8,<svg fill="%2364748B" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 16px;
    }
    .m-filter-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 16px;
        border: 1px solid rgba(98, 75, 255, 0.15);
        border-radius: 10px;
        background: var(--white, #fff);
        color: var(--primary-purple, #624BFF);
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }
    .m-search {
        position: relative;
    }
    .m-search input {
        width: 100%;
        padding: 12px 16px 12px 42px;
        border: 1px solid var(--border, #F1F5F9);
        border-radius: 10px;
        background: var(--white, #fff);
        font-size: 13px;
        color: var(--text-dark, #0F172A);
        box-sizing: border-box;
    }
    .m-search i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-gray, #64748B);
        font-size: 18px;
    }

    /* Document List */
    .m-doc-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 16px;
    }
    .m-doc-item {
        background: var(--white, #fff);
        border: 1px solid var(--border, #F1F5F9);
        border-radius: 14px;
        padding: 20px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        position: relative;
        overflow: hidden;
    }
    .m-doc-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .m-doc-info {
        flex: 1;
        min-width: 0;
    }
    .m-doc-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-dark, #0F172A);
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 20px; /* prevent text overlap with badge */
    }
    .m-doc-subtitle {
        font-size: 12px;
        color: var(--text-gray, #64748B);
        margin-bottom: 6px;
    }
    .m-doc-status-date {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .m-status-badge {
        position: absolute;
        top: 0;
        right: 0;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 12px;
        border-radius: 0 0 0 12px;
    }
    .m-doc-date {
        font-size: 11px;
        color: var(--text-gray, #64748B);
        font-weight: 600;
    }
    .m-doc-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
    .m-action-btn {
        width: 32px;
        height: 32px;
        border: 1px solid var(--border, #F1F5F9);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-purple, #624BFF);
        font-size: 18px;
        background: var(--white, #fff);
        text-decoration: none;
    }
    .m-view-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        color: var(--primary-purple, #624BFF);
        font-size: 13px;
        font-weight: 700;
        padding: 8px;
        margin-bottom: 24px;
        cursor: pointer;
    }

    /* Upload Area */
    .m-section-card {
        background: var(--white, #fff);
        border: 1px solid var(--border, #F1F5F9);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.03);
    }
    .m-section-title {
        font-size: 15px;
        font-weight: 800;
        color: var(--text-dark, #0F172A);
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .m-upload-zone {
        border: 2px dashed rgba(98, 75, 255, 0.2);
        border-radius: 14px;
        padding: 24px 20px;
        text-align: center;
        background: rgba(98, 75, 255, 0.02);
    }
    .m-upload-zone i {
        font-size: 32px;
        color: var(--primary-purple, #624BFF);
        background: rgba(98, 75, 255, 0.1);
        padding: 12px;
        border-radius: 50%;
        margin-bottom: 12px;
    }
    .m-upload-zone h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-dark, #0F172A);
        margin: 0 0 6px 0;
    }
    .m-upload-zone p {
        font-size: 12px;
        color: var(--text-gray, #64748B);
        margin: 0 0 16px 0;
        font-weight: 500;
    }
    .m-btn-primary {
        background: var(--primary-purple, #624BFF);
        color: white;
        border: none;
        padding: 12px;
        width: 100%;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
    }

    /* Categories Grid */
    .m-categories-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .m-cat-item {
        border: 1px solid var(--border, #F1F5F9);
        border-radius: 12px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--white, #fff);
    }
    .m-cat-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .m-cat-info {
        flex: 1;
        min-width: 0;
    }
    .m-cat-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-dark, #0F172A);
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .m-cat-count {
        font-size: 11px;
        color: var(--text-gray, #64748B);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
    }

    /* Tips */
    .m-tips-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .m-tips-list li {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 12px;
        color: var(--text-dark, #0F172A);
        font-weight: 600;
        line-height: 1.4;
    }
    .m-tips-list li i {
        color: #10B981;
        font-size: 16px;
        margin-top: 0px;
    }
    
    /* Dark Theme Mobile Overrides */
    .dark-theme .m-header { background: var(--sidebar-bg, #111827); border-color: var(--border, #1E293B); }
    .dark-theme .m-container { background: var(--bg-main, #0B0F19); }
    .dark-theme .m-kpi-card, .dark-theme .m-doc-item, .dark-theme .m-section-card, .dark-theme .m-cat-item { background: var(--white, #111827); border-color: var(--border, #1E293B); }
    .dark-theme .m-filter-select, .dark-theme .m-search input { background-color: var(--bg-main, #0B0F19); color: var(--text-dark, #F8FAFC); border-color: var(--border, #1E293B); }
    .dark-theme .m-upload-zone { background: rgba(255,255,255,0.02); }
    .dark-theme .m-filter-btn, .dark-theme .m-action-btn { background: var(--white, #111827); border-color: var(--border, #1E293B); }
</style>

<header class="premium-header-pill">
    <div class="m-header-module m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu-alt-left'></i>
    </div>
    
    <div class="m-header-module m-header-brand">
        <img src="../assets/img/logo.png" alt="Logo">
        <span><?php echo htmlspecialchars(defined('HOUSE_NAME') ? HOUSE_NAME : 'Madhav Kunj'); ?></span>
    </div>
    
    <div class="m-header-module m-header-right">
        <div class="header-icon-btn" id="themeToggleMobile" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
            <i class='bx bx-moon'></i>
        </div>
        <div class="header-divider"></div>
        <div class="header-icon-btn" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';">
            <i class='bx bx-bell'></i>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="m-notif-badge"></span>
            <?php endif; ?>
        </div>
    </div>
</header>
<div style="height: 90px; width: 100%; display: block; flex-shrink: 0;"></div>


<div class="m-container animate-up">
    <!-- KPI Scroll -->
    <div class="m-kpi-scroll">
        <div class="m-kpi-card">
            <div class="m-kpi-icon" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">
                <i class='bx bx-folder'></i>
            </div>
            <div class="m-kpi-title">Total Documents</div>
            <div class="m-kpi-value"><?php echo count($documents); ?></div>
            <div class="m-kpi-subtitle">All documents</div>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                <i class='bx bx-check-shield'></i>
            </div>
            <div class="m-kpi-title">Verified Documents</div>
            <div class="m-kpi-value"><?php echo $verified_count; ?></div>
            <div class="m-kpi-subtitle">Approved & verified</div>
        </div>
        <div class="m-kpi-card">
            <div class="m-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                <i class='bx bx-time-five'></i>
            </div>
            <div class="m-kpi-title">Pending Documents</div>
            <div class="m-kpi-value"><?php echo $pending_count; ?></div>
            <div class="m-kpi-subtitle">Awaiting verification</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="m-filters">
        <div class="m-filters-row">
            <select class="m-filter-select">
                <option>All Categories</option>
            </select>
            <select class="m-filter-select">
                <option>All Status</option>
            </select>
            <button class="m-filter-btn">
                <i class='bx bx-filter'></i> Filter
            </button>
        </div>
        <div class="m-search">
            <i class='bx bx-search'></i>
            <input type="text" placeholder="Search documents...">
        </div>
    </div>

    <!-- Document List -->
    <div class="m-doc-list">
        <?php 
        $has_available = false;
        if (!empty($documents)) {
            foreach ($documents as $doc) {
                if ($doc['status'] === 'Verified') {
                    $has_available = true;
                    ?>
                    <div class="m-doc-item">
                        <div class="m-doc-icon" style="background: <?php echo htmlspecialchars($doc['cat_bg']); ?>; color: <?php echo htmlspecialchars($doc['cat_color']); ?>;">
                            <i class='bx <?php echo htmlspecialchars($doc['icon']); ?>'></i>
                        </div>
                        <div class="m-doc-info">
                            <div class="m-doc-title"><?php echo htmlspecialchars($doc['name']); ?></div>
                            <div class="m-doc-subtitle" style="margin-bottom: 0;"><?php echo htmlspecialchars($doc['desc']); ?></div>
                        </div>
                        <span class="m-status-badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">Verified</span>
                        <div class="m-doc-actions">
                            <?php if (!empty($doc['url'])): ?>
                            <a href="<?php echo htmlspecialchars($doc['url']); ?>" target="_blank" class="m-action-btn"><i class='bx bx-show'></i></a>
                            <a href="<?php echo htmlspecialchars($doc['url']); ?>" download class="m-action-btn"><i class='bx bx-download'></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            }
        }
        
        if (!$has_available):
        ?>
        <div style="text-align: center; padding: 30px 20px; color: var(--text-gray); font-size: 13px; font-weight: 500; background: var(--white); border: 1px solid var(--border); border-radius: 14px;">
            <i class='bx bx-folder-open' style="font-size: 32px; color: var(--border); margin-bottom: 8px; display: block;"></i>
            No available documents found.
        </div>
        <?php endif; ?>
    </div>


    <!-- Upload New Document -->
    <?php if (empty($user_docs['aadhaar_file'])): ?>
    <div class="m-section-card">
        <div class="m-section-title">Upload Aadhar Card</div>
        <div class="m-upload-zone" onclick="document.getElementById('aadhar-upload')?.click();">
            <i class='bx bx-cloud-upload'></i>
            <h4>Drag & drop your file here<br>or click to browse</h4>
            <p>Supports: PDF, JPG, PNG (Max. 10MB)</p>
            <button class="m-btn-primary">Choose File</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Document Categories -->
    <div class="m-section-card">
        <div class="m-section-title">
            Document Categories
            <a href="#" style="font-size: 13px; color: var(--primary-purple); text-decoration: none; font-weight: 700;">View All</a>
        </div>
        <div class="m-categories-grid">
            <?php 
            $dynamic_categories = [];
            foreach ($documents as $doc) {
                $cat_key = $doc['category'];
                if (!isset($dynamic_categories[$cat_key])) {
                    $dynamic_categories[$cat_key] = [
                        'name' => $cat_key === 'Identity' ? 'Identity Proof' : ($cat_key === 'Utility' ? 'Utility Bills' : $cat_key),
                        'icon' => $doc['icon'],
                        'color' => $doc['cat_color'],
                        'bg' => $doc['cat_bg'],
                        'count' => 0
                    ];
                }
                if ($doc['status'] === 'Verified') {
                    $dynamic_categories[$cat_key]['count']++;
                }
            }
            
            foreach ($dynamic_categories as $cat):
            ?>
            <div class="m-cat-item">
                <div class="m-cat-icon" style="background: <?php echo htmlspecialchars($cat['bg']); ?>; color: <?php echo htmlspecialchars($cat['color']); ?>;">
                    <i class='bx <?php echo htmlspecialchars($cat['icon']); ?>'></i>
                </div>
                <div class="m-cat-info">
                    <div class="m-cat-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                    <div class="m-cat-count"><?php echo $cat['count']; ?> <i class='bx bx-chevron-right'></i></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Important Tips -->
    <div class="m-section-card">
        <div class="m-section-title" style="margin-bottom: 12px;">
            <span style="display: flex; align-items: center; gap: 8px;"><i class='bx bx-bulb' style="color: #F59E0B; font-size: 20px;"></i> Important Tips</span>
        </div>
        <ul class="m-tips-list">
            <li><i class='bx bx-check'></i> Upload clear and valid documents for quick verification.</li>
            <li><i class='bx bx-check'></i> Supported formats: PDF, JPG, PNG</li>
            <li><i class='bx bx-check'></i> Max file size: 10MB per document</li>
            <li><i class='bx bx-check'></i> Keep your documents up to date</li>
        </ul>
    </div>
</div>
