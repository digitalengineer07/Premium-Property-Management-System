import os
import re

filepath = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Replace the hardcoded created_at month display with logic that respects $n['month'] if available
    # The existing line 748: 
    # <span class="pv-bill-info-type" title="<?php echo $bType ? htmlspecialchars($bType) . ' - ' : ''; ?><?php echo date('M Y', strtotime($n['created_at'])); ?>"><?php echo $bType ? $bType . ' - ' : ''; ?><?php echo date('M Y', strtotime($n['created_at'])); ?></span>
    
    # Let's add some logic before this to determine the correct month string
    new_logic = """
                            <?php
                            $disp_month = date('M Y', strtotime($n['created_at'])); // Default fallback
                            if (!empty($n['month'])) {
                                $disp_month = date('M Y', strtotime($n['month'] . '-01'));
                            } elseif ($n['bill_id'] > 0) {
                                // Try to fetch from rent/electricity table
                                $bid = (int)$n['bill_id'];
                                if ($n['bill_type'] == 'rent') {
                                    $mr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month FROM rent WHERE id=$bid"));
                                    if ($mr && !empty($mr['month'])) $disp_month = date('M Y', strtotime($mr['month'] . '-01'));
                                } elseif ($n['bill_type'] == 'electricity' || $n['bill_type'] == 'elec_rent') {
                                    $mr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT month FROM electricity WHERE id=$bid"));
                                    if ($mr && !empty($mr['month'])) $disp_month = date('M Y', strtotime($mr['month'] . '-01'));
                                }
                            }
                            ?>
                            <span class="pv-bill-info-type" title="<?php echo $bType ? htmlspecialchars($bType) . ' - ' : ''; ?><?php echo htmlspecialchars($disp_month); ?>"><?php echo $bType ? htmlspecialchars($bType) . ' - ' : ''; ?><?php echo htmlspecialchars($disp_month); ?></span>"""
    
    content = re.sub(
        r'<span class="pv-bill-info-type"[^>]*>.*?</span>',
        new_logic.strip(),
        content,
        count=1
    )
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed admin/payment-verifications.php display logic.")
