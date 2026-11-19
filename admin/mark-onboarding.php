<?php
require_once '../db.php';
require_once __DIR__ . '/../includes/accounting_store.php';
session_start();

if (!isset($_SESSION['admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit;
}

$user = (int)($_POST['user_id'] ?? 0);
try {
    $receipt = trim($_POST['receipt'] ?? '');
    if ($receipt === '') {
        $receipt = 'DEP-' . date('Ymd') . '-' . bin2hex(random_bytes(3));
    }
    $date = !empty($_POST['received_date']) ? $_POST['received_date'] : date('Y-m-d');
    $amount = trim($_POST['amount'] ?? '');
    if ($amount === '') {
        $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT security_deposit FROM users WHERE id=$user"));
        if ($profile) {
            $secDue = max(0, accounting_paise($profile['security_deposit']) - accounting_deposit_paid($conn, $user));
            $amount = accounting_decimal($secDue);
        }
    }
    accounting_record_deposit($conn, $user, $amount, 'Cash/Offline', $receipt, payment_form_reference('DEP-'), $date);
    $_SESSION['success'] = 'Verified security deposit receipt recorded successfully.';
} catch (InvalidArgumentException $error) {
    $_SESSION['error'] = $error->getMessage();
} catch (Throwable $error) {
    error_log('Deposit recording failed: ' . $error->getMessage());
    $_SESSION['error'] = 'Unable to record the security deposit.';
}
header('Location: view-renter.php?id=' . $user);
