import os

filepath = r'c:\xampp\htdocs\renter-system\renter\payment-history.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Find the start of the fetching logic
start_str = "/* Fetch Billing Lists */"
end_str = "if ($is_mobile) {"

if start_str in content and end_str in content:
    pre = content.split(start_str)[0]
    post = end_str + content.split(end_str, 1)[1]
    
    new_logic = """/* Fetch Transaction History Instead of Bills */
$notifs = [];
$q_n = mysqli_query($conn, "SELECT id, amount, month, payment_method as payment_mode, status, transaction_id, DATE(created_at) as p_date, sys_tx_id, admin_note FROM payment_notifications WHERE user_id = $user_id ORDER BY id DESC LIMIT 50");
$sys_tx_ids = [];
while ($row = mysqli_fetch_assoc($q_n)) {
    if (!empty($row['sys_tx_id'])) {
        $sys_tx_ids[] = "'" . mysqli_real_escape_string($conn, $row['sys_tx_id']) . "'";
    }
    
    $color = ($row['status'] == 'Approved') ? 'green' : (($row['status'] == 'Rejected') ? 'red' : 'orange');
    $icon = ($row['status'] == 'Approved') ? 'bx-check-circle' : (($row['status'] == 'Rejected') ? 'bx-x-circle' : 'bx-time-five');
    
    $notifs[] = [
        'id' => $row['id'],
        'filter_type' => strtolower($row['status']),
        'title' => 'Payment Application',
        'subtitle' => 'Period: ' . ($row['month'] ?: 'Multiple/General') . ($row['transaction_id'] ? ' | UTR: ' . $row['transaction_id'] : ''),
        'due_date' => '-',
        'amount' => $row['amount'],
        'status' => $row['status'],
        'payment_mode' => $row['payment_mode'] ?: 'Online',
        'color' => $color,
        'icon' => $icon,
        'p_date' => date('d M Y', strtotime($row['p_date']))
    ];
}

$not_in_clause = "";
if (count($sys_tx_ids) > 0) {
    $not_in_clause = "AND (sys_tx_id IS NULL OR sys_tx_id = '' OR sys_tx_id NOT IN (" . implode(',', $sys_tx_ids) . "))";
} else {
    $not_in_clause = "AND (sys_tx_id IS NULL OR sys_tx_id = '')";
}

$manuals = [];
$q_m = mysqli_query($conn, "
    SELECT 
        DATE(payment_date) as p_date,
        payment_mode,
        transaction_id,
        sys_tx_id,
        SUM(paid_amount) as amount,
        GROUP_CONCAT(DISTINCT month SEPARATOR ', ') as period
    FROM payments 
    WHERE user_id = $user_id $not_in_clause
    GROUP BY DATE(payment_date), payment_mode, transaction_id, sys_tx_id
    ORDER BY p_date DESC
    LIMIT 50
");
while ($row = mysqli_fetch_assoc($q_m)) {
    $manuals[] = [
        'id' => rand(10000, 99999),
        'filter_type' => 'approved',
        'title' => 'Manual Admin Record',
        'subtitle' => 'Period: ' . ($row['period'] ?: 'Multiple') . ($row['transaction_id'] ? ' | UTR: ' . $row['transaction_id'] : ''),
        'due_date' => '-',
        'amount' => $row['amount'],
        'status' => 'Paid',
        'payment_mode' => $row['payment_mode'] ?: 'Offline',
        'color' => 'green',
        'icon' => 'bx-check-double',
        'p_date' => date('d M Y', strtotime($row['p_date']))
    ];
}

$all_bills = array_merge($notifs, $manuals);
usort($all_bills, function($a, $b) {
    return strtotime($b['p_date']) - strtotime($a['p_date']);
});

"""
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(pre + new_logic + post)
    print("Updated payment-history.php logic")
else:
    print("Targets not found")
