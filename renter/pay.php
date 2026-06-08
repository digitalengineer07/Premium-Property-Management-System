<?php
require_once "../db.php";
session_start();
require_once "../audit.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not authenticated']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$type = $_POST['type'] ?? '';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$date = date('Y-m-d');

if (isset($_POST['csrf'])) {
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        echo json_encode(['status'=>'error','message'=>'Invalid CSRF token']);
        exit;
    }
}

if ($id <= 0 || !in_array($type, ['rent','electricity'])) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

if ($type === 'rent') {
    $stmt = mysqli_prepare($conn, "SELECT id, user_id, month, rent_amount, status FROM rent WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) !== 1) {
        echo json_encode(['status'=>'error','message'=>'Rent record not found']);
        exit;
    }

    $row = mysqli_fetch_assoc($res);
    if ($row['status'] === 'Paid') {
        echo json_encode(['status'=>'error','message'=>'Already paid']);
        exit;
    }

    $amount = (int) $row['rent_amount'];
    $month = $row['month'];

    $ins = mysqli_prepare($conn, "INSERT INTO payments (user_id, month, total_amount, payment_date) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($ins, "isis", $user_id, $month, $amount, $date);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);

    $upd = mysqli_prepare($conn, "UPDATE rent SET status = 'Paid' WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($upd, "ii", $id, $user_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    logAction($conn, "renter", $user_id, "Paid rent id {$id}");

    $rent_due_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE user_id=$user_id AND status='Due'"));
    $elec_due_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM electricity WHERE user_id=$user_id AND status='Due'"));
    $total_due = (int)$rent_due_row['total'] + (int)$elec_due_row['total'];

    echo json_encode(['status'=>'ok','message'=>'Payment recorded','type'=>'rent','id'=>$id,'total_due'=>$total_due]);
    exit;
}

if ($type === 'electricity') {
    $stmt = mysqli_prepare($conn, "SELECT id, user_id, month, amount, status FROM electricity WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) !== 1) {
        echo json_encode(['status'=>'error','message'=>'Electricity record not found']);
        exit;
    }

    $row = mysqli_fetch_assoc($res);
    if ($row['status'] === 'Paid') {
        echo json_encode(['status'=>'error','message'=>'Already paid']);
        exit;
    }

    $amount = (int) $row['amount'];
    $month = $row['month'];

    $ins = mysqli_prepare($conn, "INSERT INTO payments (user_id, month, total_amount, payment_date) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($ins, "isis", $user_id, $month, $amount, $date);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);

    $upd = mysqli_prepare($conn, "UPDATE electricity SET status = 'Paid' WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($upd, "ii", $id, $user_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    logAction($conn, "renter", $user_id, "Paid electricity id {$id}");

    $rent_due_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(rent_amount),0) AS total FROM rent WHERE user_id=$user_id AND status='Due'"));
    $elec_due_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM electricity WHERE user_id=$user_id AND status='Due'"));
    $total_due = (int)$rent_due_row['total'] + (int)$elec_due_row['total'];

    echo json_encode(['status'=>'ok','message'=>'Payment recorded','type'=>'electricity','id'=>$id,'total_due'=>$total_due]);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Unknown error']);
exit;
