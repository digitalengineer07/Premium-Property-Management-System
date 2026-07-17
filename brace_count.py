import sys
lines = open(r'c:\xampp\htdocs\renter-system\assets\css\admin-design-system.css', 'r', encoding='utf-8').readlines()
b=0
for i in range(1624, 1867):
    l = lines[i]
    if '{' in l or '}' in l:
        b += l.count('{') - l.count('}')
        print(f"L{i+1} B{b} : {l.strip()}")
