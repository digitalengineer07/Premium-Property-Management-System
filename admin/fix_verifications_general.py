import os

filepath = r'c:\xampp\htdocs\renter-system\admin\payment-verifications.php'

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the logic
    old_logic = "if ($p_btype == 'total' || $p_btype == 'monthly') {"
    new_logic = "if ($p_btype == 'total' || $p_btype == 'monthly' || $p_btype == 'general') {"

    content = content.replace(old_logic, new_logic)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
