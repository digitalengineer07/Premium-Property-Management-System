<?php
// admin/export_payments.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    die("Unauthorized");
}

// Filters (same as manage-payments.php)
$filter_user = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
$filter_mode = isset($_GET['payment_mode']) && $_GET['payment_mode'] !== '' ? mysqli_real_escape_string($conn, $_GET['payment_mode']) : null;
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? mysqli_real_escape_string($conn, $_GET['date_from']) : null;
$filter_date_to = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? mysqli_real_escape_string($conn, $_GET['date_to']) : null;

$where_clauses = ["1=1"];
if ($filter_user) $where_clauses[] = "p.user_id = $filter_user";
if ($filter_mode) $where_clauses[] = "p.payment_mode = '$filter_mode'";
if ($filter_search) $where_clauses[] = "(p.transaction_id LIKE '%$filter_search%' OR u.name LIKE '%$filter_search%' OR p.sys_tx_id LIKE '%$filter_search%')";
if ($filter_date_from) $where_clauses[] = "p.payment_date >= '$filter_date_from'";
if ($filter_date_to) $where_clauses[] = "p.payment_date <= '$filter_date_to'";

$where_sql = implode(" AND ", $where_clauses);

$sql = "SELECT p.*, u.name as renter_name, u.room_no 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE $where_sql 
        ORDER BY p.payment_date DESC, p.payment_time DESC";
$res = mysqli_query($conn, $sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=payments_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Payment ID', 'Date', 'Time', 'Renter Name', 'Room No', 'Bill Type', 'Month', 'Amount', 'Mode', 'Transaction ID', 'Recorded By']);

while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($output, [
        $row['id'],
        $row['payment_date'],
        $row['payment_time'],
        $row['renter_name'],
        $row['room_no'],
        $row['bill_type'],
        $row['month'],
        $row['paid_amount'],
        $row['payment_mode'],
        $row['transaction_id'] ?: $row['sys_tx_id'],
        $row['admin_name']
    ]);
}
fclose($output);
exit;
