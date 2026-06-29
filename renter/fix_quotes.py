import os

files_to_fix = [
    'my-bills.php',
    'electricity-record.php',
    'queries.php',
    'notices.php',
    'documents.php',
    'payment-history.php'
]

for filename in files_to_fix:
    if not os.path.exists(filename):
        continue
    
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace class=\'bx with class='bx
    new_content = content.replace("class=\\'bx", "class='bx")
    # Also fix closing quote just in case
    new_content = new_content.replace("\\' style=", "' style=")

    if new_content != content:
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Fixed quotes in {filename}")
    else:
        print(f"No changes needed for {filename}")
