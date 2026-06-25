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
$stmt = mysqli_prepare($conn, "SELECT p.*, a.username as admin_name FROM payments p LEFT JOIN admin a ON p.recorded_by = a.id WHERE p.user_id = ? ORDER BY p.id DESC");
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
            <div class="welcome" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: var(--text-dark); margin: 0;">Payment History</h1>
                    <p style="color: var(--text-gray); font-size: 14px; margin: 4px 0 0 0;">All recorded transactions for <?php echo htmlspecialchars($user['name']); ?> (Room <?php echo htmlspecialchars($user['room_no'] ?: 'N/A'); ?>)</p>
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
                                <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; gap: 16px; transition: border-color 0.2s;" onmouseover="this.style.borderColor='#CBD5E1'" onmouseout="this.style.borderColor='var(--border)'">
                                    
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
