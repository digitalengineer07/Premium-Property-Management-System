import mysql.connector

conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="renter_system"
)

cursor = conn.cursor(dictionary=True)

print("--- payment_notifications ---")
cursor.execute("SELECT id, sys_tx_id, transaction_id, status FROM payment_notifications ORDER BY id DESC LIMIT 5")
for row in cursor.fetchall():
    print(row)

print("\n--- payments ---")
cursor.execute("SELECT id, sys_tx_id, transaction_id FROM payments ORDER BY id DESC LIMIT 5")
for row in cursor.fetchall():
    print(row)

conn.close()
