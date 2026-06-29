import os
import re

pages_info = {
    'my-bills.php': {'title': 'My Bills', 'subtitle': 'View your upcoming and past bills.'},
    'electricity-record.php': {'title': 'Electricity Record', 'subtitle': 'Track your daily usage and readings.'},
    'queries.php': {'title': 'Raise Query', 'subtitle': 'Submit a request or report an issue.'},
    'notices.php': {'title': 'Notices & Announcements', 'subtitle': 'Stay updated with the latest alerts.'},
    'documents.php': {'title': 'My Documents', 'subtitle': 'Access your important agreements and files.'},
    'payment-history.php': {'title': 'Payment History', 'subtitle': 'Review your previous transactions.'},
    'profile.php': {'title': 'Profile Settings', 'subtitle': 'Manage your personal details and preferences.'},
    'change-password.php': {'title': 'Change Password', 'subtitle': 'Update your account security credentials.'},
    'about-dev.php': {'title': 'About Developer', 'subtitle': 'Learn more about the creators of this system.'}
}

for filename, info in pages_info.items():
    if not os.path.exists(filename):
        continue
    
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the header-greeting block
    # It looks like:
    # <div class="header-greeting">
    #     <h1>...</h1>
    #     <p>...</p>
    # </div>
    
    pattern = re.compile(r'(<div class="header-greeting"[^>]*>\s*)<h1[^>]*>.*?</h1>\s*<p[^>]*>.*?</p>\s*(</div>)', re.DOTALL)
    
    new_html = r'\1<h1>' + info['title'] + r'</h1>\n                <p>' + info['subtitle'] + r'</p>\n            \2'
    
    new_content = pattern.sub(new_html, content)
    
    # Also replace any ?? that might still be there in the file if it wasn't matched properly? No, the regex will replace the whole h1 and p tags.
    
    if new_content != content:
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filename}")
    else:
        print(f"Could not update or no changes for {filename}")
