import os

files_to_fix = [
    r'c:\xampp\htdocs\renter-system\renter\dashboard.php',
    r'c:\xampp\htdocs\renter-system\renter\my-payments.php',
    r'c:\xampp\htdocs\renter-system\renter\my-bills.php',
    r'c:\xampp\htdocs\renter-system\renter\payment-history.php'
]

for filepath in files_to_fix:
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        modified = False

        # Add session check for success message
        old_decl = '    $payment_success = "";\n    $payment_error = "";'
        # It might be global scope without indentation
        old_decl_global = '$payment_success = "";\n$payment_error = "";'
        
        new_decl = '''$payment_success = "";
$payment_error = "";
if (isset($_SESSION['payment_success'])) {
    $payment_success = $_SESSION['payment_success'];
    unset($_SESSION['payment_success']);
}'''
        
        if old_decl_global in content and "unset($_SESSION['payment_success']);" not in content:
            content = content.replace(old_decl_global, new_decl)
            modified = True
            
        if old_decl in content and "unset($_SESSION['payment_success']);" not in content:
            # Re-indent for inside a block if necessary, but these are usually at root scope
            content = content.replace(old_decl, new_decl)
            modified = True

        # Change the success path to redirect
        old_success = '''            if (mysqli_stmt_execute($stmt)) {
                $payment_success = "Payment notification sent to Admin for verification!";
            } else {'''
            
        new_success = '''            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['payment_success'] = "Payment notification sent to Admin for verification!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {'''

        if old_success in content:
            content = content.replace(old_success, new_success)
            modified = True

        if modified:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Added PRG pattern to {os.path.basename(filepath)}")
