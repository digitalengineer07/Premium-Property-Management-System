import re

dashboard_path = r'C:\xampp\htdocs\renter-system\renter\dashboard.php'
approvals_path = r'C:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

with open(dashboard_path, 'r', encoding='utf-8') as f:
    dash_content = f.read()

with open(approvals_path, 'r', encoding='utf-8') as f:
    app_content = f.read()

# Extract sidebar HTML from dashboard.php
sidebar_html_match = re.search(r'(<aside class="sidebar">.*?</aside>)', dash_content, flags=re.IGNORECASE | re.DOTALL)
if sidebar_html_match:
    sidebar_html = sidebar_html_match.group(1)
    # Fix active class for approvals
    sidebar_html = re.sub(r'class="nav-item active"', 'class="nav-item"', sidebar_html)
    sidebar_html = re.sub(r'(<a href="payment-approvals\.php" class="nav-item)(">)', r'\1 active\2', sidebar_html)

    # Replace sidebar HTML in approvals
    app_content = re.sub(r'<aside class="sidebar">.*?</aside>', sidebar_html, app_content, flags=re.IGNORECASE | re.DOTALL)

# Extract sidebar CSS from dashboard.php
css_match = re.search(r'(\.sidebar\s*\{.*?)(\.go-mobile-widget\s*\{|\.user-profile-pill)', dash_content, flags=re.IGNORECASE | re.DOTALL)
if css_match:
    sidebar_css = css_match.group(1)
    
    # Replace sidebar CSS in approvals
    # Find existing sidebar css
    app_css_match = re.search(r'(\.sidebar\s*\{.*?\n        \.main-content)', app_content, flags=re.IGNORECASE | re.DOTALL)
    if app_css_match:
        app_content = app_content.replace(app_css_match.group(1), sidebar_css + '\n        .main-content')
    else:
        print("Could not find CSS to replace in approvals")

with open(approvals_path, 'w', encoding='utf-8') as f:
    f.write(app_content)

print("Updated approvals sidebar to match dashboard perfectly.")
