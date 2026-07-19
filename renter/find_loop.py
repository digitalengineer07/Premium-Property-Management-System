import os
filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'
with open(filepath, 'r', encoding='utf-8') as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if 'foreach' in line and '$merged_rents' in line:
        print(f"Match around line {i+1}")
        for j in range(max(0, i-2), min(len(lines), i+40)):
            print(f"{j+1}: {lines[j].strip()}")
        break
