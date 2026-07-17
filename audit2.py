import os
import re

print("Starting Deep Security Audit...")
base_dir = r"c:\xampp\htdocs\renter-system"

sql_pattern_1 = re.compile(r'\$_(GET|POST|REQUEST)\[.*?\]')
query_pattern = re.compile(r'mysqli_query\s*\(\s*\$conn\s*,([^;]+)\)')

issues = []

for root, _, files in os.walk(base_dir):
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                lines = f.readlines()
                
                for i, line in enumerate(lines):
                    # Find query calls
                    q_match = query_pattern.search(line)
                    if q_match:
                        query_string = q_match.group(1)
                        if sql_pattern_1.search(query_string):
                            issues.append(f"[SQLi] Potential direct injection in {filepath}:{i+1} -> {line.strip()}")

for issue in issues:
    print(issue)
print("Deep Audit complete.")
