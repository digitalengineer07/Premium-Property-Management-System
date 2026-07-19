import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'

with open(filepath, 'r') as f:
    content = f.read()

# Replace all occurrences of min((float)$bill['amount'], (float)$total_due) with remaining_amount check.
replacement = "min((float)(isset($bill['remaining_amount']) ? $bill['remaining_amount'] : $bill['amount']), (float)$total_due)"
content = content.replace("min((float)$bill['amount'], (float)$total_due)", replacement)

with open(filepath, 'w') as f:
    f.write(content)
print("Updated my-payments_desktop.php")
