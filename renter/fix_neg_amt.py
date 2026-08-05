import os

filepath = r'c:\xampp\htdocs\renter-system\renter\payment-history.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = "$amt = (float)$_POST['amount'];"
replacement = """$amt = (float)$_POST['amount'];
        if ($amt <= 0) {
            $payment_error = "Payment amount must be greater than zero.";
        } else"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Injected negative amount validation in payment-history.php")
else:
    print("Target not found")
