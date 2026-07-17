import os
import re

print("Starting Advanced Security Audit...")
base_dir = r"c:\xampp\htdocs\renter-system"

# Match queries containing unescaped variables like $user_id, $id, etc
# Excludes prepared statements (mysqli_prepare) and strictly queries that use string interpolation
query_pattern = re.compile(r'mysqli_query\s*\(\s*\$conn\s*,\s*".*?(?<!\\)(\$[a-zA-Z0-9_]+).*?"', re.IGNORECASE)

issues = []

for root, _, files in os.walk(base_dir):
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                lines = f.readlines()
                for i, line in enumerate(lines):
                    q_match = query_pattern.search(line)
                    if q_match:
                        issues.append(f"[SQLi Var] Variable interpolated in {filepath}:{i+1} -> {line.strip()}")

for issue in issues:
    print(issue)
print("Advanced Audit complete.")
