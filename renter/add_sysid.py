import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target1 = "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A'),"
replacement1 = "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),"

target2 = "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline'),"
replacement2 = "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),"

if target1 in content or target2 in content:
    content = content.replace(target1, replacement1)
    content = content.replace(target2, replacement2)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Re-added SysID to payment-history_desktop.php")
else:
    print("Targets not found")
