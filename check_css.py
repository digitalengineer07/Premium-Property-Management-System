import os
filepath = r'c:\xampp\htdocs\renter-system\assets\css\admin-design-system.css'

with open(filepath, 'r', encoding='utf-8') as f:
    lines = f.readlines()

brace_count = 0
for i, line in enumerate(lines):
    for char in line:
        if char == '{':
            brace_count += 1
        elif char == '}':
            brace_count -= 1
            if brace_count < 0:
                print(f"Extra closing brace found at line {i+1}")
                brace_count = 0

print(f"Final brace count: {brace_count}")
if brace_count > 0:
    print("There is a missing closing brace '}' somewhere in the file.")
