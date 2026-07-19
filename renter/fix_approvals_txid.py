import os

filepath = r'c:\xampp\htdocs\renter-system\renter\payment-approvals.php'

target1 = """        $tr_id = trim($_POST['transaction_id'] ?? '');
        $p_month = !empty($_POST['bill_month']) ? date('F Y', strtotime($_POST['bill_month'].'-01')) : 'Advance/General';
        $payment_method = $_POST['payment_method'] ?? 'UPI';"""

replacement1 = """        $tr_id = trim($_POST['transaction_id'] ?? '');
        $p_month = !empty($_POST['bill_month']) ? date('F Y', strtotime($_POST['bill_month'].'-01')) : 'Advance/General';
        $payment_method = $_POST['payment_method'] ?? 'UPI';
        $sys_tx_id = 'TXN-' . date('md') . '-' . strtoupper(bin2hex(random_bytes(4)));"""

target2 = """                $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isidsss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method);"""

replacement2 = """                $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method, sys_tx_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isidssss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method, $sys_tx_id);"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if target1 in content and target2 in content:
        content = content.replace(target1, replacement1)
        content = content.replace(target2, replacement2)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath} with sys_tx_id logic")
    else:
        print(f"Target not found in {filepath}")
else:
    print(f"File not found: {filepath}")
