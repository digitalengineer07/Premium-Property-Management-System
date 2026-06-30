<?php
// renter/fetch_notifications.php
// Shared notification logic across all renter portal pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$notif_user_id = (int)($_SESSION['user_id'] ?? 0);
if ($notif_user_id <= 0) return;

// Ensure $conn is available
if (!isset($conn)) {
    if (file_exists("../db.php")) require_once "../db.php";
    elseif (file_exists("db.php")) require_once "db.php";
}

if (!isset($conn) || !$conn) return;

$unread_notifications = [];
$dismissed_cookie = $_COOKIE['dismissed_notifs'] ?? '';
$dismissed_ids = $dismissed_cookie ? explode(',', $dismissed_cookie) : [];

// 1. Unread Announcements
$ann_q = @mysqli_query($conn, "SELECT id, title, created_at FROM announcements WHERE created_at >= NOW() - INTERVAL 7 DAY ORDER BY created_at DESC");
if ($ann_q) {
    while($a = mysqli_fetch_assoc($ann_q)) {
        $nid = 'ann_' . $a['id'];
        if (in_array($nid, $dismissed_ids)) continue;
        $unread_notifications[] = [
            'id' => $nid,
            'type' => 'announcement',
            'title' => 'New Announcement',
            'message' => $a['title'],
            'time' => $a['created_at'],
            'icon' => 'bx-speaker',
            'color' => '#3B82F6'
        ];
    }
}

// 2. Rejected Payments
@mysqli_query($conn, "ALTER TABLE payment_notifications ADD COLUMN is_dismissed TINYINT(1) DEFAULT 0");
$rej_notif_q = @mysqli_query($conn, "SELECT id, transaction_id, admin_note, amount, created_at FROM payment_notifications WHERE user_id = $notif_user_id AND status = 'Rejected' AND IFNULL(is_dismissed, 0) = 0 ORDER BY id DESC");
if ($rej_notif_q) {
    while($r = mysqli_fetch_assoc($rej_notif_q)) {
        $nid = 'rej_' . $r['id'];
        if (in_array($nid, $dismissed_ids)) continue;
        $unread_notifications[] = [
            'id' => $nid,
            'type' => 'rejection',
            'title' => 'Payment Rejected',
            'message' => '₹' . number_format($r['amount'], 2) . ' (UTR: ' . $r['transaction_id'] . ') ' . (!empty($r['admin_note']) ? '- ' . $r['admin_note'] : ''),
            'time' => $r['created_at'],
            'icon' => 'bx-x-circle',
            'color' => '#EF4444'
        ];
    }
}

// 3. Outstanding Balance (Always calculated independently using decoupled status columns)
$pure_rent_due_n = 0;
$stmt_n = mysqli_prepare($conn, "SELECT IFNULL(SUM(rent_amount),0) as total FROM rent WHERE user_id = ? AND status != 'Paid'");
if ($stmt_n) {
    mysqli_stmt_bind_param($stmt_n, "i", $notif_user_id);
    mysqli_stmt_execute($stmt_n);
    $r1_n = mysqli_stmt_get_result($stmt_n);
    $pure_rent_due_n = (float)(mysqli_fetch_assoc($r1_n)['total'] ?? 0);
    mysqli_stmt_close($stmt_n);
}

$elec_due_n = 0;
$rent_portion_due_n = 0;
$stmt_n2 = mysqli_prepare($conn, "SELECT amount, rent_amount, maintenance, dues, status, elec_status, rent_status FROM electricity WHERE user_id = ?");
if ($stmt_n2) {
    mysqli_stmt_bind_param($stmt_n2, "i", $notif_user_id);
    mysqli_stmt_execute($stmt_n2);
    $r2_n = mysqli_stmt_get_result($stmt_n2);
    while ($row = mysqli_fetch_assoc($r2_n)) {
        $e_status = !empty($row['elec_status']) ? $row['elec_status'] : $row['status'];
        $r_status = !empty($row['rent_status']) ? $row['rent_status'] : $row['status'];
        if ($e_status === 'Due') {
            $elec_due_n += (float)$row['amount'];
        }
        if ($r_status === 'Due') {
            $rent_portion_due_n += ((float)$row['rent_amount'] + (float)$row['maintenance'] + (float)$row['dues']);
        }
    }
    mysqli_stmt_close($stmt_n2);
}

$u_adj_q = @mysqli_query($conn, "SELECT pending_adjustment FROM users WHERE id = $notif_user_id");
$unbilled_adj_n = $u_adj_q ? (float)(mysqli_fetch_assoc($u_adj_q)['pending_adjustment'] ?? 0) : 0;

$notif_total_due = $elec_due_n + $pure_rent_due_n + $rent_portion_due_n - $unbilled_adj_n;

if ($notif_total_due > 0) {
    $nid = 'due_' . date('Y_m');
    if (!in_array($nid, $dismissed_ids)) {
        $unread_notifications[] = [
            'id' => $nid,
            'type' => 'due',
            'title' => 'Payment Due',
            'message' => 'You have an outstanding balance of ₹' . number_format($notif_total_due, 2),
            'time' => date('Y-m-d H:i:s'),
            'icon' => 'bx-wallet',
            'color' => '#F59E0B'
        ];
    }
}

// Sort by time descending
usort($unread_notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

$unread_count = count($unread_notifications);
?>
