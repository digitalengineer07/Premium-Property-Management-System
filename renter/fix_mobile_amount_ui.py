import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-payments_mobile.php'

target1 = """        foreach ($all_mobile as $idx => $t): 
            $isLast = ($idx === count($all_mobile) - 1);
            $isPending = ($t['status'] === 'Due');
            $amount = (float)$t['amount'];
            $dataYear = date('Y', strtotime($t['month'] . '-01'));"""

replacement1 = """        foreach ($all_mobile as $idx => $t): 
            $isLast = ($idx === count($all_mobile) - 1);
            $isPending = ($t['status'] === 'Due');
            $amount = (float)$t['amount'];
            $displayAmount = ($t['status'] === 'Paid') ? $amount : (isset($t['remaining_amount']) ? (float)$t['remaining_amount'] : $amount);
            $dataYear = date('Y', strtotime($t['month'] . '-01'));"""

target2 = """                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 2px;">
                            <?php echo $title; ?>
                        </h4>
                        <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">₹<?php echo number_format($amount); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 2px;">
                            <?php echo $subtitle; ?>
                        </p>"""

replacement2 = """                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 2px;">
                            <?php echo $title; ?>
                        </h4>
                        <div style="text-align: right;">
                            <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">₹<?php echo number_format($displayAmount); ?></div>
                            <?php if ($t['status'] === 'Partial' && $displayAmount < $amount): ?>
                                <div style="font-size: 10px; color: var(--text-gray); font-weight: 500;">Orig: ₹<?php echo number_format($amount); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 2px;">
                            <?php echo $subtitle; ?>
                        </p>"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # We will do replacement1 first
    if target1 in content:
        content = content.replace(target1, replacement1)
        
        # Now target2 appears 3 times in the code (for aggregate, rent_or_other, electricity)
        # We replace all of them
        content = content.replace(target2, replacement2)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath} with dynamic amount logic")
    else:
        print(f"Target not found in {filepath}")
else:
    print(f"File not found: {filepath}")
