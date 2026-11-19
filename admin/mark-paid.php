<?php
require_once __DIR__ . '/../db.php';
session_start();
require_once __DIR__ . '/allocate_payment.php';
$ajax = !empty($_POST['ajax']);
function payment_response(bool $ok, string $message, bool $ajax): void {
    if ($ajax) {
        header('Content-Type: application/json');
        if (!$ok) http_response_code(400);
        echo json_encode(['success'=>$ok,'message'=>$message,'error'=>$ok ? null : $message]);
    } else {
        $_SESSION[$ok ? 'success' : 'error'] = $message;
        $dest = !empty($_POST['return_url']) ? trim($_POST['return_url']) : 'manage-payments.php';
        if (!preg_match('/^[a-zA-Z0-9_\-\.\?\&=\/]+$/', $dest) || strpos($dest, '://') !== false) {
            $dest = 'manage-payments.php';
        }
        header('Location: ' . $dest);
    }
    exit;
}
try {
    if (!isset($_SESSION['admin'])) throw new RuntimeException('Please sign in as administrator.');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf'] ?? '')) throw new RuntimeException('Security validation failed.');
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if (!in_array($type, ['rent', 'electricity', 'advance', 'security_deposit'], true) || $id <= 0) throw new InvalidArgumentException('Invalid bill.');
    if ($type === 'security_deposit') {
        $user = $id;
        $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM users WHERE id=$user"));
        if (!$profile) throw new RuntimeException('Resident not found.');
        $target = accounting_paise($profile['security_deposit']);
        $paidSoFar = accounting_deposit_paid($conn, $user);
        $depositDue = max(0, $target - $paidSoFar);
        $amount = $_POST['paid_amount'] ?? accounting_decimal($depositDue);
        $mode = $_POST['payment_mode'] ?? 'Cash';
        if (!in_array($mode, ['Cash', 'Cash/Offline', 'Offline', 'UPI', 'Online', 'Bank Transfer', 'Historical Deposit'], true)) throw new InvalidArgumentException('Invalid payment mode.');
        $ref = payment_form_reference('DEP-');
        $external = trim($_POST['transaction_id'] ?? '');
        if ($external === '' && in_array($mode, ['Cash', 'Cash/Offline', 'Offline', 'Historical Deposit'], true)) $external = $ref;
        $posted = accounting_record_deposit($conn, $user, $amount, $mode, $external, $ref, $_POST['payment_date'] ?? null, $_POST['payment_time'] ?? null);
    } else {
        $table = $type === 'rent' ? 'rent' : ($type === 'advance' ? 'users' : 'electricity');
        $bill = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM $table WHERE id=$id"));
        if (!$bill) throw new RuntimeException('Bill not found.');
        $user = $type === 'advance' ? $id : (int)$bill['user_id'];
        $default = $type === 'advance' ? 0 : max(0, accounting_paise($bill[$type === 'rent' ? 'rent_amount' : 'total_amount']) - accounting_bill_paid($conn, $user, $table, $id));
        $amount = $_POST['paid_amount'] ?? accounting_decimal($default);
        $mode = $_POST['payment_mode'] ?? 'Cash';
        if (!in_array($mode, ['Cash', 'Cash/Offline', 'Offline', 'UPI', 'Online', 'Bank Transfer'], true)) throw new InvalidArgumentException('Invalid payment mode.');
        $ref = payment_form_reference('MAN-');
        $external = trim($_POST['transaction_id'] ?? '');
        if ($external === '' && in_array($mode, ['Cash', 'Cash/Offline', 'Offline'], true)) $external = $ref;
        $posted = accounting_specific_payment($conn, $user, $type, $id, $amount, $mode, $external, $ref, $_POST['payment_date'] ?? null, $_POST['payment_time'] ?? null);
    }
    if ($posted) {
        $resident = mysqli_fetch_assoc(accounting_query($conn, "SELECT name, email FROM users WHERE id=$user"));
        if (!empty($resident['email'])) {
            try {
                require_once __DIR__ . '/utils_mailer.php';
                $desc = ($type === 'security_deposit') ? 'Security deposit payment' : 'Payment';
                sendEmail($resident['email'], 'Payment Receipt - ' . HOUSE_NAME, "$desc of ₹" . htmlspecialchars((string)$amount) . ' recorded. Receipt reference: ' . htmlspecialchars($ref));
            } catch (Throwable $mailFailure) { error_log('Receipt delivery failed: ' . $mailFailure->getMessage()); }
        }
    }
    payment_response(true,$posted ? 'Payment recorded. Balance updated from the ledger.' : 'This receipt was already recorded. No duplicate payment was added.',$ajax);
} catch (Throwable $failure) {
    error_log($failure->getMessage());
    payment_response(false,$failure instanceof InvalidArgumentException || $failure instanceof RuntimeException ? $failure->getMessage() : 'Unable to record payment. No changes were saved.',$ajax);
}
