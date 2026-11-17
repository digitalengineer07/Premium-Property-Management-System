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
    accounting_record_existing_deposit($conn, $user, trim($_POST['amount'] ?? ''), trim($_POST['receipt'] ?? ''),
        payment_form_reference('HIST-DEP-'), $_POST['received_date'] ?? '');
    $_SESSION['deposit_notice'] = 'Verified security deposit receipt recorded.';
} catch (InvalidArgumentException $error) {
    $_SESSION['deposit_notice'] = $error->getMessage();
} catch (Throwable $error) {
    error_log('Deposit recording failed: ' . $error->getMessage());
    $_SESSION['deposit_notice'] = 'Unable to record the security deposit.';
}
header('Location: view-renter.php?id=' . $user);
