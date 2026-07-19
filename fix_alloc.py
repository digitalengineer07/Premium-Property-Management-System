import os

filepath = r'c:\xampp\htdocs\renter-system\admin\allocate_payment.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """    // 2. Electricity (elec_rent part and electricity part)
    $qElec = mysqli_query($conn, "SELECT id, month, due_date, amount as elec_part, (rent_amount + maintenance) as rent_part FROM electricity WHERE user_id=$user_id AND status IN ('Due', 'Partial')");"""

replacement = """    // 2. Electricity (elec_rent part and electricity part)
    $qElec = mysqli_query($conn, "SELECT id, month, due_date, amount as elec_part, (rent_amount + maintenance + dues) as rent_part FROM electricity WHERE user_id=$user_id AND status IN ('Due', 'Partial')");"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Successfully replaced in allocate_payment.php")
else:
    print("Target not found.")
