import os

filepath = r'c:\xampp\htdocs\renter-system\renter\payment-history.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = 'mysqli_stmt_bind_param($stmt, "isidsss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method);'
replacement = 'mysqli_stmt_bind_param($stmt, "isidssss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method, $sys_tx_id);'

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed bind_param in payment-history.php")
else:
    print("Target not found in payment-history.php")
