import mysql.connector

# Connect to database
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="renter_system"
)

cursor = db.cursor(dictionary=True)

cursor.execute("SELECT id, name FROM users WHERE role = 'renter'")
users = cursor.fetchall()

print("User balances check:")
for user in users:
    uid = user['id']
    name = user['name']
    
    # Calculate electricity due
    cursor.execute(f"SELECT SUM(amount) as elec_sum FROM electricity WHERE user_id = {uid} AND (elec_status IN ('Due', 'Partial') OR elec_status IS NULL) AND status IN ('Due', 'Partial')")
    elec_res = cursor.fetchone()
    elec_sum = elec_res['elec_sum'] if elec_res['elec_sum'] else 0
    
    cursor.execute(f"SELECT SUM(paid_amount) as elec_paid FROM payments WHERE user_id = {uid} AND bill_type='electricity'")
    elec_paid_res = cursor.fetchone()
    elec_paid = elec_paid_res['elec_paid'] if elec_paid_res['elec_paid'] else 0
    
    # Calculate rent due
    cursor.execute(f"SELECT SUM(rent_amount + maintenance + dues + extra_charges) as rent_sum FROM electricity WHERE user_id = {uid} AND (rent_status IN ('Due', 'Partial') OR rent_status IS NULL) AND status IN ('Due', 'Partial')")
    rent_res = cursor.fetchone()
    rent_sum = rent_res['rent_sum'] if rent_res['rent_sum'] else 0
    
    cursor.execute(f"SELECT SUM(paid_amount) as rent_paid FROM payments WHERE user_id = {uid} AND bill_type='elec_rent'")
    rent_paid_res = cursor.fetchone()
    rent_paid = rent_paid_res['rent_paid'] if rent_paid_res['rent_paid'] else 0
    
    cursor.execute(f"SELECT pending_adjustment FROM users WHERE id = {uid}")
    adj_res = cursor.fetchone()
    adj = adj_res['pending_adjustment'] if adj_res['pending_adjustment'] else 0
    
    e_due = max(0, elec_sum - elec_paid)
    r_due = max(0, rent_sum - rent_paid)
    
    total = float(e_due) + float(r_due) - float(adj)
    
    if total != 0:
        print(f"[{uid}] {name} - Total: {total} (Elec Due: {e_due}, Rent Due: {r_due}, Adj: {adj})")
    
print("Check completed.")
