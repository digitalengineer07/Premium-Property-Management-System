import os
import re

print("Starting POST CSRF Audit...")
base_dir = r"c:\xampp\htdocs\renter-system"

form_pattern = re.compile(r'<form\b[^>]*method=["\']POST["\'][^>]*>', re.IGNORECASE)
csrf_pattern = re.compile(r'name=["\']csrf["\']|getCsrfToken', re.IGNORECASE)

issues = []

for root, _, files in os.walk(base_dir):
    for file in files:
        if file.endswith('.php'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                lines = f.readlines()
                
                in_form = False
                has_csrf = False
                form_start_line = 0
                
                for i, line in enumerate(lines):
                    if form_pattern.search(line):
                        in_form = True
                        form_start_line = i + 1
                        has_csrf = False
                        
                    if in_form:
                        if csrf_pattern.search(line):
                            has_csrf = True
                        if '</form>' in line.lower():
                            if not has_csrf:
                                issues.append(f"[CSRF] POST Form without CSRF token in {filepath} starting at line {form_start_line}")
                            in_form = False

for issue in issues:
    print(issue)
print("Audit complete.")
