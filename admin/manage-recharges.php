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

    <div class="welcome animate-up" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 32px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <a href="electricity-list.php" class="btn-outline" style="padding: 8px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-right: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-color: #E2E8F0;"><i class='bx bx-arrow-back' style="font-size: 20px; color: #64748B;"></i></a>
            
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 12px 24px rgba(245, 158, 11, 0.25);">
                <i class='bx bx-receipt' style="color: white; font-size: 30px;"></i>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 30px; font-weight: 800; color: #0F172A; letter-spacing: -0.5px; line-height: 1.2;">Meter Recharges</h1>
                <p style="margin: 6px 0 0 0; color: #64748B; font-size: 15px; font-weight: 500;">Log and track your electricity meter expenses</p>
            </div>
        </div>
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

    <div class="dashboard-grid-70 animate-up" style="gap: 32px;">
        <div class="left-col">
            <div class="panel" style="background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 24px; border: 1px solid #F1F5F9;">
                <div class="panel-header" style="border-bottom: 1px solid #F1F5F9; padding-bottom: 16px; margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class='bx bx-history' style="color: #64748B;"></i> Recharge History
                    </h2>
                </div>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                        <thead>
                            <tr>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #E2E8F0; text-align: left;">Date & Time</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #E2E8F0; text-align: left;">Amount</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #E2E8F0; text-align: left;">Notes</th>
                                <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #E2E8F0; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recharges)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 40px; color: #94A3B8; font-size: 15px; background: #F8FAFC; border-radius: 12px;">No recharge records yet.</td></tr>
                            <?php else: foreach ($recharges as $r): ?>
                                <tr class="animate-up" style="transition: all 0.2s ease;">
                                    <td data-label="Date & Time" style="padding: 16px; background: #fff; border-bottom: 1px solid #F1F5F9;">
                                        <div style="font-weight: 700; color: #1E293B; font-size: 15px;"><?php echo date('M d, Y', strtotime($r['recharge_date'])); ?></div>
                                        <div style="font-size: 13px; color: #64748B; font-weight: 500; margin-top: 2px;"><i class='bx bx-time-five' style="vertical-align: middle;"></i> <?php echo date('h:i A', strtotime($r['recharge_time'])); ?></div>
                                    </td>
                                    <td data-label="Amount" style="padding: 16px; background: #fff; border-bottom: 1px solid #F1F5F9;">
                                        <span style="background: #FEF2F2; color: #EF4444; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 14px; border: 1px solid #FEE2E2; display: inline-block;">
                                            - ₹<?php echo number_format($r['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td data-label="Note" style="padding: 16px; background: #fff; border-bottom: 1px solid #F1F5F9;">
                                        <div style="font-size: 14px; color: #475569; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($r['notes']); ?>">
                                            <?php echo htmlspecialchars($r['notes']) ?: '<span style="color: #CBD5E1; font-style: italic;">No notes</span>'; ?>
                                        </div>
                                    </td>
                                    <td data-label="Actions" style="padding: 16px; background: #fff; border-bottom: 1px solid #F1F5F9; text-align: right;">
                                        <a href="?delete=<?php echo $r['id']; ?>" class="btn-outline" style="color: #EF4444; border-color: rgba(239, 68, 68, 0.2); background: #FEF2F2; padding: 8px; border-radius: 10px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; margin-left: auto;" onclick="return confirm('Delete this record?')" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                                            <i class='bx bx-trash' style="font-size: 18px;"></i>
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
            <div class="panel" style="background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 24px; border: 1px solid #F1F5F9; position: sticky; top: 24px;">
                <div class="panel-header" style="border-bottom: 1px solid #F1F5F9; padding-bottom: 16px; margin-bottom: 24px;">
                    <h2 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class='bx bx-plus-circle' style="color: #F59E0B;"></i> Log New Recharge
                    </h2>
                </div>
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Recharge Amount (₹)</label>
                        <div style="position: relative;">
                            <i class='bx bx-rupee' style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 20px; color: #94A3B8;"></i>
                            <input type="number" step="0.01" name="amount" placeholder="0.00" required style="width: 100%; padding: 14px 14px 14px 40px; border-radius: 12px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 15px; font-weight: 600; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Date</label>
                            <input type="date" name="recharge_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease; cursor: pointer;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'">
                        </div>
                        <div class="form-group">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Time</label>
                            <input type="time" name="recharge_time" value="<?php echo date('H:i'); ?>" required style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 14px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease; cursor: pointer;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="e.g. Done via GPAY" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 14px; color: #1E293B; outline: none; transition: all 0.2s ease; resize: none; font-family: inherit;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'"></textarea>
                    </div>
                    
                    <button type="submit" name="add_recharge" class="btn-primary" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); border: none; padding: 16px; font-size: 16px; font-weight: 700; border-radius: 14px; box-shadow: 0 8px 16px rgba(245, 158, 11, 0.2); transition: all 0.3s ease;">
                        <i class='bx bx-plus-circle' style="font-size: 22px;"></i> Add Recharge
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>
