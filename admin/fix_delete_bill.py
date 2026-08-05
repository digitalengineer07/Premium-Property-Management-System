import os
import re

filepath = r'c:\xampp\htdocs\renter-system\admin\delete-bill.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace electricity check
old_elec_check = """    $e_q = mysqli_query($conn, "SELECT status FROM electricity WHERE id = $id");
    if ($e_q && $row = mysqli_fetch_assoc($e_q)) {
        if ($row['status'] == 'Paid') {
            die("<script>alert('Error: Cannot delete a fully Paid electricity bill to protect accounting integrity.'); window.history.back();</script>");
        }
    }"""

new_elec_check = """    $e_q = mysqli_query($conn, "SELECT status, elec_status, rent_status FROM electricity WHERE id = $id");
    if ($e_q && $row = mysqli_fetch_assoc($e_q)) {
        if ($row['status'] == 'Paid' || $row['status'] == 'Partial' || $row['elec_status'] == 'Paid' || $row['elec_status'] == 'Partial' || $row['rent_status'] == 'Paid' || $row['rent_status'] == 'Partial') {
            die("<script>alert('Error: Cannot delete a bill that has associated payments (Paid or Partial) to protect accounting integrity. Please reverse the payments first.'); window.history.back();</script>");
        }
    }"""

content = content.replace(old_elec_check, new_elec_check)

# Replace rent check
old_rent_check = """    $r_q = mysqli_query($conn, "SELECT status FROM rent WHERE id = $id");
    if ($r_q && $row = mysqli_fetch_assoc($r_q)) {
        if ($row['status'] == 'Paid') {
            die("<script>alert('Error: Cannot delete a fully Paid rent bill to protect accounting integrity.'); window.history.back();</script>");
        }
    }"""

new_rent_check = """    $r_q = mysqli_query($conn, "SELECT status FROM rent WHERE id = $id");
    if ($r_q && $row = mysqli_fetch_assoc($r_q)) {
        if ($row['status'] == 'Paid' || $row['status'] == 'Partial') {
            die("<script>alert('Error: Cannot delete a bill that has associated payments (Paid or Partial) to protect accounting integrity. Please reverse the payments first.'); window.history.back();</script>");
        }
    }"""

content = content.replace(old_rent_check, new_rent_check)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Hardened delete-bill.php")
