import os

filepath = r'c:\xampp\htdocs\renter-system\renter\my-bills.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """        $b_type = $_POST['bill_type'] ?? 'total';
        $b_id = !empty($_POST['bill_id']) ? (int)$_POST['bill_id'] : null;
        $amt = (float)$_POST['amount'];
        $tr_id = trim($_POST['transaction_id'] ?? '');
        $month = $_POST['month'] ?? '';

        $payment_method = $_POST['payment_method'] ?? 'UPI';
        $sys_tx_id = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));

        if ($payment_method === 'UPI' && empty($tr_id)) {"""

replacement = """        $b_type = $_POST['bill_type'] ?? 'total';
        $b_id = !empty($_POST['bill_id']) ? (int)$_POST['bill_id'] : null;
        $amt = (float)$_POST['amount'];
        $tr_id = trim($_POST['transaction_id'] ?? '');
        $month = $_POST['month'] ?? '';

        $payment_method = $_POST['payment_method'] ?? 'UPI';
        if (empty($tr_id)) {
            $tr_id = 'SYS-' . strtoupper(bin2hex(random_bytes(6)));
        }
        $sys_tx_id = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));

        $bill_valid = true;
        if ($b_id > 0 && $b_type === 'rent') {
            $ck = mysqli_query($conn, "SELECT id FROM rent WHERE id=$b_id AND user_id=$user_id");
            if (mysqli_num_rows($ck) == 0) $bill_valid = false;
        } else if ($b_id > 0 && $b_type === 'electricity') {
            $ck = mysqli_query($conn, "SELECT id FROM electricity WHERE id=$b_id AND user_id=$user_id");
            if (mysqli_num_rows($ck) == 0) $bill_valid = false;
        }

        if ($amt <= 0) {
            $payment_error = "Payment amount must be greater than zero.";
        } else if (!$bill_valid) {
            $payment_error = "Invalid bill reference. You can only pay your own bills.";
        } else if ($payment_method === 'UPI' && strpos($tr_id, 'SYS-') === 0) {"""

target2 = """            $is_duplicate = false;
            if ($payment_method === 'UPI' && !empty($tr_id)) {
                // Check for duplicate UTR
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $tr_id);
                mysqli_stmt_execute($check_stmt);
                $check_res = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_res) > 0) {
                    $is_duplicate = true;
                }
            }"""

replacement2 = """            $is_duplicate = false;
            if ($payment_method === 'UPI' && !empty($tr_id) && strpos($tr_id, 'SYS-') !== 0) {
                // Check for duplicate UTR
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
            }"""

if target in content and target2 in content:
    content = content.replace(target, replacement)
    content = content.replace(target2, replacement2)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched my-bills.php")
else:
    print("Targets not found")
