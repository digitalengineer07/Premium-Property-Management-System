import os

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\dashboard.php',
    r'c:\xampp\htdocs\renter-system\renter\my-bills.php',
    r'c:\xampp\htdocs\renter-system\renter\my-payments.php',
    r'c:\xampp\htdocs\renter-system\renter\payment-history.php'
]

target = "$sys_tx_id = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));"
replacement = "$sys_tx_id = 'TXN-' . date('md') . '-' . strtoupper(bin2hex(random_bytes(4)));"

for filepath in files_to_update:
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if target in content:
            content = content.replace(target, replacement)
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated {filepath}")
        else:
            print(f"Target not found in {filepath}")
    else:
        print(f"File not found: {filepath}")
