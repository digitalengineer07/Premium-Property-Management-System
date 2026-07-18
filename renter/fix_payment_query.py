import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the missing column in SELECT
old_query = '"SELECT id, amount, month, payment_method as payment_mode, status, transaction_id, created_at as p_date, sys_tx_id, admin_note FROM payment_notifications WHERE user_id = $user_id ORDER BY id DESC LIMIT 50"'
new_query = '"SELECT id, bill_type, amount, month, payment_method as payment_mode, status, transaction_id, created_at as p_date, sys_tx_id, admin_note FROM payment_notifications WHERE user_id = $user_id ORDER BY id DESC LIMIT 50"'

if old_query in content:
    content = content.replace(old_query, new_query)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed missing column in query")
else:
    print("Query not found! Maybe it was formatted differently.")
