<?php
// admin/transactions-log.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
$admin_user = s($_SESSION['admin'] ?? 'Admin');

// Filter Inputs
$f_search = mysqli_real_escape_string($conn, trim($_GET['search'] ?? ''));
$f_type = mysqli_real_escape_string($conn, $_GET['type'] ?? 'All');
$f_source = mysqli_real_escape_string($conn, $_GET['source'] ?? 'All');
$f_start = mysqli_real_escape_string($conn, $_GET['start_date'] ?? '');
$f_end = mysqli_real_escape_string($conn, $_GET['end_date'] ?? '');

$where = ["1=1"];

if ($f_search !== '') {
    $where[] = "(u.name LIKE '%$f_search%' OR combined_tx.id LIKE '%$f_search%')";
}
if ($f_type !== 'All') {
    $where[] = "combined_tx.type = '$f_type'";
}
if ($f_source !== 'All') {
    $where[] = "combined_tx.source = '$f_source'";
}
if ($f_start !== '') {
    $where[] = "combined_tx.payment_date >= '$f_start'";
}
if ($f_end !== '') {
    $where[] = "combined_tx.payment_date <= '$f_end'";
}

$where_clause = implode(" AND ", $where);

// Pagination
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$unified_tx_sql = "
    SELECT combined_tx.*, u.name as renter_name, u.room_no 
    FROM (
        SELECT 
            id, user_id, bill_type as type, bill_id, paid_amount as amount, payment_mode as mode, 
            payment_date, payment_time, 'Success' as status, 'admin' as source
        FROM payments
        UNION ALL
        SELECT 
            id, user_id, bill_type as type, bill_id, amount, payment_method as mode, 
            DATE(created_at) as payment_date, TIME(created_at) as payment_time, status, 'renter' as source
        FROM payment_notifications
    ) as combined_tx
    JOIN users u ON combined_tx.user_id = u.id
    WHERE $where_clause
    ORDER BY payment_date DESC, payment_time DESC
    LIMIT $limit OFFSET $offset
";

$transactions = mysqli_query($conn, $unified_tx_sql);

$count_sql = "
    SELECT COUNT(*) as total 
    FROM (
        SELECT 
            id, user_id, bill_type as type, bill_id, paid_amount as amount, payment_mode as mode, 
            payment_date, payment_time, 'Success' as status, 'admin' as source
        FROM payments
        UNION ALL
        SELECT 
            id, user_id, bill_type as type, bill_id, amount, payment_method as mode, 
            DATE(created_at) as payment_date, TIME(created_at) as payment_time, status, 'renter' as source
        FROM payment_notifications
    ) as combined_tx
    JOIN users u ON combined_tx.user_id = u.id
    WHERE $where_clause
";
$total_rows = mysqli_fetch_assoc(mysqli_query($conn, $count_sql))['total'];
$total_pages = ceil($total_rows / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Log | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        .badge-admin { background: #E0E7FF; color: #4F46E5; }
        .badge-renter { background: #FEF3C7; color: #D97706; }
        
        @media (max-width: 768px) {
            .table-responsive { overflow: visible !important; }
            table, thead, tbody, th, td, tr { display: block !important; width: 100% !important; }
            thead { display: none !important; }
            
            tbody tr {
                background: var(--white); border: 1px solid var(--border); border-radius: 20px;
                padding: 20px; margin-bottom: 20px; box-shadow: var(--card-shadow);
            }
            tbody td {
                padding: 0 !important; border: none !important; margin-bottom: 12px;
                display: flex !important; justify-content: space-between; align-items: center; font-size: 14px;
            }
            tbody td::before {
                content: attr(data-label); font-weight: 700; color: var(--text-gray); text-transform: uppercase; font-size: 11px;
            }
            tbody td:last-child {
                margin-top: 15px; padding-top: 15px !important; border-top: 1px solid var(--border) !important; display: block !important;
            }
            tbody td:last-child::before { display: none; }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class='bx bx-history' style="color: var(--primary-purple); font-size: 32px; vertical-align: middle;"></i> Transaction Log</h1>
                <p>Complete history of all recorded payments and verifications</p>
            </div>
            <a href="dashboard.php" class="btn-outline"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
        </div>
    </div>

    <div class="panel animate-up" style="margin-bottom: 24px; padding: 20px;">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Search Name/ID</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($f_search); ?>" placeholder="Search..." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div style="flex: 1; min-width: 140px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Bill Type</label>
                <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="All" <?php if($f_type=='All') echo 'selected';?>>All Types</option>
                    <option value="rent" <?php if($f_type=='rent') echo 'selected';?>>Rent</option>
                    <option value="electricity" <?php if($f_type=='electricity') echo 'selected';?>>Electricity</option>
                    <option value="advance" <?php if($f_type=='advance') echo 'selected';?>>Advance</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 140px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Source</label>
                <select name="source" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    <option value="All" <?php if($f_source=='All') echo 'selected';?>>All Sources</option>
                    <option value="admin" <?php if($f_source=='admin') echo 'selected';?>>Admin (Manual)</option>
                    <option value="renter" <?php if($f_source=='renter') echo 'selected';?>>Renter (Online)</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">Start Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($f_start); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div style="flex: 1; min-width: 130px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: var(--text-gray);">End Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($f_end); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div style="display: flex; gap: 8px; flex: 1; min-width: 200px;">
                <button type="submit" class="btn-primary" style="flex: 1; justify-content: center; padding: 10px; border-radius: 8px;"><i class='bx bx-filter-alt'></i> Apply</button>
                <a href="transactions-log.php" class="btn-outline" style="flex: 1; justify-content: center; padding: 10px; border-radius: 8px;"><i class='bx bx-reset'></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="panel animate-up">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Resident</th>
                        <th>Ref / ID</th>
                        <th>Amount</th>
                        <th>Type & Mode</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($transactions) == 0): ?>
                        <tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--text-gray);">No transactions found matching your criteria.</td></tr>
                    <?php else: while ($tx = mysqli_fetch_assoc($transactions)): ?>
                    <tr>
                        <td data-label="Resident">
                            <div style="font-weight: 600; color: var(--primary-purple);"><?php echo s($tx['renter_name']); ?></div>
                            <div style="font-size: 11px; color: var(--text-gray);">Room <?php echo s($tx['room_no']); ?></div>
                        </td>
                        <td data-label="Ref / ID">
                            <code style="font-size: 11px; background: var(--bg-main); padding: 4px 8px; border-radius: 6px; font-weight: 600;">#<?php echo $tx['id']; ?></code>
                            <div style="margin-top: 4px;">
                                <span class="badge <?php echo $tx['source'] == 'admin' ? 'badge-admin' : 'badge-renter'; ?>" style="font-size: 9px; padding: 2px 6px;">
                                    <?php echo $tx['source'] == 'admin' ? 'Admin Entry' : 'Renter Portal'; ?>
                                </span>
                            </div>
                        </td>
                        <td data-label="Amount" style="font-weight: 700; color: #10B981;">₹<?php echo number_format($tx['amount']); ?></td>
                        <td data-label="Type & Mode" style="font-size: 12px;">
                            <span style="text-transform: capitalize; font-weight: 600;"><?php echo $tx['type']; ?></span>
                            <div style="color: var(--text-gray); font-size: 11px;"><?php echo $tx['mode'] ?: 'Unknown'; ?></div>
                        </td>
                        <td data-label="Date & Time">
                            <div style="font-weight: 500;"><?php echo date('M d, Y', strtotime($tx['payment_date'])); ?></div>
                            <div style="font-size: 11px; color: var(--text-gray);"><?php echo date('h:i A', strtotime($tx['payment_time'])); ?></div>
                        </td>
                        <td data-label="Status">
                            <span class="badge <?php 
                                if($tx['status'] == 'Approved' || $tx['status'] == 'Success') echo 'badge-paid'; 
                                elseif($tx['status'] == 'Pending') echo 'badge-due'; 
                                else echo 'badge-rejected'; 
                            ?>">
                                <?php echo $tx['status']; ?>
                            </span>
                        </td>
                        <td data-label="Action">
                            <?php if($tx['status'] == 'Approved' || $tx['status'] == 'Success'): ?>
                                <?php if($tx['type'] == 'rent'): ?>
                                    <a href="slip.php?rent_id=<?php echo $tx['bill_id']; ?>" target="_blank" class="btn-outline" style="padding: 6px 12px; font-size: 11px;"><i class='bx bx-receipt'></i> Receipt</a>
                                <?php elseif($tx['type'] == 'electricity'): ?>
                                    <a href="generate-bill.php?id=<?php echo $tx['bill_id']; ?>" target="_blank" class="btn-outline" style="padding: 6px 12px; font-size: 11px;"><i class='bx bx-receipt'></i> Receipt</a>
                                <?php endif; ?>
                            <?php elseif($tx['status'] == 'Pending' && $tx['source'] == 'renter'): ?>
                                <a href="payment-verifications.php" class="btn-primary" style="padding: 6px 12px; font-size: 11px;"><i class='bx bx-check-shield'></i> Verify</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
            <?php if($page > 1): ?>
                <a href="?p=<?php echo $page-1; ?>&search=<?php echo urlencode($f_search); ?>&type=<?php echo $f_type; ?>&source=<?php echo $f_source; ?>&start_date=<?php echo $f_start; ?>&end_date=<?php echo $f_end; ?>" class="btn-outline" style="padding: 8px 12px;">&laquo; Prev</a>
            <?php endif; ?>
            
            <span style="padding: 8px 12px; font-weight: 600; color: var(--text-gray);">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            
            <?php if($page < $total_pages): ?>
                <a href="?p=<?php echo $page+1; ?>&search=<?php echo urlencode($f_search); ?>&type=<?php echo $f_type; ?>&source=<?php echo $f_source; ?>&start_date=<?php echo $f_start; ?>&end_date=<?php echo $f_end; ?>" class="btn-outline" style="padding: 8px 12px;">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .badge-rejected { background: #FEF2F2; color: #EF4444; }
</style>

<script>
    document.querySelector('.search-bar input')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }
    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        if (isDark) themeToggle.classList.replace('bx-moon', 'bx-sun');
        else themeToggle.classList.replace('bx-sun', 'bx-moon');
    });
</script>

</body>
</html>
