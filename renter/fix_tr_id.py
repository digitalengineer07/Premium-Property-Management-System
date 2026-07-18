import os

filepath = r'c:\xampp\htdocs\renter-system\renter\payment-history.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = "$tr_id = trim($_POST['transaction_id'] ?? '');"
replacement = """$tr_id = trim($_POST['transaction_id'] ?? '');
        if (empty($tr_id)) {
            $tr_id = 'SYS-' . strtoupper(bin2hex(random_bytes(6)));
        }"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Injected random system transaction ID logic")
else:
    print("Target not found")
