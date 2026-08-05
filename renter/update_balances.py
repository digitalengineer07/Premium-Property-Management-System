import os

def update_file(filepath):
    if not os.path.exists(filepath):
        print(f"Skipping {filepath}, does not exist.")
        return
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Replace rent calculation
    old_rent = """// 1. Rent from pure 'rent' table
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(rent_amount),0) as total FROM rent WHERE user_id = ? AND status = 'Due'");"""
    
    new_rent = """// 1. Rent from pure 'rent' table (including Partial)
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(rent_amount), 0) - 
    IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='rent' AND p.bill_id=r.id), 0)
    AS total 
    FROM rent r WHERE user_id = ? AND status IN ('Due', 'Partial')");"""

    content = content.replace(old_rent, new_rent)
    
    # Replace electricity calculation
    old_elec_1 = """// 2. Electricity and Rent components from 'electricity' table
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(CASE WHEN elec_status = 'Due' OR (elec_status = '' AND status = 'Due') OR (status = 'Due' AND elec_status != 'Paid') THEN amount ELSE 0 END), 0) as elec_total, 
    IFNULL(SUM(CASE WHEN rent_status = 'Due' OR (rent_status = '' AND status = 'Due') OR (status = 'Due' AND rent_status != 'Paid') THEN (rent_amount + maintenance + dues) ELSE 0 END), 0) as rent_portion_total 
FROM electricity WHERE user_id = ?");"""

    old_elec_2 = """// 2. Electricity and Rent components from 'electricity' table
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(CASE WHEN elec_status = 'Due' OR (elec_status = '' AND status = 'Due') OR (status = 'Due' AND elec_status = 'Due') THEN amount ELSE 0 END), 0) as elec_total, 
    IFNULL(SUM(CASE WHEN rent_status = 'Due' OR (rent_status = '' AND status = 'Due') OR (status = 'Due' AND rent_status = 'Due') THEN (rent_amount + maintenance + dues) ELSE 0 END), 0) as rent_portion_total 
FROM electricity WHERE user_id = ?");"""

    new_elec = """// 2. Electricity and Rent components from 'electricity' table (including Partial)
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(
        CASE WHEN elec_status IN ('Due', 'Partial') OR (elec_status = '' AND status IN ('Due', 'Partial')) OR (status IN ('Due', 'Partial') AND elec_status != 'Paid')
        THEN amount - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='electricity' AND p.bill_id=e.id), 0) 
        ELSE 0 END
    ), 0) as elec_total, 
    IFNULL(SUM(
        CASE WHEN rent_status IN ('Due', 'Partial') OR (rent_status = '' AND status IN ('Due', 'Partial')) OR (status IN ('Due', 'Partial') AND rent_status != 'Paid')
        THEN (rent_amount + maintenance + dues) - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='elec_rent' AND p.bill_id=e.id), 0) 
        ELSE 0 END
    ), 0) as rent_portion_total 
FROM electricity e WHERE user_id = ?");"""

    content = content.replace(old_elec_1, new_elec)
    content = content.replace(old_elec_2, new_elec)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Updated {os.path.basename(filepath)}")

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\dashboard.php',
    r'c:\xampp\htdocs\renter-system\renter\my-bills.php',
    r'c:\xampp\htdocs\renter-system\renter\my-payments.php'
]

for fp in files_to_update:
    update_file(fp)
