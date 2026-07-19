import os

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\dashboard.php',
    r'c:\xampp\htdocs\renter-system\renter\my-bills.php',
    r'c:\xampp\htdocs\renter-system\renter\payment-history.php'
]

target_str = "THEN (rent_amount + maintenance)"
replacement_str = "THEN (rent_amount + maintenance + dues)"

for filepath in files_to_update:
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if target_str in content:
            content = content.replace(target_str, replacement_str)
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Reverted {filepath}")
        else:
            print(f"Target not found in {filepath}")
    else:
        print(f"File not found: {filepath}")

# For admin/allocate_payment.php
filepath = r'c:\xampp\htdocs\renter-system\admin\allocate_payment.php'
if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    target_alloc = "amount as elec_part, (rent_amount + maintenance) as rent_part"
    replacement_alloc = "amount as elec_part, (rent_amount + maintenance + dues) as rent_part"
    
    if target_alloc in content:
        content = content.replace(target_alloc, replacement_alloc)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Reverted {filepath}")
    else:
        print(f"Target not found in {filepath}")
