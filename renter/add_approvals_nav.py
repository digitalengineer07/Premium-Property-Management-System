import os

folder = r'C:\xampp\htdocs\renter-system\renter'

for root, dirs, files in os.walk(folder):
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()

            # For Desktop nav
            if '<span>Payment History</span></a>' in content and '<span>Approvals</span></a>' not in content:
                content = content.replace(
                    "<span>Payment History</span></a>",
                    "<span>Payment History</span></a>\n            <a href=\"payment-approvals.php\" class=\"nav-item\"><i class='bx bx-check-shield'></i><span>Approvals</span></a>"
                )
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Updated Desktop nav in {path}")

            # For Mobile nav
            if '<span>Approvals</span>' not in content and 'class="mobile-bottom-nav"' in content:
                print(f"File {file} has mobile nav but no approvals link")
