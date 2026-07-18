import os

path = r'c:\xampp\htdocs\renter-system\renter\payment-approvals.php'

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    old_logic = """            if ($is_duplicate) {
                $payment_error = "This UTR number has already been submitted. Please check your transaction ID.";
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");"""

    new_logic = """            // Advanced Validation: Check if a request already exists to prevent duplicate submissions
            $is_duplicate_request = false;
            $duplicate_msg = "";

            if (!$is_duplicate) {
                if ($b_id > 0) {
                    // Specific bill check
                    $chk_stmt = mysqli_prepare($conn, "SELECT status FROM payment_notifications WHERE user_id = ? AND bill_id = ? AND status IN ('Pending', 'Approved')");
                    mysqli_stmt_bind_param($chk_stmt, "ii", $user_id, $b_id);
                    mysqli_stmt_execute($chk_stmt);
                    $res = mysqli_stmt_get_result($chk_stmt);
                    if ($row = mysqli_fetch_assoc($res)) {
                        $is_duplicate_request = true;
                        $duplicate_msg = "You already have a " . $row['status'] . " payment request for this bill.";
                    }
                } else if ($b_type === 'total') {
                    // Clear all dues check
                    $chk_stmt = mysqli_prepare($conn, "SELECT status FROM payment_notifications WHERE user_id = ? AND bill_type = 'total' AND status = 'Pending'");
                    mysqli_stmt_bind_param($chk_stmt, "i", $user_id);
                    mysqli_stmt_execute($chk_stmt);
                    $res = mysqli_stmt_get_result($chk_stmt);
                    if (mysqli_num_rows($res) > 0) {
                        $is_duplicate_request = true;
                        $duplicate_msg = "You already have a Pending request to clear all dues.";
                    }
                } else {
                    // General / Advance payment check
                    $chk_stmt = mysqli_prepare($conn, "SELECT status FROM payment_notifications WHERE user_id = ? AND bill_type = 'general' AND amount = ? AND status = 'Pending' AND DATE(created_at) = CURDATE()");
                    mysqli_stmt_bind_param($chk_stmt, "id", $user_id, $amt);
                    mysqli_stmt_execute($chk_stmt);
                    $res = mysqli_stmt_get_result($chk_stmt);
                    if (mysqli_num_rows($res) > 0) {
                        $is_duplicate_request = true;
                        $duplicate_msg = "You already have a Pending advance payment for this exact amount today.";
                    }
                }
            }

            if ($is_duplicate) {
                $payment_error = "This UTR number has already been submitted. Please check your transaction ID.";
            } else if ($is_duplicate_request) {
                $payment_error = $duplicate_msg;
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id, month, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");"""

    content = content.replace(old_logic, new_logic)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Loophole validation logic added successfully.")
