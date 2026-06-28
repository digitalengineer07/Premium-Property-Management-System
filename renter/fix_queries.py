import sys
import re

path = 'C:/xampp/htdocs/renter-system/renter/queries.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

insert_block = """
            <!-- Right: List -->
            <div class="list-card">
                <div class="list-header">
                    <h3>My Queries</h3>
                    <div style="display: flex; gap: 12px;">
                        <select class="form-control" style="padding: 8px 36px 8px 16px; width: auto; font-weight: 600; font-size: 13px; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=%22none%22 stroke=%22%2364748B%22 stroke-width=%222%22 viewBox=%220 0 24 24%22 xmlns=%22http://www.w3.org/2000/svg%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22></path></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px;">
                            <option>All Status</option>
                            <option>Open</option>
                            <option>Resolved</option>
                            <option>Closed</option>
                        </select>
                        <button class="btn-outline" style="width: auto; padding: 8px 16px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>

                <div style="flex: 1;">
                    <?php 
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = 5;
                    $total_pages = $total_queries > 0 ? ceil($total_queries / $limit) : 1;
                    if ($page > $total_pages) $page = $total_pages;
                    $offset = ($page - 1) * $limit;
                    
                    if(empty($queries)) {
                        echo '<div style="padding: 40px; text-align: center; color: var(--text-gray);">No queries found.</div>';
                    }
                    $paginated_queries = array_slice($queries, $offset, $limit);
                    foreach($paginated_queries as $index => $q): 
                        // Map categories to icons and colors
                        $cat = strtolower($q['category']);
                        if (strpos($cat, 'plumbing') !== false) {
                            $icon = 'bx-water'; $bg = 'rgba(245, 158, 11, 0.1)'; $col = '#F59E0B';
                        } elseif (strpos($cat, 'elect') !== false) {
                            $icon = 'bx-bolt-circle'; $bg = 'rgba(59, 130, 246, 0.1)'; $col = '#3B82F6';
                        } elseif (strpos($cat, 'housekeep') !== false || strpos($cat, 'clean') !== false) {
                            $icon = 'bx-brush'; $bg = 'rgba(16, 185, 129, 0.1)'; $col = '#10B981';
                        } elseif (strpos($cat, 'maintain') !== false || strpos($cat, 'maintenance') !== false) {
                            $icon = 'bx-wrench'; $bg = 'rgba(98, 75, 255, 0.1)'; $col = 'var(--primary-purple)';
                        } elseif (strpos($cat, 'parking') !== false) {
                            $icon = 'bx-car'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        } elseif (strpos($cat, 'general') !== false) {
                            $icon = 'bx-category'; $bg = 'rgba(139, 92, 246, 0.1)'; $col = '#8B5CF6';
                        } else {
                            $icon = 'bx-info-circle'; $bg = 'rgba(239, 68, 68, 0.1)'; $col = '#EF4444';
                        }

                        // Map Status
                        $st = strtolower($q['ui_status']);
                        if ($st == 'open') {
                            $s_bg = 'rgba(245, 158, 11, 0.1)'; $s_col = '#F59E0B';
                        } elseif ($st == 'resolved') {
                            $s_bg = 'rgba(16, 185, 129, 0.1)'; $s_col = '#10B981';
                        } else {
                            $s_bg = 'rgba(239, 68, 68, 0.1)'; $s_col = '#EF4444'; // Closed
                        }

                        $date_formatted = date('d M Y', strtotime($q['created_at']));
                        $qid_formatted = '#QRY-' . str_pad($q['id'], 4, '0', STR_PAD_LEFT);
                    ?>
                    <div class="query-item">
                        <div class="qi-icon" style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>;">
                            <i class='bx <?php echo $icon; ?>'></i>
                        </div>
                        <div class="qi-details">
                            <h4><?php echo htmlspecialchars($q['subject']); ?></h4>
                            <span class="category"><?php echo htmlspecialchars($q['category']); ?></span>
                            <p><?php echo htmlspecialchars($q['message']); ?></p>
                        </div>
                        <div class="qi-status" style="background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>;">
                            <?php echo htmlspecialchars($q['ui_status']); ?>
                        </div>
                        <div class="qi-meta">
                            <span class="date"><?php echo $date_formatted; ?></span>
                            <span class="qid"><?php echo $qid_formatted; ?></span>
                        </div>
                        <button class="qi-action"><i class='bx bx-chevron-right'></i></button>
                    </div>
                    <?php endforeach; ?>
                </div>"""

new_content = re.sub(r'</form>\s*</div>\s*<!-- Footer Pagination -->', '</form>\n            </div>\n' + insert_block + '\n\n                <!-- Footer Pagination -->', content)

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_content)
print("Fix applied successfully")
