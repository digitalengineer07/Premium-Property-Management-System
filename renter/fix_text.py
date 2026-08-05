import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace "Consolidated Payment"
content = content.replace("$title = 'Consolidated Payment';", "$title = 'Online Payment';")

# Replace "Admin Manual Payment"
content = content.replace("'title' => 'Admin Manual Payment',", "'title' => 'Cash / Offline Payment',")

# Replace "SysID: PAY-FIX-31-964D | UTR: N/A"
# Old: 'subtitle' => 'SysID: ' . ($row['sys_tx_id'] ?: 'N/A') . ' | UTR: ' . ($row['transaction_id'] ?: 'N/A'),
# New: 'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A'),
content = content.replace(
    "'subtitle' => 'SysID: ' . ($row['sys_tx_id'] ?: 'N/A') . ' | UTR: ' . ($row['transaction_id'] ?: 'N/A'),",
    "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A'),"
)

# Replace "SysID: N/A | Ref: Offline"
# Old: 'subtitle' => 'SysID: ' . ($row['sys_tx_id'] ?: 'N/A') . ' | Ref: ' . ($row['transaction_id'] ?: 'Offline'),
# New: 'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline'),
content = content.replace(
    "'subtitle' => 'SysID: ' . ($row['sys_tx_id'] ?: 'N/A') . ' | Ref: ' . ($row['transaction_id'] ?: 'Offline'),",
    "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline'),"
)

# Replace "Advance/General"
# Oh wait, $row['month'] ?: 'Multiple'
# Wait, "Advance/General" is probably the data in the db for the 'month' column.
# 'period' => $row['month'] ?: 'Multiple',
# If it's "Advance" or "Advance/General", let's render it as "Advance Balance".
content = content.replace(
    "'period' => $row['month'] ?: 'Multiple',",
    "'period' => ($row['month'] == 'Advance' || $row['month'] == 'Advance/General') ? 'Advance Balance' : ($row['month'] ?: 'Multiple'),"
)

content = content.replace(
    "'period' => $row['period'] ?: 'Multiple',",
    "'period' => ($row['period'] == 'Advance' || $row['period'] == 'Advance/General') ? 'Advance Balance' : ($row['period'] ?: 'Multiple'),"
)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated text on payment-history_desktop.php")
