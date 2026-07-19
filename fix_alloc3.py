import os

filepath = r'c:\xampp\htdocs\renter-system\admin\allocate_payment.php'

with open(filepath, 'r') as f:
    content = f.read()

target1 = """$rent_part = (float)$b['rent_amount'] + (float)$b['maintenance'] + (float)$b['dues'];"""
replacement1 = """$rent_part = (float)$b['rent_amount'] + (float)$b['maintenance'] + (float)$b['dues'] + (float)$b['extra_charges'];"""

target2 = """$qElec = mysqli_query($conn, "SELECT id, month, due_date, amount as elec_part, (rent_amount + maintenance + dues) as rent_part FROM electricity WHERE user_id=$user_id AND status IN ('Due', 'Partial')");"""
replacement2 = """$qElec = mysqli_query($conn, "SELECT id, month, due_date, amount as elec_part, (rent_amount + maintenance + dues + extra_charges) as rent_part FROM electricity WHERE user_id=$user_id AND status IN ('Due', 'Partial')");"""

target3 = """$qBill = mysqli_query($conn, "SELECT amount, rent_amount, maintenance, dues, total_amount, user_id FROM electricity WHERE id=$bill_id");"""
replacement3 = """$qBill = mysqli_query($conn, "SELECT amount, rent_amount, maintenance, dues, extra_charges, total_amount, user_id FROM electricity WHERE id=$bill_id");"""

if target1 in content:
    content = content.replace(target1, replacement1)
else:
    print("target1 not found")

if target2 in content:
    content = content.replace(target2, replacement2)
else:
    print("target2 not found")
    
if target3 in content:
    content = content.replace(target3, replacement3)
else:
    print("target3 not found")

with open(filepath, 'w') as f:
    f.write(content)
print("Updated allocate_payment.php")
