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

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$totalRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM meter_recharges");
$totalRow = mysqli_fetch_assoc($totalRes);
$totalRecords = $totalRow['cnt'];
$totalPages = ceil($totalRecords / $limit);
if ($totalPages < 1) $totalPages = 1;

// Fetch recharges for current page
$res = mysqli_query($conn, "SELECT * FROM meter_recharges ORDER BY recharge_date DESC, recharge_time DESC LIMIT $limit OFFSET $offset");
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
            <a href="electricity-list.php" class="btn-outline" style="padding: 8px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 16px; margin-right: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #E2E8F0; background: #ffffff; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#F8FAFC'; this.style.borderColor='#CBD5E1'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#E2E8F0'; this.style.transform='none';"><i class='bx bx-arrow-back' style="font-size: 24px; color: #64748B;"></i></a>
            
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

    <div style="display: flex; flex-direction: column; gap: 20px; width: 100%;">
        
        <!-- TOP SECTION: Log New Recharge -->
        <div class="animate-up" style="animation-delay: 0.1s;">
            <div class="panel" style="background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 32px; border: 1px solid #F1F5F9;">
                <div class="panel-header" style="border-bottom: 1px solid #F1F5F9; padding-bottom: 20px; margin-bottom: 24px;">
                    <h2 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i class='bx bx-plus-circle' style="color: #F59E0B; font-size: 26px;"></i> Log New Recharge
                    </h2>
                </div>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
                        
                        <!-- Recharge Amount -->
                        <div class="form-group" style="margin: 0;">
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #64748B; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Recharge Amount (₹)</label>
                            <div style="position: relative;">
                                <i class='bx bx-rupee' style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 22px; color: #94A3B8;"></i>
                                <input type="number" step="0.01" name="amount" placeholder="0.00" required style="width: 100%; padding: 16px 16px 16px 48px; border-radius: 14px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 16px; font-weight: 600; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'; this.style.boxShadow='0 0 0 4px rgba(245, 158, 11, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'; this.style.boxShadow='none';">
                            </div>
                        </div>
                        
                        <!-- Date & Time -->
                        <div class="form-group" style="margin: 0;">
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #64748B; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Date & Time</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div style="position: relative;">
                                    <input type="date" name="recharge_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 16px 16px 16px 16px; border-radius: 14px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 15px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease; cursor: pointer;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'; this.style.boxShadow='0 0 0 4px rgba(245, 158, 11, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'; this.style.boxShadow='none';">
                                </div>
                                <div style="position: relative;">
                                    <input type="time" name="recharge_time" value="<?php echo date('H:i'); ?>" required style="width: 100%; padding: 16px 16px 16px 16px; border-radius: 14px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 15px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease; cursor: pointer;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'; this.style.boxShadow='0 0 0 4px rgba(245, 158, 11, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'; this.style.boxShadow='none';">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes (Full Width) -->
                        <div class="form-group" style="margin: 0; grid-column: 1 / -1;">
                            <label style="display: block; font-size: 13px; font-weight: 700; color: #64748B; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Notes (Optional)</label>
                            <div style="position: relative;">
                                <i class='bx bx-note' style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 22px; color: #94A3B8;"></i>
                                <input type="text" name="notes" placeholder="e.g. Done via GPAY" style="width: 100%; padding: 16px 16px 16px 48px; border-radius: 14px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 15px; font-weight: 500; color: #1E293B; outline: none; transition: all 0.2s ease;" onfocus="this.style.borderColor='#F59E0B'; this.style.background='#fff'; this.style.boxShadow='0 0 0 4px rgba(245, 158, 11, 0.1)';" onblur="this.style.borderColor='#E2E8F0'; this.style.background='#F8FAFC'; this.style.boxShadow='none';">
                            </div>
                        </div>
                        
                    </div>
                    
                    <div style="margin-top: 32px; display: flex; justify-content: flex-end; border-top: 1px solid #F1F5F9; padding-top: 24px;">
                        <button type="submit" name="add_recharge" class="btn-primary" style="background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); border: none; padding: 16px 40px; font-size: 16px; font-weight: 700; border-radius: 14px; box-shadow: 0 8px 16px rgba(245, 158, 11, 0.25); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;">
                            <i class='bx bx-plus-circle' style="font-size: 22px;"></i> Add Recharge
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- BOTTOM SECTION: Recharge History -->
        <div id="history" class="animate-up" style="animation-delay: 0.2s;">
            <div class="panel" style="background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 32px; border: 1px solid #F1F5F9;">
                <div class="panel-header" style="border-bottom: 1px solid #F1F5F9; padding-bottom: 20px; margin-bottom: 24px;">
                    <h2 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 10px;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(100,116,139,0.1); display: flex; align-items: center; justify-content: center; color: #64748B;"><i class='bx bx-history' style="font-size: 24px;"></i></div> Recharge History
                    </h2>
                </div>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px;">
                        <thead>
                            <tr>
                                <th style="padding: 0 24px 12px 24px; font-size: 13px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #F1F5F9; text-align: left;">Date & Time</th>
                                <th style="padding: 0 24px 12px 24px; font-size: 13px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #F1F5F9; text-align: left;">Amount</th>
                                <th style="padding: 0 24px 12px 24px; font-size: 13px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #F1F5F9; text-align: left;">Notes</th>
                                <th style="padding: 0 24px 12px 24px; font-size: 13px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #F1F5F9; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recharges)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 60px; color: #94A3B8; font-size: 16px; background: #F8FAFC; border-radius: 16px; border: 1px dashed #CBD5E1;">No recharge records found. Try logging a new one!</td></tr>
                            <?php else: foreach ($recharges as $r): ?>
                                <tr class="animate-up" style="transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.01);" onmouseover="this.style.boxShadow='0 10px 25px rgba(0,0,0,0.05)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.01)'; this.style.transform='none';">
                                    <td data-label="Date & Time" style="padding: 24px; background: #fff; border: 1px solid #F1F5F9; border-right: none; border-radius: 16px 0 0 16px;">
                                        <div style="font-weight: 800; color: #1E293B; font-size: 16px;"><?php echo date('M d, Y', strtotime($r['recharge_date'])); ?></div>
                                        <div style="font-size: 13px; color: #64748B; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px;"><i class='bx bx-time-five' style="font-size: 15px;"></i> <?php echo date('h:i A', strtotime($r['recharge_time'])); ?></div>
                                    </td>
                                    <td data-label="Amount" style="padding: 24px; background: #fff; border-top: 1px solid #F1F5F9; border-bottom: 1px solid #F1F5F9;">
                                        <span style="background: #FEF2F2; color: #EF4444; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 16px; border: 1px solid #FEE2E2; display: inline-block;">
                                            - ₹<?php echo number_format($r['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td data-label="Note" style="padding: 24px; background: #fff; border-top: 1px solid #F1F5F9; border-bottom: 1px solid #F1F5F9;">
                                        <div style="font-size: 15px; color: #475569; max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500;" title="<?php echo htmlspecialchars($r['notes']); ?>">
                                            <?php echo htmlspecialchars($r['notes']) ?: '<span style="color: #CBD5E1; font-style: italic;">No notes provided</span>'; ?>
                                        </div>
                                    </td>
                                    <td data-label="Actions" style="padding: 24px; background: #fff; border: 1px solid #F1F5F9; border-left: none; border-radius: 0 16px 16px 0; text-align: right;">
                                        <a href="?delete=<?php echo $r['id']; ?>" class="btn-outline" style="color: #EF4444; border-color: rgba(239, 68, 68, 0.2); background: #FEF2F2; padding: 10px; border-radius: 12px; width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; margin-left: auto;" onclick="return confirm('Are you sure you want to delete this record?')" onmouseover="this.style.background='#FEE2E2'; this.style.transform='scale(1.05)';" onmouseout="this.style.background='#FEF2F2'; this.style.transform='scale(1)';">
                                            <i class='bx bx-trash' style="font-size: 20px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 16px; padding-top: 32px; border-top: 1px solid #F1F5F9;">
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>#history" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #64748B; border: 1px solid #E2E8F0; text-decoration: none; transition: all 0.2s ease; background: #fff;" onmouseover="this.style.background='#F8FAFC'; this.style.borderColor='#CBD5E1'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#fff'; this.style.borderColor='#E2E8F0'; this.style.transform='none';">
                            <i class='bx bx-chevron-left' style="font-size: 22px;"></i>
                        </a>
                    <?php else: ?>
                        <div style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #94A3B8; border: 1px solid #E2E8F0; background: #F8FAFC; cursor: not-allowed; opacity: 0.6;">
                            <i class='bx bx-chevron-left' style="font-size: 22px;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; color: #ffffff; background: var(--primary-purple, #624BFF); box-shadow: 0 8px 16px rgba(98, 75, 255, 0.3);">
                        <?php echo $page; ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>#history" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #64748B; border: 1px solid #E2E8F0; text-decoration: none; transition: all 0.2s ease; background: #fff;" onmouseover="this.style.background='#F8FAFC'; this.style.borderColor='#CBD5E1'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#fff'; this.style.borderColor='#E2E8F0'; this.style.transform='none';">
                            <i class='bx bx-chevron-right' style="font-size: 22px;"></i>
                        </a>
                    <?php else: ?>
                        <div style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #94A3B8; border: 1px solid #E2E8F0; background: #F8FAFC; cursor: not-allowed; opacity: 0.6;">
                            <i class='bx bx-chevron-right' style="font-size: 22px;"></i>
                        </div>
                    <?php endif; ?>
                    
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

</body>
</html>
