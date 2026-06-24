<?php
// admin/manage-recharges.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Ensure table exists
$createTable = "CREATE TABLE IF NOT EXISTS meter_recharges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10, 2) NOT NULL,
    recharge_date DATE NOT NULL,
    recharge_time TIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

$success = "";
$error = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM meter_recharges WHERE id = $id");
    $success = "Recharge record deleted.";
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recharge'])) {
    $amount = (float)$_POST['amount'];
    $date = $_POST['recharge_date'];
    $time = $_POST['recharge_time'];
    $notes = trim($_POST['notes'] ?? '');

    if ($amount <= 0 || !$date || !$time) {
        $error = "Please provide valid amount, date and time.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO meter_recharges (amount, recharge_date, recharge_time, notes) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "dsss", $amount, $date, $time, $notes);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Recharge added successfully!";
        } else {
            $error = "Error saving record.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all recharges
$res = mysqli_query($conn, "SELECT * FROM meter_recharges ORDER BY recharge_date DESC, recharge_time DESC");
$recharges = [];
while ($row = mysqli_fetch_assoc($res)) $recharges[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Meter Recharges | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        @media (max-width: 768px) {
            .welcome { text-align: center !important; margin-bottom: 30px !important; margin-top: 15px !important; }
            .welcome-title-row { flex-direction: column !important; align-items: center !important; gap: 12px !important; }
            .welcome h1 { font-size: 26px !important; }
            
            .dashboard-grid-70 { display: flex !important; flex-direction: column-reverse !important; gap: 24px !important; }
            .left-col, .right-col { width: 100% !important; }

            /* Card View for History */
            .table-responsive { border: none !important; }
            table, thead, tbody, th, td, tr { display: block !important; width: 100% !important; }
            thead { display: none !important; }
            tr { background: var(--bg-main); border-radius: 16px; padding: 16px; margin-bottom: 16px; border: 1px solid var(--border); }
            td { padding: 8px 0 !important; border: none !important; display: flex !important; justify-content: space-between !important; align-items: center !important; }
            td::before { content: attr(data-label); font-weight: 700; font-size: 13px; color: var(--text-gray); }
            td:last-child { border-top: 1px solid var(--border); margin-top: 8px; padding-top: 12px !important; }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <div class="welcome-title-row" style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
            <a href="electricity-list.php" class="btn-outline" style="padding: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px;"><i class='bx bx-arrow-back'></i></a>
            <h1 style="margin: 0;">Meter Recharges</h1>
        </div>
        <p>Log and track your electricity meter expenses</p>
    </div>

    <?php if ($success): ?>
        <div class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7;">
            <i class='bx bx-check-circle'></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FEE2E2;">
            <i class='bx bx-error-circle'></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid-70 animate-up">
        <div class="left-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Recharge History</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Amount</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recharges)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-gray);">No recharge records yet.</td></tr>
                            <?php else: foreach ($recharges as $r): ?>
                                <tr class="animate-up">
                                    <td data-label="Date & Time">
                                        <div style="font-weight: 600;"><?php echo date('M d, Y', strtotime($r['recharge_date'])); ?></div>
                                        <div style="font-size: 12px; color: var(--text-gray);"><?php echo date('h:i A', strtotime($r['recharge_time'])); ?></div>
                                    </td>
                                    <td data-label="Amount" style="font-weight: 700; color: #EF4444;">₹<?php echo number_format($r['amount'], 2); ?></td>
                                    <td data-label="Note" style="font-size: 13px; color: var(--text-gray);"><?php echo htmlspecialchars($r['notes']); ?></td>
                                    <td data-label="Actions">
                                        <a href="?delete=<?php echo $r['id']; ?>" class="btn-outline" style="color: #EF4444; border-color: rgba(239, 68, 68, 0.1); padding: 8px; border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;" onclick="return confirm('Delete this record?')">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="right-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Log New Recharge</h2>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Recharge Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" placeholder="0.00" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="recharge_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="recharge_time" value="<?php echo date('H:i'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="e.g. Done via GPAY"></textarea>
                    </div>
                    <button type="submit" name="add_recharge" class="btn-primary" style="width: 100%; justify-content: center;">
                        <i class='bx bx-plus'></i> Add Recharge
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>
