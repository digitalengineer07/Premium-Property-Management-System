import os
import re

directories = [
    r'c:\xampp\htdocs\renter-system',
    r'c:\xampp\htdocs\renter-system\admin',
    r'c:\xampp\htdocs\renter-system\renter',
]

pattern = re.compile(r'mysqli_query\s*\([^,]+,\s*["\'].*?\$_(GET|POST)\[.*?["\']')

for d in directories:
    for filename in os.listdir(d):
        if filename.endswith(".php"):
            filepath = os.path.join(d, filename)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
                matches = pattern.finditer(content)
                for match in matches:
                    print(f"Potential SQLi in {filepath}: {match.group(0)}")

