<?php
// admin/add-electricity.php - Unified SaaS UI
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    header("Location: manage-renters.php");
    exit;
}

// Fetch renter info
$u_stmt = mysqli_prepare($conn, "SELECT name, room_no FROM users WHERE id = ?");
mysqli_stmt_bind_param($u_stmt, "i", $user_id);
mysqli_stmt_execute($u_stmt);
$u_res = mysqli_stmt_get_result($u_stmt);
$user = mysqli_fetch_assoc($u_res);
mysqli_stmt_close($u_stmt);

if (!$user) {
    header("Location: manage-renters.php");
    exit;
}

if (isset($_POST['save'])) {
    $month = $_POST['month'];
    $units = $_POST['units'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];

    $p_date = DateTime::createFromFormat('F Y', $month) ?: DateTime::createFromFormat('Y-m', $month);
    if ($p_date && (int)$p_date->format('Ym') > (int)date('Ym')) {
        echo "<script>alert('Protocol Violation: Cannot generate electricity bill for upcoming/future months beyond current date.'); history.back();</script>";
        exit;
    }

    $i_stmt = mysqli_prepare($conn, "INSERT INTO electricity (user_id, month, units, amount, status) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($i_stmt, "isiss", $user_id, $month, $units, $amount, $status);
    mysqli_stmt_execute($i_stmt);
    mysqli_stmt_close($i_stmt);

    header("Location: view-renter.php?id=$user_id");
    exit;
}

$elec = mysqli_query($conn, "SELECT * FROM electricity WHERE user_id = $user_id ORDER BY id DESC LIMIT 5");
$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Add Electricity Bill | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1>New Electricity Bill</h1>
        <p>Record utility usage for <?php echo htmlspecialchars($user['name']); ?></p>
    </div>

    <div class="dashboard-grid-70 animate-up" style="margin-top: 30px;">
        <div class="left-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Bill Details</h2>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Bill For Month <span style="color:#EF4444">*</span></span>
                            <span style="background: #EEF2FF; color: #4F46E5; font-size: 11px; padding: 2px 8px; border-radius: 6px; font-weight: 700;">Consumption Period</span>
                        </label>
                        <input type="text" name="month" required placeholder="February 2026">
                        <small style="color: var(--text-gray); font-size: 11px;">The consumption month for which this bill applies.</small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;" class="responsive-grid">
                        <div class="form-group">
                            <label>Units Consumed</label>
                            <input type="number" name="units" required placeholder="0">
                        </div>

                        <div class="form-group">
                            <label>Total Amount (₹)</label>
                            <input type="number" name="amount" required placeholder="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Due">Due</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>

                    <button type="submit" name="save" class="btn-primary" style="width: 100%; justify-content: center; padding: 15px; margin-top: 10px;">
                        <i class='bx bx-save'></i> Save Bill Record
                    </button>
                </form>
            </div>
        </div>

        <div class="right-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Recent History</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Amt</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($elec) == 0): ?>
                                <tr><td colspan="3" style="text-align:center; padding: 20px; color: var(--text-gray);">No logs.</td></tr>
                            <?php else: while ($e = mysqli_fetch_assoc($elec)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($e['month']); ?></td>
                                    <td style="font-weight: 600;">₹<?php echo number_format($e['amount']); ?></td>
                                    <td><span class="badge <?php echo $e['status'] == 'Paid' ? 'badge-paid' : 'badge-due'; ?>"><?php echo $e['status']; ?></span></td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
