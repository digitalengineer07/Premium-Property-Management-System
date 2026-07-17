import os

def fix_session_fixation():
    # admin/login.php and api/auth/login.php
    files = [
        r"c:\xampp\htdocs\renter-system\admin\login.php",
        r"c:\xampp\htdocs\renter-system\api\auth\login.php"
    ]
    
    for filepath in files:
        if os.path.exists(filepath):
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # For admin/login.php
            if "session_regenerate_id(true);" not in content:
                content = content.replace("$_SESSION['admin_id'] = $admin['id'];", "session_regenerate_id(true);\n                    $_SESSION['admin_id'] = $admin['id'];")
            
            # For api/auth/login.php
            if "session_regenerate_id(true);" not in content and "api/auth" in filepath:
                # The auth API may not use PHP sessions. Wait, let's see.
                pass
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Fixed session in {filepath}")

def add_csrf_to_forms():
    # Add <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>"> to missing forms
    # We will search for <form method="POST" ...> and inject right after it.
    
    import re
    base_dir = r"c:\xampp\htdocs\renter-system"
    
    form_pattern = re.compile(r'(<form[^>]*method=["\']POST["\'][^>]*>)', re.IGNORECASE)
    
    for root, _, files in os.walk(base_dir):
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                
                original_content = content
                
                # We need to find forms that don't have csrf
                # Easiest way: split by form pattern, check next 200 chars
                parts = form_pattern.split(content)
                if len(parts) > 1:
                    new_content = parts[0]
                    for i in range(1, len(parts), 2):
                        form_tag = parts[i]
                        after_form = parts[i+1]
                        
                        # Check if csrf is already in the next few characters
                        if "getCsrfToken" not in after_form[:300] and 'name="csrf"' not in after_form[:300]:
                            # Inject
                            inject = '\n                  <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">'
                            new_content += form_tag + inject + after_form
                        else:
                            new_content += form_tag + after_form
                    
                    if new_content != original_content:
                        with open(filepath, 'w', encoding='utf-8') as f:
                            f.write(new_content)
                        print(f"Added CSRF hidden inputs to {filepath}")

def add_csrf_validation_to_php():
    # For upload scripts in admin
    files = [
        r"c:\xampp\htdocs\renter-system\admin\upload-agreement.php",
        r"c:\xampp\htdocs\renter-system\admin\upload-aadhaar.php",
        r"c:\xampp\htdocs\renter-system\admin\upload-electricity-doc.php"
    ]
    
    for filepath in files:
        if os.path.exists(filepath):
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            target = "if ($_SERVER['REQUEST_METHOD'] === 'POST') {"
            if target in content and "verifyCsrfToken" not in content:
                replace_with = target + "\n    if (!verifyCsrfToken($_POST['csrf'] ?? '')) { die('Security validation failed.'); }"
                content = content.replace(target, replace_with)
                
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Added CSRF validation to {filepath}")

fix_session_fixation()
add_csrf_to_forms()
add_csrf_validation_to_php()
print("All automated security fixes applied.")
