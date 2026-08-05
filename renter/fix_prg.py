import os

filepath = r'c:\xampp\htdocs\renter-system\renter\payment-approvals.php'

target = """// Handle Payment Submission
$payment_error = '';
$payment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment_notif'])) {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $payment_error = "Invalid CSRF token.";
    } else {
        $b_type = $_POST['bill_type'] ?? 'general';
        $b_id = !empty($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
        $amt = (float)$_POST['amount'];
        $tr_id = trim($_POST['transaction_id'] ?? '');
        $p_month = !empty($_POST['bill_month']) ? date('F Y', strtotime($_POST['bill_month'].'-01')) : 'Advance/General';
        $payment_method = $_POST['payment_method'] ?? 'UPI';
        $sys_tx_id = 'TXN-' . date('md') . '-' . strtoupper(bin2hex(random_bytes(4)));

        if ($payment_method === 'UPI' && empty($tr_id)) {
            $payment_error = "Please enter the Transaction ID / UTR.";
        } else {
            $is_duplicate = false;
            if ($payment_method === 'UPI' && !empty($tr_id)) {
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $tr_id);
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

            if ($is_duplicate) {
                $payment_error = "This UTR number has already been submitted. Please check your transaction ID.";
            } else if ($is_duplicate_request) {
                $payment_error = $duplicate_msg;
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method, sys_tx_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isidssss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method, $sys_tx_id);
                if (mysqli_stmt_execute($stmt)) {
                    $payment_success = "Payment approval request submitted successfully!";
                } else {
                    $payment_error = "Error submitting request. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}"""

replacement = """// Handle Payment Submission
$payment_error = $_SESSION['payment_error'] ?? '';
$payment_success = $_SESSION['payment_success'] ?? '';
unset($_SESSION['payment_error'], $_SESSION['payment_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment_notif'])) {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $_SESSION['payment_error'] = "Invalid CSRF token.";
        header("Location: payment-approvals.php");
        exit;
    } else {
        $b_type = $_POST['bill_type'] ?? 'general';
        $b_id = !empty($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
        $amt = (float)$_POST['amount'];
        $tr_id = trim($_POST['transaction_id'] ?? '');
        $p_month = !empty($_POST['bill_month']) ? date('F Y', strtotime($_POST['bill_month'].'-01')) : 'Advance/General';
        $payment_method = $_POST['payment_method'] ?? 'UPI';
        $sys_tx_id = 'TXN-' . date('md') . '-' . strtoupper(bin2hex(random_bytes(4)));

        if ($payment_method === 'UPI' && empty($tr_id)) {
            $_SESSION['payment_error'] = "Please enter the Transaction ID / UTR.";
            header("Location: payment-approvals.php");
            exit;
        } else {
            $is_duplicate = false;
            if ($payment_method === 'UPI' && !empty($tr_id)) {
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $tr_id);
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

            if ($is_duplicate) {
                $_SESSION['payment_error'] = "This UTR number has already been submitted. Please check your transaction ID.";
            } else if ($is_duplicate_request) {
                $_SESSION['payment_error'] = $duplicate_msg;
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method, sys_tx_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isidssss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method, $sys_tx_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['payment_success'] = "Payment approval request submitted successfully!";
                } else {
                    $_SESSION['payment_error'] = "Error submitting request. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: payment-approvals.php");
            exit;
        }
    }
}"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath} with PRG pattern")
    else:
        print(f"Target not found in {filepath}")
else:
    print(f"File not found: {filepath}")
