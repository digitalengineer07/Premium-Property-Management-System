import os

def update_file(filepath):
    if not os.path.exists(filepath):
        return
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # In my-bills.php and my-payments_mobile.php, the SQL queries for rent, electricity, etc.
    # Rent query in my-bills.php
    old_rent = """SELECT r.id, r.month, r.rent_amount as amount, r.status, p.adjustment_amount, p.adjustment_type 
      FROM rent r 
      LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type 
FROM payments WHERE bill_type = 'rent' GROUP BY bill_id) p ON p.bill_id = r.id 
      WHERE r.user_id = ? 
      ORDER BY r.id DESC LIMIT 10"""
      
    new_rent = """SELECT r.id, r.month, 
      (r.rent_amount - IFNULL((SELECT SUM(paid_amount) FROM payments p2 WHERE p2.bill_type='rent' AND p2.bill_id=r.id), 0)) as amount, 
      r.status, p.adjustment_amount, p.adjustment_type 
      FROM rent r 
      LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'rent' GROUP BY bill_id) p ON p.bill_id = r.id 
      WHERE r.user_id = ? 
      ORDER BY r.id DESC LIMIT 10"""
      
    content = content.replace(old_rent, new_rent)

    # Elec rent portion query in my-bills.php
    old_elec_rent = """SELECT e.id, e.month, (e.rent_amount + e.maintenance + e.dues) as amount, e.status, p.adjustment_amount, p.adjustment_type 
      FROM electricity e 
      LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'electricity' GROUP BY bill_id) p ON p.bill_id = e.id 
      WHERE e.user_id = ? AND e.status != 'Paid' AND (e.rent_amount + e.maintenance + e.dues) > 0
      ORDER BY e.id DESC LIMIT 10"""
      
    new_elec_rent = """SELECT e.id, e.month, 
      ((e.rent_amount + e.maintenance + e.dues) - IFNULL((SELECT SUM(paid_amount) FROM payments p2 WHERE p2.bill_type='elec_rent' AND p2.bill_id=e.id), 0)) as amount, 
      e.status, p.adjustment_amount, p.adjustment_type 
      FROM electricity e 
      LEFT JOIN (SELECT bill_id, MAX(adjustment_amount) as adjustment_amount, MAX(adjustment_type) as adjustment_type FROM payments WHERE bill_type = 'elec_rent' GROUP BY bill_id) p ON p.bill_id = e.id 
      WHERE e.user_id = ? AND e.rent_status != 'Paid' AND (e.rent_amount + e.maintenance + e.dues) > 0
      ORDER BY e.id DESC LIMIT 10"""
      
    content = content.replace(old_elec_rent, new_elec_rent)

    # Elec electricity portion query in my-bills.php
    old_elec_elec = """SELECT e.id, e.month, e.amount, e.units_consumed, e.status 
      FROM electricity e 
      WHERE e.user_id = ? 
      ORDER BY e.id DESC LIMIT 10"""
      
    new_elec_elec = """SELECT e.id, e.month, 
      (e.amount - IFNULL((SELECT SUM(paid_amount) FROM payments p2 WHERE p2.bill_type='electricity' AND p2.bill_id=e.id), 0)) as amount, 
      e.units_consumed, e.status 
      FROM electricity e 
      WHERE e.user_id = ? 
      ORDER BY e.id DESC LIMIT 10"""
      
    content = content.replace(old_elec_elec, new_elec_elec)

    # Update dashboard.php bills (upcoming bills widget)
    old_dash_rent = """SELECT id, month, rent_amount as amount, due_date, status, 'rent' as type 
FROM rent WHERE user_id = ? AND status IN ('Due', 'Partial')"""
    
    new_dash_rent = """SELECT id, month, (rent_amount - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='rent' AND p.bill_id=rent.id), 0)) as amount, due_date, status, 'rent' as type 
FROM rent WHERE user_id = ? AND status IN ('Due', 'Partial')"""
    
    old_dash_elec_rent = """SELECT id, month, (rent_amount + maintenance + dues) as amount, due_date, rent_status as status, 'elec_rent' as type 
FROM electricity WHERE user_id = ? AND rent_status IN ('Due', 'Partial')"""
    
    new_dash_elec_rent = """SELECT id, month, ((rent_amount + maintenance + dues) - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='elec_rent' AND p.bill_id=electricity.id), 0)) as amount, due_date, rent_status as status, 'elec_rent' as type 
FROM electricity WHERE user_id = ? AND rent_status IN ('Due', 'Partial')"""
    
    old_dash_elec_elec = """SELECT id, month, amount, due_date, elec_status as status, 'electricity' as type 
FROM electricity WHERE user_id = ? AND elec_status IN ('Due', 'Partial')"""
    
    new_dash_elec_elec = """SELECT id, month, (amount - IFNULL((SELECT SUM(paid_amount) FROM payments p WHERE p.bill_type='electricity' AND p.bill_id=electricity.id), 0)) as amount, due_date, elec_status as status, 'electricity' as type 
FROM electricity WHERE user_id = ? AND elec_status IN ('Due', 'Partial')"""
    
    content = content.replace(old_dash_rent, new_dash_rent)
    content = content.replace(old_dash_elec_rent, new_dash_elec_rent)
    content = content.replace(old_dash_elec_elec, new_dash_elec_elec)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for fp in [
    r'c:\xampp\htdocs\renter-system\renter\my-bills.php',
    r'c:\xampp\htdocs\renter-system\renter\dashboard.php'
]:
    update_file(fp)
