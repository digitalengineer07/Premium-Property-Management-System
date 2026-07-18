import os
import re

backend_path = r'c:\xampp\htdocs\renter-system\renter\payment-approvals.php'
desktop_path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'
mobile_path = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

# 1. Update Backend
if os.path.exists(backend_path):
    with open(backend_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    old_fetch = """// Fetch Approvals from DB
$approvals = [];
$res = mysqli_query($conn, "SELECT * FROM payment_notifications WHERE user_id = $user_id ORDER BY created_at DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $approvals[] = $row;
    }
}"""

    new_fetch = """// Fetch Approvals from DB
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$total_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM payment_notifications WHERE user_id = $user_id");
$total_row = mysqli_fetch_assoc($total_res);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

$approvals = [];
$res = mysqli_query($conn, "SELECT * FROM payment_notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $approvals[] = $row;
    }
}"""
    content = content.replace(old_fetch, new_fetch)
    with open(backend_path, 'w', encoding='utf-8') as f:
        f.write(content)

# Pagination HTML block
pagination_html = """
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; padding: 20px 0; border-top: 1px solid var(--border);">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" style="padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--text-dark); display: flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600;"><i class='bx bx-chevron-left'></i> Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" style="padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; <?php echo $i === $page ? 'background: var(--primary-purple); color: white;' : 'border: 1px solid var(--border); color: var(--text-dark);'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" style="padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--text-dark); display: flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600;">Next <i class='bx bx-chevron-right'></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
"""

# 2. Update Desktop View
if os.path.exists(desktop_path):
    with open(desktop_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Insert pagination after the table closing tag
    if "</table>" in content:
        content = content.replace("</table>", "</table>" + pagination_html)
        with open(desktop_path, 'w', encoding='utf-8') as f:
            f.write(content)

# 3. Update Mobile View
if os.path.exists(mobile_path):
    with open(mobile_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Insert pagination after the foreach loop ends
    if "<?php endforeach; ?>" in content:
        content = content.replace("<?php endforeach; ?>", "<?php endforeach; ?>" + pagination_html)
        with open(mobile_path, 'w', encoding='utf-8') as f:
            f.write(content)

print("Pagination added successfully to all files.")
