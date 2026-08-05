import os
import re

path = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Update CSS class
    old_css = ".pv-bill-info-type { font-size: 12px; font-weight: 600; color: #0F172A; margin-bottom: 2px; display: block; white-space: nowrap; }"
    new_css = ".pv-bill-info-type { font-size: 12px; font-weight: 600; color: #0F172A; margin-bottom: 2px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; cursor: default; }"
    
    content = content.replace(old_css, new_css)
    
    # Update HTML to add title attribute
    # The original line: <span class="pv-bill-info-type"><?php echo $bType ? $bType . ' - ' : ''; ?><?php echo date('M Y', strtotime($n['created_at'])); ?></span>
    # We want to change it to: <span class="pv-bill-info-type" title="<?php echo $bType ? $bType . ' - ' : ''; ?><?php echo date('M Y', strtotime($n['created_at'])); ?>"><?php echo $bType ? $bType . ' - ' : ''; ?><?php echo date('M Y', strtotime($n['created_at'])); ?></span>
    
    old_html = r"<span class=\"pv-bill-info-type\"><?php echo $bType \? $bType . ' - ' : ''; \?><?php echo date\('M Y', strtotime\(\$n\['created_at'\]\)\); \?></span>"
    new_html = r"<span class=\"pv-bill-info-type\" title=\"<?php echo htmlspecialchars($bType ? $bType . ' - ' : '' . date('M Y', strtotime($n['created_at']))); ?>\"><?php echo $bType ? $bType . ' - ' : ''; ?><?php echo date('M Y', strtotime($n['created_at'])); ?></span>"
    
    # Actually, simpler replace:
    content = content.replace(
        '<span class="pv-bill-info-type"><?php echo $bType ? $bType . \' - \' : \'\'; ?><?php echo date(\'M Y\', strtotime($n[\'created_at\'])); ?></span>',
        '<span class="pv-bill-info-type" title="<?php echo $bType ? htmlspecialchars($bType) . \' - \' : \'\'; ?><?php echo date(\'M Y\', strtotime($n[\'created_at\'])); ?>"><?php echo $bType ? $bType . \' - \' : \'\'; ?><?php echo date(\'M Y\', strtotime($n[\'created_at\'])); ?></span>'
    )

    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print("Updated Bill Info text truncation successfully.")
