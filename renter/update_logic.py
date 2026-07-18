import os

files_to_update = [
    'dashboard.php',
    'my-bills.php',
    'my-payments.php',
    'payment-history.php'
]

old_logic = """        if (empty($tr_id)) {
            $payment_error = "Please enter the Transaction ID / UTR.";
        } else {
            // Check for duplicate UTR
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
            mysqli_stmt_bind_param($check_stmt, "s", $tr_id);
            mysqli_stmt_execute($check_stmt);
            $check_res = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_res) > 0) {
                $payment_error = "This UTR number has already been submitted. Please check your transaction ID.";
            } else {"""

new_logic = """        $payment_method = $_POST['payment_method'] ?? 'UPI';

        if ($payment_method === 'UPI' && empty($tr_id)) {
            $payment_error = "Please enter the Transaction ID / UTR.";
        } else {
            $is_duplicate = false;
            if ($payment_method === 'UPI' && !empty($tr_id)) {
                // Check for duplicate UTR
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $tr_id);
                mysqli_stmt_execute($check_stmt);
                $check_res = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_res) > 0) {
                    $is_duplicate = true;
                }
            }
            
            if ($is_duplicate) {
                $payment_error = "This UTR number has already been submitted. Please check your transaction ID.";
            } else {"""

insert_old_1 = """            $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isids", $user_id, $b_type, $b_id, $amt, $tr_id);"""
insert_new_1 = """            $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isidss", $user_id, $b_type, $b_id, $amt, $tr_id, $payment_method);"""

insert_old_2 = """            $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isidss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month);"""
insert_new_2 = """            $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isidsss", $user_id, $b_type, $b_id, $amt, $tr_id, $p_month, $payment_method);"""

for file_name in files_to_update:
    path = os.path.join(r'C:\xampp\htdocs\renter-system\renter', file_name)
    if os.path.exists(path):
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        content = content.replace(old_logic, new_logic)
        content = content.replace(insert_old_1, insert_new_1)
        content = content.replace(insert_old_2, insert_new_2)
        
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {file_name}")
