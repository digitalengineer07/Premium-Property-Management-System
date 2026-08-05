import re
import os

path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 1. Update the table headers
    content = content.replace("<th>Date Submitted</th>", "<th>Date & Time</th>\n                        <th>Bill Month</th>")
    
    # 2. Update the table body
    # Find the block for the first <td> (which has Date & Time) and append a new <td> for Bill Month.
    td_block = """                        <td>
                            <div style="font-weight: 700; color: var(--text-dark);"><?php echo date('d M Y', strtotime($ap['created_at'])); ?></div>
                            <div style="font-size: 12px; color: var(--text-gray); margin-top: 4px;"><?php echo date('h:i A', strtotime($ap['created_at'])); ?></div>
                        </td>"""
    
    new_td_block = """                        <td>
                            <div style="font-weight: 700; color: var(--text-dark);"><?php echo date('d M Y', strtotime($ap['created_at'])); ?></div>
                            <div style="font-size: 12px; color: var(--text-gray); margin-top: 4px;"><?php echo date('h:i A', strtotime($ap['created_at'])); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-gray);">
                                <?php echo !empty($ap['month']) ? htmlspecialchars($ap['month']) : '-'; ?>
                            </div>
                        </td>"""
    
    content = content.replace(td_block, new_td_block)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added Bill Month and updated Date & Time successfully.")
