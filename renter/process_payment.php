<?php
// renter/process_payment.php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$return_url = $_POST['return_url'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment_notif'])) {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $_SESSION['payment_error'] = "Invalid CSRF token.";
        header("Location: " . $return_url);
        exit;
    } else {
        $b_type = $_POST['bill_type'] ?? 'general';
        $b_id = !empty($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
        $amt = (float)$_POST['amount'];
        $tr_id = trim($_POST['transaction_id'] ?? '');
        if (!empty($_POST['bill_month']) && $_POST['bill_month'] !== 'undefined') {
            $p_month = date('F Y', strtotime($_POST['bill_month'].'-01'));
        } else {
            if ($b_type === 'total') {
                $p_month = 'Total Balance';
            } elseif ($b_type === 'onboarding') {
                $p_month = 'Onboarding & Advance';
            } else {
                $p_month = 'Miscellaneous';
            }
        }
        $payment_method = $_POST['payment_method'] ?? 'UPI';
        $sys_tx_id = 'TXN-' . date('md') . '-' . strtoupper(bin2hex(random_bytes(4)));

        if ($amt <= 0) {
            $_SESSION['payment_error'] = "Payment amount must be greater than zero.";
            header("Location: " . $return_url);
            exit;
        }

        if ($payment_method === 'UPI' && empty($tr_id)) {
            $_SESSION['payment_error'] = "Please enter the Transaction ID / UTR.";
            header("Location: " . $return_url);
            exit;
        } elseif ($payment_method === 'UPI' && !preg_match('/^\d{12}$/', $tr_id)) {
            $_SESSION['payment_error'] = "Invalid UTR. Please enter exactly 12 digits.";
            header("Location: " . $return_url);
            exit;
        } else {
            if (empty($tr_id)) {
                $prefix = ($payment_method === 'Cash') ? 'CASH-' : 'BANK-';
                $tr_id = $prefix . date('Ymd') . '-' . strtoupper(substr(md5($sys_tx_id), 0, 6));
            }
            $is_duplicate = false;
            if ($payment_method === 'UPI' && !empty($tr_id)) {
                $check_stmt = mysqli_prepare($conn, "
                    SELECT id FROM payment_notifications WHERE transaction_id = ?
                    UNION 
                    SELECT id FROM payments WHERE transaction_id = ?
                ");
                mysqli_stmt_bind_param($check_stmt, "ss", $tr_id, $tr_id);
                mysqli_stmt_execute($check_stmt);
                $check_res = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_res) > 0) {
                    $is_duplicate = true;
                }
            } else if ($payment_method === 'Cash' || $payment_method === 'Bank Transfer') {
                // Check for duplicate cash/bank applications within the last 5 minutes to prevent spam
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE user_id = ? AND amount = ? AND payment_method = ? AND created_at >= NOW() - INTERVAL 5 MINUTE");
                mysqli_stmt_bind_param($check_stmt, "ids", $user_id, $amt, $payment_method);
                mysqli_stmt_execute($check_stmt);
                $check_res = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_res) > 0) {
                    $is_duplicate = true;
                }
            }
            
            // Advanced Validation: Check if a request already exists to prevent duplicate submissions
            $is_duplicate_request = false;
            $duplicate_msg = "";

            if (!$is_duplicate) {
                if ($b_id > 0) {
                    // Specific bill check
                    $chk_stmt = mysqli_prepare($conn, "SELECT status FROM payment_notifications WHERE user_id = ? AND bill_id = ? AND status = 'Pending'");
                    mysqli_stmt_bind_param($chk_stmt, "ii", $user_id, $b_id);
                    mysqli_stmt_execute($chk_stmt);
                    $res = mysqli_stmt_get_result($chk_stmt);
                    if ($row = mysqli_fetch_assoc($res)) {
                        $is_duplicate_request = true;
                        $duplicate_msg = "You already have a Pending payment request for this bill.";
                    }
                } else if ($b_type === 'total') {
                    // Clear all dues check
                    $chk_stmt = mysqli_prepare($conn, "SELECT status FROM payment_notifications WHERE user_id = ? AND bill_type = 'total' AND status = 'Pending'");
                    mysqli_stmt_bind_param($chk_stmt, "i", $user_id);
                    mysqli_stmt_execute($chk_stmt);
                    $res = mysqli_stmt_get_result($chk_stmt);
                    if (mysqli_num_rows($res) > 0) {
                        $is_duplicate_request = true;
                        $duplicate_msg = "You already have a Pending request to clear all dues.";
                    }
                } else if ($b_type === 'onboarding') {
                    // Onboarding payment check
                    $chk_stmt = mysqli_prepare($conn, "SELECT status FROM payment_notifications WHERE user_id = ? AND bill_type = 'onboarding' AND status = 'Pending'");
                    mysqli_stmt_bind_param($chk_stmt, "i", $user_id);
                    mysqli_stmt_execute($chk_stmt);
                    $res = mysqli_stmt_get_result($chk_stmt);
                    if (mysqli_num_rows($res) > 0) {
                        $is_duplicate_request = true;
                        $duplicate_msg = "You already have a Pending request for Onboarding Dues.";
                    }
                } else {
                    // General / Advance payment check
                    $chk_stmt = mysqli_prepare($conn, "SELECT status FROM payment_notifications WHERE user_id = ? AND bill_type = 'general' AND amount = ? AND status = 'Pending' AND DATE(created_at) = CURDATE()");
                    mysqli_stmt_bind_param($chk_stmt, "id", $user_id, $amt);
                    mysqli_stmt_execute($chk_stmt);
                    $res = mysqli_stmt_get_result($chk_stmt);
                    if (mysqli_num_rows($res) > 0) {
                        $is_duplicate_request = true;
                        $duplicate_msg = "You already have a Pending advance payment for this exact amount today.";
                    }
                }
            }

            if ($is_duplicate) {
                $_SESSION['payment_error'] = "This UTR number has already been submitted. Please check your transaction ID.";
            } else if ($is_duplicate_request) {
                $_SESSION['payment_error'] = $duplicate_msg;
            } else {
                // Ensure table exists (safeguard)
                mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payment_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    bill_type VARCHAR(50) NOT NULL,
                    bill_id INT NULL,
                    amount DECIMAL(10, 2) NOT NULL,
                    transaction_id VARCHAR(50) NOT NULL,
                    payment_method VARCHAR(50) DEFAULT 'UPI',
                    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
                    admin_note TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    sys_tx_id VARCHAR(100),
                    month VARCHAR(50)
                )");
                
                $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method, sys_tx_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isidssss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method, $sys_tx_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['payment_success'] = "Payment approval request submitted successfully!";
                } else {
                    $_SESSION['payment_error'] = "Error submitting request: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: " . $return_url);
            exit;
        }
    }
}

// Fallback if accessed without POST
header("Location: dashboard.php");
exit;
?>
