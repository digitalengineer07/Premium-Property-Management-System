import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-payments_mobile.php'

target = """        foreach($mobile_aggregates as $agg) {
            $all_mobile[] = $agg;
        }"""

replacement = """        foreach($mobile_aggregates as &$agg) {
            // Need to compute aggregate remaining amount, but mobile aggregate didn't track remaining_amount. Let's fix that too.
        }"""

target2 = """            if(!isset($mobile_aggregates[$m])) {
                $mobile_aggregates[$m] = [
                    'item_type' => 'aggregate',
                    'month' => $m,
                    'amount' => 0,
                    'status' => 'Paid',
                    'has_unpaid' => false,
                    'has_partial' => false,
                    'has_paid' => false
                ];
            }
            $mobile_aggregates[$m]['amount'] += (float)$t['amount'];"""

replacement2 = """            if(!isset($mobile_aggregates[$m])) {
                $mobile_aggregates[$m] = [
                    'item_type' => 'aggregate',
                    'month' => $m,
                    'amount' => 0,
                    'remaining_amount' => 0,
                    'status' => 'Paid',
                    'has_unpaid' => false,
                    'has_partial' => false,
                    'has_paid' => false
                ];
            }
            $mobile_aggregates[$m]['amount'] += (float)$t['amount'];
            $mobile_aggregates[$m]['remaining_amount'] += isset($t['remaining_amount']) ? (float)$t['remaining_amount'] : (float)$t['amount'];"""

target3 = """                        <?php if ($t['status'] === 'Partial'): ?>
                            <div style="display: flex; gap: 6px;">
                                <button onclick="openPaymentModal(<?php echo max(0, min((float)$amount, (float)$total_due)); ?>, 'Total Payment', 'monthly', 0, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                    <i class='bx bx-revision'></i> Pay Now
                                </button>
                                <a href="payment-history.php?month=<?php echo urlencode(date('F Y', strtotime($t['month'].'-01'))); ?>" style="background: white; border: 1px solid rgba(98, 75, 255, 0.3); color: #624BFF; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer; text-decoration: none;">
                                    <i class='bx bx-history'></i> History
                                </a>
                            </div>
                        <?php elseif ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo max(0, min((float)$amount, (float)$total_due)); ?>, 'Total Payment', 'monthly', 0, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                <i class='bx bx-credit-card-alt'></i> Pay Now
                            </button>
                        <?php else: ?>"""

replacement3 = """                        <?php if ($t['status'] === 'Partial'): ?>
                            <div style="display: flex; gap: 6px;">
                                <button onclick="openPaymentModal(<?php echo max(0, min((float)(isset($t['remaining_amount']) ? $t['remaining_amount'] : $amount), (float)$total_due)); ?>, 'Total Payment', 'monthly', 0, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                    <i class='bx bx-revision'></i> Pay Now
                                </button>
                                <a href="payment-history.php?month=<?php echo urlencode(date('F Y', strtotime($t['month'].'-01'))); ?>" style="background: white; border: 1px solid rgba(98, 75, 255, 0.3); color: #624BFF; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer; text-decoration: none;">
                                    <i class='bx bx-history'></i> History
                                </a>
                            </div>
                        <?php elseif ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo max(0, min((float)(isset($t['remaining_amount']) ? $t['remaining_amount'] : $amount), (float)$total_due)); ?>, 'Total Payment', 'monthly', 0, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                <i class='bx bx-credit-card-alt'></i> Pay Now
                            </button>
                        <?php else: ?>"""

target4 = """                <?php if ($t['status'] === 'Partial'): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(245,158,11,0.1); color: #D97706; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Partial</span>
                <?php elseif ($isPending): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(255,75,107,0.1); color: #FF4B6B; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Due</span>
                <?php else: ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(16,185,129,0.1); color: #10B981; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Paid</span>
                <?php endif; ?>
                <!-- Icon -->
                <div style="<?php echo $iconStyle; ?> width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <?php echo $icon; ?>
                </div>
                
                <!-- Body -->
                <div style="flex: 1; min-width: 0; margin-left: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 2px;">
                            <?php echo $title; ?>
                        </h4>
                        <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">â‚¹<?php echo number_format($amount); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 2px;">
                            <?php echo $subtitle; ?>
                        </p>
                        <?php if ($t['status'] === 'Partial'): ?>
                            <div style="display: flex; gap: 6px;">
                                <button onclick="openPaymentModal(<?php echo max(0, min((float)$amount, (float)$total_due)); ?>, '<?php echo htmlspecialchars($title); ?>', '<?php echo $t['source'] === 'rent_table' ? 'rent' : 'electricity'; ?>', <?php echo $t['id']; ?>, '<?php echo addslashes($subtitle); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                    <i class='bx bx-revision'></i> Pay Now
                                </button>
                                <a href="payment-history.php?month=<?php echo urlencode(date('F Y', strtotime($t['month'].'-01'))); ?>" style="background: white; border: 1px solid rgba(98, 75, 255, 0.3); color: #624BFF; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer; text-decoration: none;">
                                    <i class='bx bx-history'></i> History
                                </a>
                            </div>
                        <?php elseif ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo max(0, min((float)$amount, (float)$total_due)); ?>, '<?php echo htmlspecialchars($title); ?>', '<?php echo $t['source'] === 'rent_table' ? 'rent' : 'electricity'; ?>', <?php echo $t['id']; ?>, '<?php echo addslashes($subtitle); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                <i class='bx bx-credit-card-alt'></i> Pay Now
                            </button>
                        <?php else: ?>"""

replacement4 = """                <?php if ($t['status'] === 'Partial'): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(245,158,11,0.1); color: #D97706; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Partial</span>
                <?php elseif ($isPending): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(255,75,107,0.1); color: #FF4B6B; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Due</span>
                <?php else: ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(16,185,129,0.1); color: #10B981; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Paid</span>
                <?php endif; ?>
                <!-- Icon -->
                <div style="<?php echo $iconStyle; ?> width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <?php echo $icon; ?>
                </div>
                
                <!-- Body -->
                <div style="flex: 1; min-width: 0; margin-left: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 2px;">
                            <?php echo $title; ?>
                        </h4>
                        <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">â‚¹<?php echo number_format($amount); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 2px;">
                            <?php echo $subtitle; ?>
                        </p>
                        <?php if ($t['status'] === 'Partial'): ?>
                            <div style="display: flex; gap: 6px;">
                                <button onclick="openPaymentModal(<?php echo max(0, min((float)(isset($t['remaining_amount']) ? $t['remaining_amount'] : $amount), (float)$total_due)); ?>, '<?php echo htmlspecialchars($title); ?>', '<?php echo $t['source'] === 'rent_table' ? 'rent' : 'electricity'; ?>', <?php echo $t['id']; ?>, '<?php echo addslashes($subtitle); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                    <i class='bx bx-revision'></i> Pay Now
                                </button>
                                <a href="payment-history.php?month=<?php echo urlencode(date('F Y', strtotime($t['month'].'-01'))); ?>" style="background: white; border: 1px solid rgba(98, 75, 255, 0.3); color: #624BFF; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer; text-decoration: none;">
                                    <i class='bx bx-history'></i> History
                                </a>
                            </div>
                        <?php elseif ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo max(0, min((float)(isset($t['remaining_amount']) ? $t['remaining_amount'] : $amount), (float)$total_due)); ?>, '<?php echo htmlspecialchars($title); ?>', '<?php echo $t['source'] === 'rent_table' ? 'rent' : 'electricity'; ?>', <?php echo $t['id']; ?>, '<?php echo addslashes($subtitle); ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 2px; cursor: pointer;">
                                <i class='bx bx-credit-card-alt'></i> Pay Now
                            </button>
                        <?php else: ?>"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    content = content.replace(target2, replacement2)
    content = content.replace(target3, replacement3)
    content = content.replace(target4, replacement4)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Updated {filepath} mobile buttons")
else:
    print(f"File not found: {filepath}")
