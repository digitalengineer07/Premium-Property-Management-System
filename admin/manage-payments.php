<?php
// admin/manage-payments.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Handle Add Manual Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual_payment'])) {
    // Basic CSRF validation could be added here
    $user_id = (int)$_POST['user_id'];
    $bill_type = mysqli_real_escape_string($conn, $_POST['bill_type']);
    $month = mysqli_real_escape_string($conn, $_POST['month']);
    $amount = (float)$_POST['amount'];
    $payment_mode = mysqli_real_escape_string($conn, $_POST['payment_mode']);
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $payment_date = date('Y-m-d');
    $payment_time = date('H:i:s');
    $admin_name = $_SESSION['admin'];
    
    $sys_tx_id = uniqid('sys_');
    $vhash = generate_payment_hash($user_id, $amount, $sys_tx_id);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, bill_type, month, total_amount, paid_amount, payment_mode, payment_date, payment_time, transaction_id, admin_name, sys_tx_id, verification_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issddsssssss", $user_id, $bill_type, $month, $amount, $amount, $payment_mode, $payment_date, $payment_time, $transaction_id, $admin_name, $sys_tx_id, $vhash);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Manual payment recorded successfully!";
    } else {
        $error_msg = "Failed to record payment.";
    }
    mysqli_stmt_close($stmt);
}

// Handle Delete Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment_id'])) {
    $del_id = (int)$_POST['delete_payment_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM payments WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $del_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Payment deleted successfully.";
    } else {
        $error_msg = "Failed to delete payment.";
    }
    mysqli_stmt_close($stmt);
}

// Filters
$filter_user = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
$filter_mode = isset($_GET['payment_mode']) && $_GET['payment_mode'] !== '' ? mysqli_real_escape_string($conn, $_GET['payment_mode']) : null;
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? mysqli_real_escape_string($conn, $_GET['date_from']) : null;
$filter_date_to = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? mysqli_real_escape_string($conn, $_GET['date_to']) : null;

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_clauses = ["1=1"];
if ($filter_user) $where_clauses[] = "p.user_id = $filter_user";
if ($filter_mode) $where_clauses[] = "p.payment_mode = '$filter_mode'";
if ($filter_search) $where_clauses[] = "(p.transaction_id LIKE '%$filter_search%' OR u.name LIKE '%$filter_search%' OR p.sys_tx_id LIKE '%$filter_search%')";
if ($filter_date_from) $where_clauses[] = "p.payment_date >= '$filter_date_from'";
if ($filter_date_to) $where_clauses[] = "p.payment_date <= '$filter_date_to'";

$where_sql = implode(" AND ", $where_clauses);

// Get totals for KPI
$kpi_sql = "SELECT 
            COUNT(*) as total_tx, 
            SUM(paid_amount) as total_amount,
            SUM(CASE WHEN payment_mode IN ('Online', 'UPI') THEN paid_amount ELSE 0 END) as online_amount,
            SUM(CASE WHEN payment_mode IN ('Offline', 'Cash') THEN paid_amount ELSE 0 END) as cash_amount,
            SUM(CASE WHEN MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE()) THEN paid_amount ELSE 0 END) as this_month_amount
            FROM payments p JOIN users u ON p.user_id = u.id WHERE $where_sql";
$kpi_res = mysqli_query($conn, $kpi_sql);
$kpi = mysqli_fetch_assoc($kpi_res);

// Get total pages
$count_sql = "SELECT COUNT(*) as total FROM payments p JOIN users u ON p.user_id = u.id WHERE $where_sql";
$count_res = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_records / $limit);

// Fetch data
$sql = "SELECT p.*, u.name as renter_name, u.room_no 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE $where_sql 
        ORDER BY p.payment_date DESC, p.payment_time DESC 
        LIMIT $limit OFFSET $offset";
$payments_res = mysqli_query($conn, $sql);
$payments = [];
while ($row = mysqli_fetch_assoc($payments_res)) {
    $payments[] = $row;
}

// Fetch all users for dropdown
$users_res = mysqli_query($conn, "SELECT id, name, room_no FROM users ORDER BY name ASC");
$all_users = [];
while ($row = mysqli_fetch_assoc($users_res)) $all_users[] = $row;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manage Payments - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            background: var(--white);
            padding: 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .filter-input {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            background: var(--bg-main);
            color: var(--text-dark);
            outline: none;
        }
        .table-wrapper {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: rgba(0,0,0,0.02);
            padding: 14px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            color: var(--text-dark);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        .btn-action {
            width: 32px; height: 32px; border-radius: 8px; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;
        }
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #EF4444; }
        .btn-delete:hover { background: #EF4444; color: white; }
    </style>
</head>
<body>
    <?php include "sidebar.php"; ?>

    <main class="main">
        <?php include 'header.php'; ?>
        
        <div style="padding: 24px;">
        <div style="padding: 24px;">
            <div class="welcome" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 26px; font-weight: 900; color: var(--text-dark); margin: 0;">Payments & Ledger</h1>
                    <p style="color: var(--text-gray); font-size: 14px; margin: 4px 0 0 0;">Complete history of payments and monthly billing status</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="export_payments.php?<?php echo $_SERVER['QUERY_STRING']; ?>" class="btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class='bx bx-export'></i> Export CSV
                    </a>
                    <button onclick="document.getElementById('manualPaymentModal').style.display='flex'" class="btn-primary" style="border: none; padding: 10px 20px; font-size: 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <i class='bx bx-plus'></i> Add Payment
                    </button>
                </div>
            </div>
            
            <div class="view-toggle" style="background: var(--bg-main); padding: 6px; border-radius: 12px; display: inline-flex; gap: 4px; border: 1px solid var(--border); margin-bottom: 24px;">
                <a href="manage-payments.php" class="toggle-btn active" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; background: var(--primary-purple); color: white; box-shadow: var(--card-shadow);">
                    <i class='bx bx-list-ul'></i> Transaction History
                </a>
                <a href="master-ledger.php" class="toggle-btn" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; color: var(--text-gray); transition: 0.2s;">
                    <i class='bx bx-book-open'></i> Monthly Ledger
                </a>
            </div>

            <?php if(isset($success_msg)): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-check-circle'></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-error-circle'></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div style="background: var(--white); padding: 20px; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--card-shadow);">
                    <div style="color: var(--text-gray); font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Total Collection</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--primary-purple);">₹<?php echo number_format((float)$kpi['total_amount'], 2); ?></div>
                    <div style="font-size: 12px; color: var(--text-gray); margin-top: 4px;"><?php echo $kpi['total_tx']; ?> transactions</div>
                </div>
                <div style="background: var(--white); padding: 20px; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--card-shadow);">
                    <div style="color: var(--text-gray); font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Online vs Cash</div>
                    <div style="display: flex; gap: 16px;">
                        <div>
                            <div style="font-size: 18px; font-weight: 800; color: #10B981;">₹<?php echo number_format((float)$kpi['online_amount'], 2); ?></div>
                            <div style="font-size: 11px; color: var(--text-gray);">Online</div>
                        </div>
                        <div>
                            <div style="font-size: 18px; font-weight: 800; color: #F59E0B;">₹<?php echo number_format((float)$kpi['cash_amount'], 2); ?></div>
                            <div style="font-size: 11px; color: var(--text-gray);">Cash</div>
                        </div>
                    </div>
                </div>
                <div style="background: var(--white); padding: 20px; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--card-shadow);">
                    <div style="color: var(--text-gray); font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">This Month</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--text-dark);">₹<?php echo number_format((float)$kpi['this_month_amount'], 2); ?></div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" class="filter-bar">
                <select name="user_id" class="filter-input" style="flex: 1; min-width: 150px;">
                    <option value="">All Renters</option>
                    <?php foreach($all_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php if($filter_user == $u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['name']); ?> (Room <?php echo $u['room_no']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <select name="payment_mode" class="filter-input">
                    <option value="">All Modes</option>
                    <option value="Online" <?php if($filter_mode == 'Online') echo 'selected'; ?>>Online</option>
                    <option value="UPI" <?php if($filter_mode == 'UPI') echo 'selected'; ?>>UPI</option>
                    <option value="Cash" <?php if($filter_mode == 'Cash') echo 'selected'; ?>>Cash</option>
                    <option value="Offline" <?php if($filter_mode == 'Offline') echo 'selected'; ?>>Offline</option>
                </select>
                <input type="date" name="date_from" class="filter-input" value="<?php echo htmlspecialchars($filter_date_from ?? ''); ?>" placeholder="From Date">
                <input type="date" name="date_to" class="filter-input" value="<?php echo htmlspecialchars($filter_date_to ?? ''); ?>" placeholder="To Date">
                <input type="text" name="search" class="filter-input" placeholder="Search Txn ID..." value="<?php echo htmlspecialchars($filter_search); ?>" style="flex: 1; min-width: 150px;">
                <button type="submit" class="btn-primary" style="border: none; padding: 0 20px; border-radius: 8px; cursor: pointer;">Filter</button>
                <a href="manage-payments.php" class="btn-outline" style="display: flex; align-items: center; text-decoration: none; border-radius: 8px;">Reset</a>
            </form>

            <!-- Table -->
            <div class="table-wrapper animate-up">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Renter</th>
                                <th>Bill Type & Month</th>
                                <th>Amount</th>
                                <th>Mode & Ref</th>
                                <th>Recorded By</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($payments)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray);">No payments found for the selected criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($payments as $p): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-dark);"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></div>
                                            <div style="font-size: 12px; color: var(--text-gray);"><?php echo date('h:i A', strtotime($p['payment_time'])); ?></div>
                                        </td>
                                        <td style="max-width: 140px;">
                                            <div style="font-weight: 600; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;" title="<?php echo htmlspecialchars($p['renter_name']); ?>"><?php echo htmlspecialchars($p['renter_name']); ?></div>
                                            <div style="font-size: 12px; color: var(--text-gray);">Room: <?php echo htmlspecialchars($p['room_no'] ?: 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; text-transform: capitalize; color: var(--text-dark);"><?php echo htmlspecialchars(str_replace('_', ' ', $p['bill_type'])); ?></div>
                                            <div style="font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($p['month'] ?? '-'); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 800; color: #10B981;">₹<?php echo number_format($p['paid_amount'], 2); ?></div>
                                        </td>
                                        <td>
                                            <span style="background: rgba(98,75,255,0.1); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($p['payment_mode']); ?></span>
                                            <div style="font-size: 11px; font-family: monospace; color: var(--text-gray); margin-top: 4px;"><?php echo htmlspecialchars($p['transaction_id'] ?: ($p['sys_tx_id'] ?: 'N/A')); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size: 13px; color: var(--text-dark);"><i class='bx bx-user'></i> <?php echo htmlspecialchars($p['admin_name'] ?: 'System'); ?></div>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this payment record? This cannot be undone.');" style="margin:0;">
                                                    <input type="hidden" name="delete_payment_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="btn-action btn-delete" title="Delete Payment"><i class='bx bx-trash'></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; <?php echo ($i == $page) ? 'background: var(--primary-purple); color: white;' : 'background: var(--white); color: var(--text-gray); border: 1px solid var(--border);'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Manual Payment Modal -->
    <div id="manualPaymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: var(--white); border-radius: 24px; padding: 32px; width: 100%; max-width: 500px; position: relative;">
            <button onclick="document.getElementById('manualPaymentModal').style.display='none'" style="position: absolute; top: 24px; right: 24px; background: none; border: none; font-size: 24px; color: var(--text-gray); cursor: pointer;"><i class='bx bx-x'></i></button>
            <h2 style="margin: 0 0 24px 0; font-size: 20px; color: var(--text-dark);">Record Manual Payment</h2>
            
            <form method="POST">
                <input type="hidden" name="add_manual_payment" value="1">
                
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Select Renter</label>
                    <select name="user_id" required class="filter-input" style="width: 100%;">
                        <option value="">-- Choose Renter --</option>
                        <?php foreach($all_users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (Room <?php echo $u['room_no']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Bill Type</label>
                        <select name="bill_type" required class="filter-input" style="width: 100%;">
                            <option value="rent">Rent</option>
                            <option value="electricity">Electricity</option>
                            <option value="elec_rent">Rent + Electricity</option>
                            <option value="other">Other / Misc</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Bill Month (Optional)</label>
                        <input type="month" name="month" class="filter-input" style="width: 100%;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Amount (₹)</label>
                        <input type="number" name="amount" step="0.01" required class="filter-input" style="width: 100%;" placeholder="0.00">
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Mode</label>
                        <select name="payment_mode" required class="filter-input" style="width: 100%;">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Transaction ID / Reference (Optional)</label>
                    <input type="text" name="transaction_id" class="filter-input" style="width: 100%;" placeholder="e.g. UTR or Cash Receipt No">
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; border: none; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer;">
                    Record Payment
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Close modal when clicking outside
        document.getElementById('manualPaymentModal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>
</body>
</html>
