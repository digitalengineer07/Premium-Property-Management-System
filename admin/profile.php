<?php
// admin/profile.php - Admin's personal dashboard for tracking billing activity
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
$admin_id = (int)$_SESSION['admin_id'];

$success_msg = "";
$error_msg = "";

/* Handle Password Change */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pass'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT password FROM admin WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($admin && password_verify($current_pass, $admin['password'])) {
        if (strlen($new_pass) < 6) {
            $error_msg = "New password must be at least 6 characters.";
        } elseif ($new_pass !== $conf_pass) {
            $error_msg = "Passwords do not match.";
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = mysqli_prepare($conn, "UPDATE admin SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "si", $hashed, $admin_id);
            if (mysqli_stmt_execute($upd)) {
                $success_msg = "Password updated successfully!";
            } else {
                $error_msg = "Error updating password.";
            }
            mysqli_stmt_close($upd);
        }
    } else {
        $error_msg = "Current password is incorrect.";
    }
}

/* Fetch Stats */
$total_bills_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM electricity"));
$total_bills = $total_bills_res['count'];

$pending_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) as total FROM electricity WHERE status='Due'"));
$pending_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) as total FROM rent WHERE status='Due'"));
$total_pending = $pending_elec['total'] + $pending_rent['total'];

/* Recent Activity across all renters */
$recent_bills = mysqli_query($conn, "
    SELECT e.*, u.name as renter_name, u.room_no 
    FROM electricity e 
    JOIN users u ON e.user_id = u.id 
    ORDER BY e.created_at DESC 
    LIMIT 10
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Admin Profile | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1>Admin Control Panel</h1>
        <p>Consolidated overview of all property billing activity</p>
    </div>

    <div class="kpi-grid animate-up">
        <div class="kpi-card">
            <div class="kpi-header">
                <i class='bx bx-file kpi-icon'></i>
            </div>
            <div class="kpi-value"><?php echo number_format($total_bills); ?></div>
            <div class="kpi-label">Total Bills Generated</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header">
                <i class='bx bx-time-five kpi-icon' style="color: #F59E0B; background: rgba(245, 158, 11, 0.1);"></i>
            </div>
            <div class="kpi-value" style="color: #F59E0B;">₹<?php echo number_format($total_pending, 2); ?></div>
            <div class="kpi-label">Awaiting Collection</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-header">
                <i class='bx bx-user-pin kpi-icon' style="color: var(--primary-purple); background: rgba(98, 75, 255, 0.1);"></i>
            </div>
            <div class="kpi-value">System Ad.</div>
            <div class="kpi-label">Account Role</div>
        </div>
    </div>

    <div class="dashboard-grid-70 animate-up">
        <div class="left-col" style="width: 100%;">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Security Settings</h2>
                    <p style="font-size: 13px; color: var(--text-gray);">Update your administrative login credentials</p>
                </div>

                <?php if($success_msg): ?>
                    <div style="background: #F0FDF4; color: #10B981; padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 20px; border: 1px solid #DCFCE7;">
                        <i class='bx bx-check-circle'></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if($error_msg): ?>
                    <div style="background: #FEF2F2; color: #EF4444; padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 20px; border: 1px solid #FEE2E2;">
                        <i class='bx bx-error-circle'></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="max-width: 500px;">
                    <div class="form-group">
                        <label>Current Password</label>
                        <div style="position: relative;">
                            <input type="password" name="current_password" placeholder="••••••••" class="pwd-input" required style="padding-right: 40px;">
                            <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>New Password</label>
                            <div style="position: relative;">
                                <input type="password" name="new_password" placeholder="Min 6 chars" class="pwd-input" required style="padding-right: 40px;">
                                <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div style="position: relative;">
                                <input type="password" name="confirm_password" placeholder="Repeat new" class="pwd-input" required style="padding-right: 40px;">
                                <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 16px; top: 14px; color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="change_pass" class="btn-primary" style="margin-top: 10px; padding: 12px 24px;">
                        <i class='bx bx-lock-alt'></i> Update Password
                    </button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Recent Billing Activity</h2>
                    <div style="display: flex; gap: 8px;">
                        <span class="badge" style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple);">Live Log</span>
                    </div>
                </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Resident</th>
                        <th>Month</th>
                        <th>Amount</th>
                        <th>Generated On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($bill = mysqli_fetch_assoc($recent_bills)): ?>
                    <tr>
                        <td style="font-weight: 600;">
                            <?php echo htmlspecialchars($bill['renter_name']); ?>
                            <div style="font-size: 11px; font-weight: 400; color: var(--text-gray);">Room <?php echo htmlspecialchars($bill['room_no']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($bill['month']); ?></td>
                        <td style="font-weight: 700;">₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                        <td style="color: var(--text-gray); font-size: 14px;"><?php echo date('M d, H:i', strtotime($bill['created_at'])); ?></td>
                        <td>
                            <a href="slip.php?elec_id=<?php echo $bill['id']; ?>" class="btn-outline" style="padding: 6px 12px; font-size: 11px;" target="_blank">View Slip</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>



<script>
    document.querySelectorAll('.pwd-toggle').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if(input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            }
        });
    });
</script>
</body>
</html>
