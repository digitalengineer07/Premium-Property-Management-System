import os
import re

files_to_fix = [
    r'c:\xampp\htdocs\renter-system\renter\dashboard.php',
    r'c:\xampp\htdocs\renter-system\renter\my-payments.php',
    r'c:\xampp\htdocs\renter-system\renter\my-bills.php',
    r'c:\xampp\htdocs\renter-system\renter\payment-history.php'
]

for filepath in files_to_fix:
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        modified = False

        # Add sys_tx_id generation
        if "$payment_method = $_POST['payment_method'] ?? 'UPI';" in content and "$sys_tx_id = 'PAY-'" not in content:
            content = content.replace(
                "$payment_method = $_POST['payment_method'] ?? 'UPI';",
                "$payment_method = $_POST['payment_method'] ?? 'UPI';\n        $sys_tx_id = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));"
            )
            modified = True

        # Update INSERT query
        old_insert = 'INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)'
        new_insert = 'INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method, sys_tx_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        
        if old_insert in content:
            content = content.replace(old_insert, new_insert)
            content = re.sub(
                r'mysqli_stmt_bind_param\(\$stmt,\s*"isidsss",\s*\$user_id,\s*\$b_type,\s*\$b_id,\s*\$amt,\s*\$tr_id,\s*\$month,\s*\$payment_method\);',
                'mysqli_stmt_bind_param($stmt, "isidssss", $user_id, $b_type, $b_id, $amt, $tr_id, $month, $payment_method, $sys_tx_id);',
                content
            )
            modified = True

        if modified:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated backend submission in {os.path.basename(filepath)}")
