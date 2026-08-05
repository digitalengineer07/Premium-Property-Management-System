import os
import re

filepath = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the UTR rendering logic
    old_utr_html = """                        <td>
                            <div class="pv-utr-text">
                                <?php echo s($n['transaction_id']); ?> <i class='bx bx-copy' title="Copy UTR" onclick="navigator.clipboard.writeText('<?php echo s($n['transaction_id']); ?>'); alert('UTR Copied!');"></i>
                            </div>
                        </td>"""
    
    new_utr_html = """                        <td>
                            <div class="pv-utr-text" style="margin-bottom:4px;">
                                <?php echo !empty($n['transaction_id']) ? s($n['transaction_id']) : 'No UTR (Cash)'; ?> 
                                <?php if(!empty($n['transaction_id'])): ?>
                                    <i class='bx bx-copy' title="Copy UTR" onclick="navigator.clipboard.writeText('<?php echo s($n['transaction_id']); ?>'); alert('UTR Copied!');"></i>
                                <?php endif; ?>
                            </div>
                            <?php if(!empty($n['sys_tx_id'])): ?>
                                <div style="font-size:11px; color:#64748B; display:flex; align-items:center; gap:4px;">
                                    <i class='bx bx-barcode-reader'></i> <?php echo s($n['sys_tx_id']); ?>
                                </div>
                            <?php endif; ?>
                        </td>"""

    if old_utr_html in content:
        content = content.replace(old_utr_html, new_utr_html)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
        print("Added sys_tx_id UI to admin panel.")
