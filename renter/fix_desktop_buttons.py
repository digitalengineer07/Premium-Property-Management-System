import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'

target = """                                        <?php if ($agg['status'] == 'Paid'): ?>
                                            <a href="payment-history.php?month=<?php echo urlencode($current_month); ?>" class="btn-view-receipt"><i class='bx bx-history'></i> History</a>
                                        <?php elseif ($agg['status'] == 'Partial'): ?>
                                            <div style="display: flex; gap: 2px; align-items: center;">
                                                <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)$agg['amount'], (float)$total_due)); ?>, 'Total Payment for <?php echo htmlspecialchars($current_month); ?>', 'monthly', 0, '<?php echo addslashes($current_month); ?>')">
                                                    <i class='bx bx-credit-card-alt'></i> Pay Now
                                                </button>
                                                <a href="payment-history.php?month=<?php echo urlencode($current_month); ?>" class="btn-view-receipt"><i class='bx bx-history'></i> History</a>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)$agg['amount'], (float)$total_due)); ?>, 'Total Payment for <?php echo htmlspecialchars($current_month); ?>', 'monthly', 0, '<?php echo addslashes($current_month); ?>')">
                                                <i class='bx bx-credit-card-alt'></i> Pay Now
                                            </button>
                                        <?php endif; ?>"""

replacement = """                                        <?php if ($agg['status'] == 'Paid'): ?>
                                            <a href="payment-history.php?month=<?php echo urlencode($current_month); ?>" class="btn-view-receipt"><i class='bx bx-history'></i> History</a>
                                        <?php elseif ($agg['status'] == 'Partial'): ?>
                                            <div style="display: flex; gap: 2px; align-items: center;">
                                                <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)$agg['remaining_amount'], (float)$total_due)); ?>, 'Total Payment for <?php echo htmlspecialchars($current_month); ?>', 'monthly', 0, '<?php echo addslashes($current_month); ?>')">
                                                    <i class='bx bx-credit-card-alt'></i> Pay Now
                                                </button>
                                                <a href="payment-history.php?month=<?php echo urlencode($current_month); ?>" class="btn-view-receipt"><i class='bx bx-history'></i> History</a>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)$agg['remaining_amount'], (float)$total_due)); ?>, 'Total Payment for <?php echo htmlspecialchars($current_month); ?>', 'monthly', 0, '<?php echo addslashes($current_month); ?>')">
                                                <i class='bx bx-credit-card-alt'></i> Pay Now
                                            </button>
                                        <?php endif; ?>"""

target2 = """                                    <?php if ($bill['status'] == 'Paid'): ?>
                                        <a href="#" class="btn-view-receipt"><i class='bx bx-download'></i> View Receipt</a>
                                    <?php elseif ($bill['status'] == 'Partial'): ?>
                                        <div style="display: flex; gap: 2px; align-items: center;">
                                            <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)$bill['amount'], (float)$total_due)); ?>, '<?php echo htmlspecialchars($bill['title']); ?> for <?php echo htmlspecialchars($bill['period']); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>, '<?php echo addslashes($bill['period']); ?>')">
                                                <i class='bx bx-credit-card-alt'></i> Pay Now
                                            </button>
                                            <a href="payment-history.php?month=<?php echo urlencode($bill['period']); ?>" class="btn-view-receipt"><i class='bx bx-history'></i> History</a>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)$bill['amount'], (float)$total_due)); ?>, '<?php echo htmlspecialchars($bill['title']); ?> for <?php echo htmlspecialchars($bill['period']); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>, '<?php echo addslashes($bill['period']); ?>')">
                                            <i class='bx bx-credit-card-alt'></i> Pay Now
                                        </button>
                                    <?php endif; ?>"""

replacement2 = """                                    <?php if ($bill['status'] == 'Paid'): ?>
                                        <a href="#" class="btn-view-receipt"><i class='bx bx-download'></i> View Receipt</a>
                                    <?php elseif ($bill['status'] == 'Partial'): ?>
                                        <div style="display: flex; gap: 2px; align-items: center;">
                                            <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)(isset($bill['remaining_amount']) ? $bill['remaining_amount'] : $bill['amount']), (float)$total_due)); ?>, '<?php echo htmlspecialchars($bill['title']); ?> for <?php echo htmlspecialchars($bill['period']); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>, '<?php echo addslashes($bill['period']); ?>')">
                                                <i class='bx bx-credit-card-alt'></i> Pay Now
                                            </button>
                                            <a href="payment-history.php?month=<?php echo urlencode($bill['period']); ?>" class="btn-view-receipt"><i class='bx bx-history'></i> History</a>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn-action-pay" onclick="openPaymentModal(<?php echo max(0, min((float)(isset($bill['remaining_amount']) ? $bill['remaining_amount'] : $bill['amount']), (float)$total_due)); ?>, '<?php echo htmlspecialchars($bill['title']); ?> for <?php echo htmlspecialchars($bill['period']); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>, '<?php echo addslashes($bill['period']); ?>')">
                                            <i class='bx bx-credit-card-alt'></i> Pay Now
                                        </button>
                                    <?php endif; ?>"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    content = content.replace(target, replacement)
    content = content.replace(target2, replacement2)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Updated {filepath} HTML buttons")
else:
    print(f"File not found: {filepath}")
