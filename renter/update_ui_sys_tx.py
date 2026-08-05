import os
import re

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php',
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-history_mobile.php'
]

for filepath in files_to_update:
    if not os.path.exists(filepath):
        continue
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace the subtitle for payment_notifications
    content = content.replace(
        "'subtitle' => 'UTR: ' . ($row['transaction_id'] ?: 'N/A'),",
        "'subtitle' => 'SysID: ' . ($row['sys_tx_id'] ?: 'N/A') . ' | UTR: ' . ($row['transaction_id'] ?: 'N/A'),"
    )

    # Replace the subtitle for payments
    content = content.replace(
        "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline'),",
        "'subtitle' => 'SysID: ' . ($row['sys_tx_id'] ?: 'N/A') . ' | Ref: ' . ($row['transaction_id'] ?: 'Offline'),"
    )

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

# Also update admin panel payment verifications to show sys_tx_id
admin_path = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'
with open(admin_path, 'r', encoding='utf-8') as f:
    admin_content = f.read()

admin_content = admin_content.replace(
    '<th>REF NO / UTR</th>',
    '<th>SYS. TXN ID</th>\n                            <th>REF NO / UTR</th>'
)

# For the table body in admin:
# We need to find where it outputs the transaction_id and add a column before it.
# Let's see what the table body code looks like for admin.
