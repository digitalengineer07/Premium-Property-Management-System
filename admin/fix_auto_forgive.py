import os

filepath = r'c:\xampp\htdocs\renter-system\admin\allocate_payment.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Remove Rent Auto-forgiveness
target_rent = """            // Clear prior unpaid if fully paid
            if ($new_status === 'Paid') {
                mysqli_query($conn, "UPDATE rent SET status='Paid' WHERE user_id={$b['user_id']} AND status IN ('Due', 'Partial') AND id < $bill_id");
            }"""

# Remove Electricity Auto-forgiveness
target_elec = """            // Clear prior unpaid if fully paid
            if ($overall_status === 'Paid') {
                mysqli_query($conn, "UPDATE electricity SET status='Paid', elec_status='Paid', rent_status='Paid' WHERE user_id={$b['user_id']} AND status IN ('Due', 'Partial') AND id < $bill_id");
            }"""

if target_rent in content or target_elec in content:
    content = content.replace(target_rent, "")
    content = content.replace(target_elec, "")
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched massive auto-forgiveness bug in allocate_payment.php")
else:
    print("Targets not found! Already patched?")
