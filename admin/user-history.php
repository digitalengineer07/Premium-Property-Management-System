<?php
// admin/user-history.php - Consolidated Rent & Electricity History
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "Invalid user id";
    exit;
}

// Fetch user details
$stmt = mysqli_prepare($conn, "SELECT id, username, name, room_no FROM users WHERE id = ?");
if (!$stmt) {
    die("Database Query Failed!");
}
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$user_res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_res);
mysqli_stmt_close($stmt);

if (!$user) {
    echo "User not found";
    exit;
}

// Fetch all consolidated history from electricity table
$stmt = mysqli_prepare($conn, "
    SELECT e.*, 
           (SELECT SUM(paid_amount) FROM payments WHERE bill_type = 'electricity' AND bill_id = e.id) as total_paid
    FROM electricity e 
    WHERE e.user_id = ? 
    ORDER BY e.id DESC
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$history_res = mysqli_stmt_get_result($stmt);
$history = [];
$total_billed = 0;
$total_paid_overall = 0;

while ($r = mysqli_fetch_assoc($history_res)) {
    $history[] = $r;
    $total_billed += $r['total_amount'];
    $total_paid_overall += $r['total_paid'];
}
mysqli_stmt_close($stmt);

$total_outstanding = max(0, $total_billed - $total_paid_overall);

// CSRF Token
function getCsrfToken() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
$admin_user = htmlspecialchars($_SESSION['admin'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Total History | <?php echo htmlspecialchars($user['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>
    <?php include "sidebar.php"; ?>
    <?php include 'header.php'; ?>

    <main class="main" style="padding: 24px;">
        <div class="welcome" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 24px; font-weight: 800; color: var(--text-dark); margin: 0;">Comprehensive History</h1>
                <p style="color: var(--text-gray); font-size: 14px; margin: 4px 0 0 0;">All rent and utility bills for <strong><?php echo htmlspecialchars($user['name']); ?></strong> (Room <?php echo htmlspecialchars($user['room_no']); ?>)</p>
            </div>
            <a href="view-renter.php?id=<?php echo $id; ?>" class="btn-outline" style="text-decoration: none;"><i class='bx bx-arrow-back'></i> Back to Profile</a>
        </div>

        <!-- Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 24px;">
            <div class="panel animate-up" style="display: flex; align-items: center; gap: 16px; padding: 24px;">
                <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(98, 75, 255, 0.1); display: flex; align-items: center; justify-content: center;">
                    <i class='bx bx-receipt' style="font-size: 28px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <p style="font-size: 13px; font-weight: 600; color: var(--text-gray); text-transform: uppercase; margin: 0 0 4px 0;">Total Billed</p>
                    <h3 style="font-size: 24px; font-weight: 800; color: var(--text-dark); margin: 0;">₹<?php echo number_format($total_billed, 2); ?></h3>
                </div>
            </div>

            <div class="panel animate-up" style="display: flex; align-items: center; gap: 16px; padding: 24px; animation-delay: 0.1s;">
                <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center;">
                    <i class='bx bx-check-shield' style="font-size: 28px; color: #10B981;"></i>
                </div>
                <div>
                    <p style="font-size: 13px; font-weight: 600; color: var(--text-gray); text-transform: uppercase; margin: 0 0 4px 0;">Total Paid</p>
                    <h3 style="font-size: 24px; font-weight: 800; color: var(--text-dark); margin: 0;">₹<?php echo number_format($total_paid_overall, 2); ?></h3>
                </div>
            </div>

            <div class="panel animate-up" style="display: flex; align-items: center; gap: 16px; padding: 24px; animation-delay: 0.2s;">
                <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center;">
                    <i class='bx bx-wallet' style="font-size: 28px; color: #EF4444;"></i>
                </div>
                <div>
                    <p style="font-size: 13px; font-weight: 600; color: var(--text-gray); text-transform: uppercase; margin: 0 0 4px 0;">Total Outstanding</p>
                    <h3 style="font-size: 24px; font-weight: 800; color: <?php echo $total_outstanding > 0 ? '#EF4444' : 'var(--text-dark)'; ?>; margin: 0;">₹<?php echo number_format($total_outstanding, 2); ?></h3>
                </div>
            </div>
        </div>

        <!-- Comprehensive Table -->
        <div class="panel animate-up" style="animation-delay: 0.3s;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="font-size: 16px; color: var(--text-dark); margin: 0; font-weight: 700;">Billing History</h4>
                <span style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;"><?php echo count($history); ?> Bills Found</span>
            </div>
            
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border);">
                            <th style="padding: 16px 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase;">Month</th>
                            <th style="padding: 16px 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase;">Rent & Maint</th>
                            <th style="padding: 16px 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase;">Electricity</th>
                            <th style="padding: 16px 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase;">Extras & Dues</th>
                            <th style="padding: 16px 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase;">Total Billed</th>
                            <th style="padding: 16px 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase;">Status</th>
                            <th style="padding: 16px 12px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-gray); text-transform: uppercase;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history)): ?>
                            <tr><td colspan="7" style="padding: 30px; text-align: center; color: var(--text-gray);">No billing history found.</td></tr>
                        <?php else: ?>
                            <?php foreach($history as $h): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 16px 12px; font-weight: 700; font-size: 14px; color: var(--text-dark);"><?php echo htmlspecialchars($h['month']); ?></td>
                                <td style="padding: 16px 12px; font-size: 13px; color: var(--text-gray);">
                                    Rent: ₹<?php echo number_format($h['rent_amount'], 2); ?><br>
                                    Maint: ₹<?php echo number_format($h['maintenance'], 2); ?>
                                </td>
                                <td style="padding: 16px 12px; font-size: 13px; color: var(--text-gray);">
                                    <?php echo htmlspecialchars($h['units_consumed'] ?? ($h['current_reading'] - $h['previous_reading'])); ?> Units<br>
                                    ₹<?php echo number_format($h['amount'], 2); ?>
                                </td>
                                <td style="padding: 16px 12px; font-size: 13px; color: var(--text-gray);">
                                    Extras: ₹<?php echo number_format($h['extra_charges'], 2); ?><br>
                                    Dues: ₹<?php echo number_format($h['dues'], 2); ?>
                                </td>
                                <td style="padding: 16px 12px; font-weight: 800; font-size: 14px; color: var(--text-dark);">
                                    ₹<?php echo number_format($h['total_amount'], 2); ?>
                                </td>
                                <td style="padding: 16px 12px;">
                                    <span style="font-size: 11px; font-weight: 700; padding: 6px 10px; border-radius: 6px; 
                                        <?php echo $h['status'] == 'Paid' ? 'color: #065F46; background: #ECFDF5; border: 1px solid #A7F3D0;' : 
                                        ($h['status'] == 'Partial' ? 'color: #92400E; background: #FEF3C7; border: 1px solid #FDE68A;' : 
                                        'color: #991B1B; background: #FEF2F2; border: 1px solid #FECACA;'); ?>">
                                        <?php echo $h['status']; ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 12px;">
                                    <?php if($h['status'] != 'Paid'): $remaining = max(0, $h['total_amount'] - $h['total_paid']); ?>
                                        <button onclick="openPaymentModal('electricity', <?php echo $h['id']; ?>, <?php echo $remaining; ?>, '<?php echo addslashes($h['month']); ?>')" class="btn-primary" style="padding: 6px 14px; border-radius: 6px; font-size: 12px;">Pay</button>
                                    <?php else: ?>
                                        <a href="slip.php?elec_id=<?php echo $h['id']; ?>" target="_blank" class="btn-outline" style="padding: 6px 14px; border-radius: 6px; font-size: 12px; text-decoration: none;">Slip</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Payment Mode Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 650px; width: 100%; padding: 32px; background: #FFFFFF; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-radius: 20px;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #E2E8F0; padding-bottom: 16px;">
                <div style="width: 48px; height: 48px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class='bx bx-credit-card' style="font-size: 24px; color: #10B981;"></i>
                </div>
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #1E293B; margin: 0;">Record Payment</h3>
                    <p id="paymentBillInfo" style="color: #64748B; font-size: 13px; margin: 4px 0 0 0;">Select payment method and amount.</p>
                </div>
            </div>
            
            <form action="mark-paid.php" method="POST">
                <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                <input type="hidden" name="id" id="paymentBillId">
                <input type="hidden" name="type" id="paymentBillType">
                <input type="hidden" name="bill_amount" id="paymentBillAmount">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Payment Mode</label>
                            <select name="payment_mode" id="paymentMode" required onchange="handlePaymentModeChange()" style="width: 100%; padding: 12px 16px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; font-weight: 500; color: #334155; background: #F8FAFC; transition: all 0.2s; outline: none;">
                                <option value="Online">Online</option>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Amount Paid (₹)</label>
                            <input type="number" step="0.01" name="paid_amount" id="paidAmountInput" placeholder="Enter amount" required style="width: 100%; padding: 12px 16px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 15px; font-weight: 600; color: #334155; background: #F8FAFC; transition: all 0.2s; outline: none;">
                            <small style="color: #94A3B8; font-size: 12px; display: block; margin-top: 6px;">Partial payments are allowed.</small>
                        </div>
                    </div>
                    
                    <div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Date</label>
                                <input type="date" name="payment_date" id="paymentDateInput" required style="width: 100%; padding: 11px 12px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; font-weight: 500; color: #334155; background: #F8FAFC; outline: none;">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Time</label>
                                <input type="time" name="payment_time" id="paymentTimeInput" required style="width: 100%; padding: 11px 12px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; font-weight: 500; color: #334155; background: #F8FAFC; outline: none;">
                            </div>
                        </div>

                        <div class="form-group" id="cashReceiverGroup" style="display: none; margin-bottom: 20px;">
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; margin-bottom: 8px; display: block;">Cash Received By</label>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 11px 16px; border: 1px solid #E2E8F0; border-radius: 10px; background: #F1F5F9;">
                                <i class='bx bx-user' style="color: #6366F1; font-size: 18px;"></i>
                                <span style="font-size: 15px; font-weight: 600; color: #0F172A;"><?php echo $admin_user; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 16px; margin-top: 12px; padding-top: 24px; border-top: 1px solid #E2E8F0;">
                    <button type="button" onclick="closePaymentModal()" class="btn-outline" style="padding: 12px 28px; border-radius: 10px; font-weight: 600; color: #64748B; border-color: #CBD5E1;">Cancel</button>
                    <button type="submit" class="btn-primary" style="background: #10B981; padding: 12px 28px; border-radius: 10px; font-weight: 600; border: none;">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentModal(type, id, amount, month) {
            document.getElementById('paymentBillId').value = id;
            document.getElementById('paymentBillType').value = type;
            document.getElementById('paymentBillAmount').value = amount;
            document.getElementById('paidAmountInput').value = amount;
            document.getElementById('paymentBillInfo').textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} Bill for ${month} (₹${amount})`;
            
            const now = new Date();
            document.getElementById('paymentDateInput').value = now.toISOString().split('T')[0];
            document.getElementById('paymentTimeInput').value = now.toTimeString().slice(0, 5);
            
            document.getElementById('paymentModal').style.display = 'flex';
            handlePaymentModeChange();
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        function handlePaymentModeChange() {
            const mode = document.getElementById('paymentMode').value;
            const receiverGroup = document.getElementById('cashReceiverGroup');
            if(receiverGroup) {
                receiverGroup.style.display = (mode === 'Cash') ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>
