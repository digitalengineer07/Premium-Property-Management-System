<?php
$file = 'c:/xampp/htdocs/renter-system/renter/views/mobile/my-payments_mobile.php';
$content = file_get_contents($file);

$startMarker = '<?php foreach ($merged_rents as $idx => $t): ?>';
$endMarker = '<?php endforeach; ?>';

$startPos = strpos($content, $startMarker);
if ($startPos === false) die("Start marker not found");

$firstEndPos = strpos($content, $endMarker, $startPos);
$secondEndPos = strpos($content, $endMarker, $firstEndPos + strlen($endMarker));
if ($secondEndPos === false) die("Second end marker not found");

$endPos = $secondEndPos + strlen($endMarker);

$replacement = <<<'EOD'
        <?php 
        $all_mobile = [];
        foreach ($merged_rents as $t) {
            $t['item_type'] = 'rent_or_other';
            $all_mobile[] = $t;
        }
        // Take all elecs, or maybe just the same slice? The original code did array_slice($elecs, 0, 3).
        // Let's take all of them so it matches desktop, but if we need slice, we will do it.
        // Actually, since they want "month wise with both electricity record and rent record just like payment page on desktop view mode", desktop takes all bills!
        foreach ($elecs as $t) {
            $t['item_type'] = 'elec_only';
            $all_mobile[] = $t;
        }
        usort($all_mobile, function($a, $b) {
            $t1 = strtotime($b['month'] . '-01');
            $t2 = strtotime($a['month'] . '-01');
            return $t2 <=> $t1; // descending order
        });
        
        foreach ($all_mobile as $idx => $t): 
            $isLast = ($idx === count($all_mobile) - 1);
            $isPending = ($t['status'] === 'Due');
            $amount = (float)$t['amount'];
            $dataYear = date('Y', strtotime($t['month'] . '-01'));

            if ($t['item_type'] === 'rent_or_other'):
                // Determine icons based on source
                if ($t['source'] === 'rent_table' || $t['source'] === 'elec_table' && $amount > 0) {
                    $icon = "<i class='bx bx-home-alt'></i>";
                    $iconStyle = "background: rgba(98, 75, 255, 0.1); color: #624BFF;";
                    $title = "Rent Payment";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                } else if ($t['source'] === 'advance') {
                    $icon = "<i class='bx bx-receipt'></i>";
                    $iconStyle = "background: rgba(59, 130, 246, 0.1); color: #3B82F6;";
                    $title = "Advance Payment";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                } else {
                    $icon = "<i class='bx bx-wrench'></i>";
                    $iconStyle = "background: rgba(255, 75, 107, 0.1); color: #FF4B6B;";
                    $title = "Maintenance Charge";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                }
                $dataType = ($t['source'] === 'rent_table' || $t['source'] === 'elec_table' && $amount > 0) ? 'rent' : (($t['source'] === 'advance') ? 'other' : 'other');
        ?>
            <div class="m-pay-card-item" data-type="<?php echo $dataType; ?>" data-year="<?php echo $dataYear; ?>" style="display: flex; align-items: center; padding: 16px; border-bottom: <?php echo $isLast ? 'none' : '1px solid var(--border)'; ?>; position: relative; overflow: hidden;">
                <?php if ($isPending): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(245,158,11,0.1); color: #D97706; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Pending</span>
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
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
                            <?php echo $title; ?>
                        </h4>
                        <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">₹<?php echo number_format($amount, 2); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <?php echo $subtitle; ?>
                        </p>
                        <?php if ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo $amount; ?>, '<?php echo addslashes($title); ?>', '<?php echo $t['source'] === 'advance' ? 'advance' : 'rent'; ?>')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                <i class='bx bx-revision'></i> Pay Now
                            </button>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 10px; color: var(--text-gray);"><?php echo date('d M Y', strtotime($t['month']. '-05')); ?></span>
                                <button style="background: none; border: 1px solid var(--border); border-radius: 8px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; color: #624BFF; cursor: pointer;"><i class='bx bx-download'></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="m-pay-card-item" data-type="electricity" data-year="<?php echo $dataYear; ?>" style="display: flex; align-items: center; padding: 16px; border-bottom: <?php echo $isLast ? 'none' : '1px solid var(--border)'; ?>; position: relative; overflow: hidden;">
                <?php if ($isPending): ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(245,158,11,0.1); color: #D97706; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Pending</span>
                <?php else: ?>
                    <span style="position: absolute; top: 0; right: 0; background: rgba(16,185,129,0.1); color: #10B981; padding: 4px 12px; border-radius: 0 16px 0 12px; font-size: 10px; font-weight: 800;">Paid</span>
                <?php endif; ?>
                <!-- Icon -->
                <div style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <i class='bx bx-bolt-circle'></i>
                </div>
                
                <!-- Body -->
                <div style="flex: 1; min-width: 0; margin-left: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
                            Electricity Payment
                        </h4>
                        <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);">₹<?php echo number_format($amount, 2); ?></div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <p style="font-size: 11px; color: var(--text-gray); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <?php echo date('M Y', strtotime($t['month'] . '-01')); ?> • Units: <?php echo htmlspecialchars($t['units_consumed']); ?>
                        </p>
                        <?php if ($isPending): ?>
                            <button onclick="openPaymentModal(<?php echo $amount; ?>, 'Electricity Bill', 'electricity')" style="background: white; border: 1px solid rgba(255, 75, 107, 0.3); color: #FF4B6B; border-radius: 12px; padding: 4px 10px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                <i class='bx bx-revision'></i> Pay Now
                            </button>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 10px; color: var(--text-gray);"><?php echo date('d M Y', strtotime($t['month']. '-03')); ?></span>
                                <button style="background: none; border: 1px solid var(--border); border-radius: 8px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; color: #624BFF; cursor: pointer;"><i class='bx bx-download'></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php endforeach; ?>
EOD;

$newContent = substr($content, 0, $startPos) . $replacement . substr($content, $endPos);
file_put_contents($file, $newContent);
echo "Success";
?>
