<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    die("Unauthorized");
}

$target_month = $_GET['month'] ?? date('Y-m-01', strtotime('first day of last month'));
$status = mysqli_real_escape_string($conn, $_GET['status'] ?? 'All');
$q = mysqli_real_escape_string($conn, trim($_GET['q'] ?? ''));

$sql = "SELECT * FROM (
    SELECT 
        r.id as bill_id,
        u.name as renter_name,
        u.room_no,
        r.month as billing_month,
        r.rent_amount,
        IFNULL(SUM(p.paid_amount), 0) as amount_paid,
        MAX(p.payment_date) as last_payment_date,
        GROUP_CONCAT(DISTINCT p.payment_mode SEPARATOR ', ') as payment_modes,
        CASE 
            WHEN IFNULL(SUM(p.paid_amount), 0) >= r.rent_amount THEN 'Paid'
            WHEN IFNULL(SUM(p.paid_amount), 0) > 0 THEN 'Partial'
            ELSE 'Due'
        END as rent_status
    FROM rent r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN payments p ON p.bill_type = 'rent' AND p.bill_id = r.id
    WHERE r.month = ?
    GROUP BY r.id
) as rent_history WHERE 1=1";

if ($status !== 'All') {
    $sql .= " AND rent_status = '$status'";
}
if ($q !== '') {
    $sql .= " AND (renter_name LIKE '%$q%' OR room_no LIKE '%$q%')";
}

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $target_month);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="rent_history_' . $target_month . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Resident Name', 'Room No', 'Billing Month', 'Rent Amount', 'Amount Paid', 'Last Payment Date', 'Payment Modes', 'Status']);

while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($output, [
        $row['renter_name'],
        $row['room_no'],
        $row['billing_month'],
        $row['rent_amount'],
        $row['amount_paid'],
        $row['last_payment_date'] ?? 'N/A',
        $row['payment_modes'] ?? 'N/A',
        $row['rent_status']
    ]);
}

fclose($output);
exit;
