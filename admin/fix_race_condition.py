import os

filepath = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Fix Approve Race Condition
target_approve = """                $upd_status = mysqli_query($conn, "UPDATE payment_notifications SET status='Approved', admin_note='$admin_note', verified_by='$admin_user', verified_at=NOW() WHERE id=$id");
                
                if ($upd_status) {"""

replacement_approve = """                $upd_status = mysqli_query($conn, "UPDATE payment_notifications SET status='Approved', admin_note='$admin_note', verified_by='$admin_user', verified_at=NOW() WHERE id=$id AND status='Pending'");
                
                if ($upd_status && mysqli_affected_rows($conn) > 0) {"""

# Fix Reject Race Condition
target_reject = """                $upd_status = mysqli_query($conn, "UPDATE payment_notifications SET status='Rejected', admin_note='$admin_note', verified_by='$admin_user', verified_at=NOW() WHERE id=$id");
                if ($upd_status) {"""

replacement_reject = """                $upd_status = mysqli_query($conn, "UPDATE payment_notifications SET status='Rejected', admin_note='$admin_note', verified_by='$admin_user', verified_at=NOW() WHERE id=$id AND status='Pending'");
                if ($upd_status && mysqli_affected_rows($conn) > 0) {"""

if target_approve in content and target_reject in content:
    content = content.replace(target_approve, replacement_approve)
    content = content.replace(target_reject, replacement_reject)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched race condition in payment-verifications.php")
else:
    print("Targets not found!")
    if target_approve not in content: print("Approve target missing")
    if target_reject not in content: print("Reject target missing")
