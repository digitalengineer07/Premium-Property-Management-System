import os
import glob
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

        # Add $month extraction
        if "$tr_id = trim($_POST['transaction_id'] ?? '');" in content and "$month = $_POST['month'] ?? '';" not in content:
            content = content.replace(
                "$tr_id = trim($_POST['transaction_id'] ?? '');",
                "$tr_id = trim($_POST['transaction_id'] ?? '');\n        $month = $_POST['month'] ?? '';"
            )
            modified = True
            print(f"Added $month to {os.path.basename(filepath)}")

        # Update INSERT query
        old_insert_1 = 'INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, payment_method) VALUES (?, ?, ?, ?, ?, ?)'
        new_insert_1 = 'INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)'

        if old_insert_1 in content:
            content = content.replace(old_insert_1, new_insert_1)
            # Update bind_param
            # Old: mysqli_stmt_bind_param($stmt, "isidss", $user_id, $b_type, $b_id, $amt, $tr_id, $payment_method);
            # New: mysqli_stmt_bind_param($stmt, "isidsss", $user_id, $b_type, $b_id, $amt, $tr_id, $month, $payment_method);
            content = re.sub(
                r'mysqli_stmt_bind_param\(\$stmt,\s*"isidss",\s*\$user_id,\s*\$b_type,\s*\$b_id,\s*\$amt,\s*\$tr_id,\s*\$payment_method\);',
                'mysqli_stmt_bind_param($stmt, "isidsss", $user_id, $b_type, $b_id, $amt, $tr_id, $month, $payment_method);',
                content
            )
            modified = True
            print(f"Updated INSERT in {os.path.basename(filepath)}")

        if modified:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
