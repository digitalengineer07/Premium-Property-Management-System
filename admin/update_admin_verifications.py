import re
import os

path = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 1. Update the table headers
    content = content.replace("<th>Date Submitted</th>", "<th>Date Submitted</th>\n                        <th>Bill Month</th>")
    
    # 2. Update the table body
    # Find the block for the first <td> (which has Date & Time) and append a new <td> for Bill Month.
    td_block = """                        <td>
                            <span class="pv-date-text"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></span>
                            <span class="pv-time-text"><?php echo date('h:i A', strtotime($n['created_at'])); ?></span>
                        </td>"""
    
    new_td_block = """                        <td>
                            <span class="pv-date-text"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></span>
                            <span class="pv-time-text"><?php echo date('h:i A', strtotime($n['created_at'])); ?></span>
                        </td>
                        <td>
                            <span class="pv-date-text" style="color: #64748B; font-weight: 500;">
                                <?php echo !empty($n['month']) ? s($n['month']) : '-'; ?>
                            </span>
                        </td>"""
    
    content = content.replace(td_block, new_td_block)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added Bill Month column to admin/payment-verifications.php.")
