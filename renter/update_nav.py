import os
import re

folder = r'C:\xampp\htdocs\renter-system\renter\views\desktop'

for root, dirs, files in os.walk(folder):
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()

            if '>Payment History</span>' in content and '>Approvals</span>' not in content:
                content = re.sub(
                    r'(<a href="payment-history\.php"[^>]*>.*?<span>Payment History</span></a>)',
                    r'\1\n            <a href="payment-approvals.php" class="nav-item"><i class=\'bx bx-check-shield\'></i><span>Approvals</span></a>',
                    content
                )
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Updated Desktop nav in {path}")
