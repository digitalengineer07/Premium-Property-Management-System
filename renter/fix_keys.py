import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace month with period for the first array
content = content.replace("'month' => $row['month'] ?: 'Multiple'", "'period' => $row['month'] ?: 'Multiple'")
content = content.replace("'pdate' => date('d M Y', strtotime($row['p_date']))", "'paid_on' => date('d M Y', strtotime($row['p_date']))")

# Replace month with period for the second array
content = content.replace("'month' => $row['period'] ?: 'Multiple'", "'period' => $row['period'] ?: 'Multiple'")

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Fixed array keys")
