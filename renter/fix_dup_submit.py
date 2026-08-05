import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\payment-history.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """            if ($payment_method === 'UPI' && !empty($tr_id)) {
                // Check for duplicate UTR
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $tr_id);
                mysqli_stmt_execute($check_stmt);
                $check_res = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_res) > 0) {
                    $is_duplicate = true;
                }
            }"""

replacement = """            if ($payment_method === 'UPI' && !empty($tr_id)) {
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

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Updated duplicate checking logic")
else:
    print("Target not found")
