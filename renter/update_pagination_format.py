import re
import os

desktop_path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'
mobile_path = r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-approvals_mobile.php'

def replace_pagination(filepath):
    if not os.path.exists(filepath):
        return
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the pagination block start and end
    start_tag = r'<\?php if \(isset\(\$total_pages\) && \$total_pages > 1\): \?>'
    end_tag = r'<\?php endif; \?>'
    
    # We need to be careful with regex here to replace the exact pagination block
    # In desktop, it's before <?php else: ?>
    
    pattern = re.compile(r'<\?php if \(isset\(\$total_pages\) && \$total_pages > 1\): \?>.*?<\?php endif; \?>', re.DOTALL)
    
    # Check if there are multiple occurrences. We only want to replace the one that has pagination logic.
    matches = pattern.findall(content)
    
    new_pagination = """<?php if (isset($total_pages) && $total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 16px; padding: 16px 0; border-top: 1px solid var(--border);">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); text-decoration: none; font-size: 18px; font-weight: 800; transition: 0.2s;"><i class='bx bx-chevron-left'></i></a>
                    <?php else: ?>
                        <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(0, 0, 0, 0.05); color: var(--text-gray); font-size: 18px; font-weight: 800; opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-left'></i></span>
                    <?php endif; ?>
                    
                    <span style="font-size: 14px; font-weight: 800; color: var(--text-dark); min-width: 24px; text-align: center;"><?php echo $page; ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); text-decoration: none; font-size: 18px; font-weight: 800; transition: 0.2s;"><i class='bx bx-chevron-right'></i></a>
                    <?php else: ?>
                        <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(0, 0, 0, 0.05); color: var(--text-gray); font-size: 18px; font-weight: 800; opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-right'></i></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>"""
    
    for match in matches:
        if 'Prev' in match or 'bx-chevron-left' in match:
            content = content.replace(match, new_pagination)
            
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Updated pagination in {filepath}")

replace_pagination(desktop_path)
replace_pagination(mobile_path)
