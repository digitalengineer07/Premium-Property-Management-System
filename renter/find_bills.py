import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'
with open(filepath, 'r', encoding='utf-8') as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if 'all_bills' in line:
        print(f"Match around line {i+1}")
        for j in range(max(0, i-5), min(len(lines), i+15)):
            print(f"{j+1}: {lines[j].strip()}")
        break
