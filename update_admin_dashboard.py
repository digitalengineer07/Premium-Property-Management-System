import os
import re

filepath = r'c:\xampp\htdocs\renter-system\admin\dashboard.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """$p_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type='electricity' AND e.status='Partial'"))['total'];
$p_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN rent r ON p.bill_id = r.id WHERE p.bill_type='rent' AND r.status='Partial'"))['total'];
$total_dues_total = max(0, ($d_elec + $d_rent) - ($p_elec + $p_rent));"""

replacement = """$p_elec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type='electricity' AND e.status='Partial'"))['total'];
$p_elec_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN electricity e ON p.bill_id = e.id WHERE p.bill_type='elec_rent' AND e.status='Partial'"))['total'];
$p_rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(p.paid_amount),0) AS total FROM payments p JOIN rent r ON p.bill_id = r.id WHERE p.bill_type='rent' AND r.status='Partial'"))['total'];

// Include pending negative adjustments (money users owe)
$d_adj = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(ABS(pending_adjustment)),0) AS total FROM users WHERE pending_adjustment < 0"))['total'];
// Include pending positive adjustments (advances that offset dues)
$adv_adj = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(pending_adjustment),0) AS total FROM users WHERE pending_adjustment > 0"))['total'];

$total_dues_total = max(0, ($d_elec + $d_rent + $d_adj) - ($p_elec + $p_elec_rent + $p_rent + $adv_adj));"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated dashboard.php")
else:
    print("Target not found")
