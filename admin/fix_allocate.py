import os

filepath = r'c:\xampp\htdocs\renter-system\admin\allocate_payment.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = "function allocate_bulk_payment($conn, $user_id, $amount, $payment_mode, $transaction_id, $sys_tx_id, $max_month_str = null) {"
replacement = """function allocate_bulk_payment($conn, $user_id, $amount, $payment_mode, $transaction_id, $sys_tx_id, $max_month_str = null) {
    if ((float)$amount <= 0) return;"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Injected negative amount defense in allocate_bulk_payment")
else:
    print("Target not found")
