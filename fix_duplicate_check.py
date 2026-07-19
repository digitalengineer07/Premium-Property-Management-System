import os

files = [
    'renter/payment-history.php',
    'renter/payment-approvals.php',
    'renter/my-payments.php',
    'renter/my-bills.php',
    'renter/dashboard.php'
]

old_text = """$check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $tr_id);"""

new_text = """$check_stmt = mysqli_prepare($conn, "
                    SELECT id FROM payment_notifications WHERE transaction_id = ?
                    UNION 
                    SELECT id FROM payments WHERE transaction_id = ?
                ");
                mysqli_stmt_bind_param($check_stmt, "ss", $tr_id, $tr_id);"""

for f in files:
    if os.path.exists(f):
        with open(f, 'r', encoding='utf-8') as file:
            content = file.read()
            
        if old_text in content:
            content = content.replace(old_text, new_text)
            with open(f, 'w', encoding='utf-8') as file:
                file.write(content)
            print(f"Updated {f}")
        else:
            print(f"String not found in {f}, skipping or trying alternative format.")
            # Alternative format with slightly different whitespace
            import re
            pattern = re.compile(r'\$check_stmt\s*=\s*mysqli_prepare\(\$conn,\s*"SELECT id FROM payment_notifications WHERE transaction_id = \?"\);\s*mysqli_stmt_bind_param\(\$check_stmt,\s*"s",\s*\$tr_id\);')
            if pattern.search(content):
                content = pattern.sub(new_text, content)
                with open(f, 'w', encoding='utf-8') as file:
                    file.write(content)
                print(f"Updated {f} using regex")
    else:
        print(f"File not found: {f}")
