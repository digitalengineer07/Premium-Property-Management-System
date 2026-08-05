import os

filepath = r'c:\xampp\htdocs\renter-system\renter\my-payments.php'

target = "THEN (rent_amount + maintenance + dues) - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='elec_rent' AND p.bill_id=e.id), 0)"
replacement = "THEN (rent_amount + maintenance) - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='elec_rent' AND p.bill_id=e.id), 0)"

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
