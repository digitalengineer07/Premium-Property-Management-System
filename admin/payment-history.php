<?php
// admin/payment-history.php - View specific renter's payment history
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

/* Fetch detailed payment history log */
$stmt = mysqli_prepare($conn, "SELECT p.*, a.username as admin_name, e.amount as elec_amount, e.rent_amount, e.maintenance, e.dues as past_dues, e.extra_charges, e.extra_charges_desc FROM payments p LEFT JOIN admin a ON p.recorded_by = a.id LEFT JOIN electricity e ON p.bill_id = e.id AND p.bill_type = 'electricity' WHERE p.user_id = ? ORDER BY p.id DESC");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$payment_res = mysqli_stmt_get_result($stmt);
$payment_history = [];
while ($r = mysqli_fetch_assoc($payment_res)) $payment_history[] = $r;
mysqli_stmt_close($stmt);

$total_paid = array_sum(array_column($payment_history, 'paid_amount'));
$admin_user = htmlspecialchars($_SESSION['admin'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Payment History - <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>
    <?php include "sidebar.php"; ?>

    <main class="main">
        <?php include 'header.php'; ?>
        
        <div style="padding: 24px;">
            <div class="welcome" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 28px; box-shadow: inset 0 0 0 1px rgba(98, 75, 255, 0.1);">
                        <i class='bx bx-credit-card-front'></i>
                    </div>
                    <div>
                        <h1 style="font-size: 26px; font-weight: 900; background: linear-gradient(135deg, var(--primary-purple), #10B981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; display: inline-block;">Payment History</h1>
                        <p style="color: var(--text-gray); font-size: 14px; margin: 6px 0 0 0; font-weight: 500; display: flex; align-items: center; gap: 6px;"><i class='bx bx-user-circle' style="font-size: 16px; color: #10B981;"></i> All transactions for <span style="color: var(--text-dark); font-weight: 700;"><?php echo htmlspecialchars($user['name']); ?></span> (Room <?php echo htmlspecialchars($user['room_no'] ?: 'N/A'); ?>)</p>
                    </div>
                </div>
                <a href="view-renter.php?id=<?php echo $user['id']; ?>" class="btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class='bx bx-arrow-back'></i> Back to Profile
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--border); border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(98,75,255,0.1); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 20px;"><i class='bx bx-history'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">Total Transactions</div>
                            <div style="color: var(--text-gray); font-size: 12px; font-weight: 500; margin-top: 2px;">Recorded historically</div>
                        </div>
                    </div>
                    <div style="font-weight: 800; font-size: 20px; color: var(--primary-purple);"><?php echo count($payment_history); ?></div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--border); border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center; color: #10B981; font-size: 20px;"><i class='bx bx-check-shield'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">Total Amount Paid</div>
                            <div style="color: var(--text-gray); font-size: 12px; font-weight: 500; margin-top: 2px;">Sum of all transactions</div>
                        </div>
                    </div>
                    <div style="font-weight: 800; font-size: 20px; color: #10B981;">₹<?php echo number_format($total_paid, 2); ?></div>
                </div>
            </div>

            <div class="panel animate-up">
                <?php if (empty($payment_history)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-gray); font-size: 14px;">
                        <i class='bx bx-receipt' style="font-size: 48px; color: var(--border); margin-bottom: 12px; display: block;"></i>
                        No payments recorded yet.
                    </div>
                <?php else: ?>
                    <div style="position: relative; padding-left: 32px;">
                        <div style="position: absolute; top: 12px; bottom: 12px; left: 6px; width: 2px; background: #E2E8F0; z-index: 1;"></div>
                        <?php foreach ($payment_history as $p): ?>
                            <div style="position: relative; z-index: 2; margin-bottom: 24px;">
                                <div style="position: absolute; left: -32px; top: 14px; width: 14px; height: 14px; border-radius: 50%; background: #10B981; border: 3px solid #fff; box-shadow: 0 0 0 1px #10B981;"></div>
                                <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: border-color 0.2s;" onmouseover="this.style.borderColor='#CBD5E1'" onmouseout="this.style.borderColor='var(--border)'">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <div style="font-weight: 800; color: var(--text-dark); font-size: 20px;">₹<?php echo number_format($p['paid_amount'], 2); ?></div>
                                            <div style="font-size: 13px; color: var(--text-gray); font-weight: 500;">
                                                <span style="color: var(--text-dark); font-weight: 600; text-transform: capitalize;"><?php echo htmlspecialchars($p['bill_type']); ?></span> Bill • <?php echo htmlspecialchars($p['month'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        
                                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                            <span style="background: rgba(16,185,129,0.1); color: #10B981; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><i class='bx bx-check-circle' style="font-size: 14px;"></i> <?php echo htmlspecialchars($p['payment_mode']); ?></span>
                                            <div style="font-size: 12px; color: var(--text-gray); display: flex; align-items: center; gap: 8px;">
                                                <span style="display: flex; align-items: center; gap: 4px;"><i class='bx bx-calendar'></i> <?php echo date('d M Y, h:i A', strtotime($p['payment_date'] . ' ' . $p['payment_time'])); ?></span>
                                                <?php if ($p['admin_name']): ?>
                                                    <span style="color: #E2E8F0;">|</span>
                                                    <span style="display: flex; align-items: center; gap: 4px; font-weight: 500;"><i class='bx bx-user'></i> <?php echo htmlspecialchars($p['admin_name']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($p['bill_type'] === 'electricity' && (isset($p['elec_amount']) || isset($p['rent_amount']))): ?>
                                        <div style="margin-top: 16px; background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #E2E8F0;">
                                            <div style="font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">Bill Breakdown</div>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px; color: var(--text-dark);">
                                                <?php if ($p['rent_amount'] > 0): ?>
                                                    <div style="display: flex; justify-content: space-between;"><span>Rent:</span> <span style="font-weight: 600;">₹<?php echo number_format($p['rent_amount'], 2); ?></span></div>
                                                <?php endif; ?>
                                                <?php if ($p['elec_amount'] > 0): ?>
                                                    <div style="display: flex; justify-content: space-between;"><span>Electricity:</span> <span style="font-weight: 600;">₹<?php echo number_format($p['elec_amount'], 2); ?></span></div>
                                                <?php endif; ?>
                                                <?php if ($p['maintenance'] > 0): ?>
                                                    <div style="display: flex; justify-content: space-between;"><span>Maintenance:</span> <span style="font-weight: 600;">₹<?php echo number_format($p['maintenance'], 2); ?></span></div>
                                                <?php endif; ?>
                                                <?php if ($p['past_dues'] > 0): ?>
                                                    <div style="display: flex; justify-content: space-between;"><span>Past Dues:</span> <span style="font-weight: 600;">₹<?php echo number_format($p['past_dues'], 2); ?></span></div>
                                                <?php endif; ?>
                                                <?php if ($p['extra_charges'] > 0): ?>
                                                    <div style="display: flex; justify-content: space-between;"><span>Extra (<?php echo htmlspecialchars($p['extra_charges_desc'] ?? 'Charge'); ?>):</span> <span style="font-weight: 600;">₹<?php echo number_format($p['extra_charges'], 2); ?></span></div>
                                                <?php endif; ?>
                                                <?php if ($p['adjustment_amount'] > 0): ?>
                                                    <div style="display: flex; justify-content: space-between;">
                                                        <span>Adjustment (<?php echo htmlspecialchars($p['adjustment_type'] ?? 'discount'); ?>):</span>
                                                        <span style="font-weight: 600; color: #EF4444;">-₹<?php echo number_format($p['adjustment_amount'], 2); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
