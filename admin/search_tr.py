import os

filepath = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# I want to add sys_tx_id to the table.
# Let's see what the table row looks like right now by searching for "transaction_id" in the file
import re
matches = re.findall(r'<td.*transaction_id.*</td>', content)
if matches:
    print(matches)
else:
    # Just print the lines around 'REF NO / UTR'
    lines = content.split('\n')
    for i, line in enumerate(lines):
        if 'REF NO / UTR' in line:
            for j in range(max(0, i-5), min(len(lines), i+15)):
                print(lines[j])
            break
