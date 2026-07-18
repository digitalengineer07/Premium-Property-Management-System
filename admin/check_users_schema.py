import os
import mysql.connector

try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="renter_system"
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SHOW COLUMNS FROM users")
    for row in cursor.fetchall():
        print(row['Field'])
except Exception as e:
    print(f"Error: {e}")
