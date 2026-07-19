import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """        // 1. Fetch user-applied transactions from payment_notifications
        $q_n = mysqli_query($conn, "SELECT id, bill_type, amount, month, payment_method as payment_mode, status, transaction_id, created_at as p_date, sys_tx_id, admin_note FROM payment_notifications WHERE user_id = $user_id ORDER BY id DESC LIMIT 50");"""

replacement = """        if (isset($_GET['month']) && !empty($_GET['month'])) {
            $target_month = mysqli_real_escape_string($conn, urldecode($_GET['month']));
            $q = mysqli_query($conn, "SELECT id, bill_type, paid_amount as amount, month, payment_mode, payment_date as p_date, transaction_id, sys_tx_id FROM payments WHERE user_id = $user_id AND month = '$target_month' ORDER BY payment_date DESC, id DESC");
            if ($q) {
                while ($row = mysqli_fetch_assoc($q)) {
                    $title = 'Ledger Split';
                    if ($row['bill_type'] == 'rent') $title = 'Rent Split';
                    if ($row['bill_type'] == 'electricity') $title = 'Electricity Split';
                    if ($row['bill_type'] == 'elec_rent') $title = 'Bill Component (Rent)';
                    if ($row['bill_type'] == 'advance') $title = 'Advance Application';
                    
                    $all_bills[] = [
                        'filter_type' => 'paid',
                        'color' => 'green',
                        'icon' => 'bx-layer',
                        'title' => $title,
                        'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),
                        'period' => $row['month'],
                        'bill_date' => date('d M Y', strtotime($row['p_date'])),
                        'due_date' => '-',
                        'amount' => (float)$row['amount'],
                        'status' => 'Allocated',
                        'paid_on' => date('d M Y', strtotime($row['p_date'])),
                        'p_ts' => strtotime($row['p_date']),
                        'payment_mode' => $row['payment_mode'] ?: 'System'
                    ];
                }
            }
        } else {
        // 1. Fetch user-applied transactions from payment_notifications
        $q_n = mysqli_query($conn, "SELECT id, bill_type, amount, month, payment_method as payment_mode, status, transaction_id, created_at as p_date, sys_tx_id, admin_note FROM payment_notifications WHERE user_id = $user_id ORDER BY id DESC LIMIT 50");"""

target2 = """        usort($all_bills, function($a, $b) {
            return $b['p_ts'] - $a['p_ts'];
        });"""

replacement2 = """        }
        usort($all_bills, function($a, $b) {
            return $b['p_ts'] - $a['p_ts'];
        });"""

if target in content and target2 in content:
    content = content.replace(target, replacement)
    content = content.replace(target2, replacement2)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched payment-history_desktop.php with specific month split logic")
else:
    print("Targets not found!")
