import os
import re

renter_dir = r"c:\xampp\htdocs\renter-system\renter"

# Regex patterns
patterns = [
    (re.compile(r"number_format\((.*?), 2\)"), r"number_format(\1)"),
    (re.compile(r"number_format\((.*?),2\)"), r"number_format(\1)"),
    (re.compile(r"'8,000\.00'"), r"'8,000'"),
    (re.compile(r"8000\.00"), r"8000"),
    (re.compile(r"8\.00"), r"8"),
]

for root, _, files in os.walk(renter_dir):
    for file in files:
        if file.endswith((".php", ".html")):
            filepath = os.path.join(root, file)
            with open(filepath, "r", encoding="utf-8") as f:
                content = f.read()
            
            new_content = content
            for pat, repl in patterns:
                new_content = pat.sub(repl, new_content)
                
            if new_content != content:
                with open(filepath, "w", encoding="utf-8") as f:
                    f.write(new_content)
                print(f"Updated {filepath}")
