import os
import re

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php',
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\payment-history_mobile.php'
]

new_logic = """        // Fetch all transactions grouped by system transaction intent (sys_tx_id) to avoid showing fragmented ledger entries
        $all_bills = [];
        
        // 1. Fetch user-applied transactions from payment_notifications
        $q_n = mysqli_query($conn, "SELECT id, amount, month, payment_method as payment_mode, status, transaction_id, created_at as p_date, sys_tx_id, admin_note FROM payment_notifications WHERE user_id = $user_id ORDER BY id DESC LIMIT 50");
        $sys_tx_ids = [];
        if ($q_n) {
            while ($row = mysqli_fetch_assoc($q_n)) {
                if (!empty($row['sys_tx_id'])) {
                    $sys_tx_ids[] = "'" . mysqli_real_escape_string($conn, $row['sys_tx_id']) . "'";
                }
                
                $color = ($row['status'] == 'Approved') ? 'green' : (($row['status'] == 'Rejected') ? 'red' : 'orange');
                $icon = ($row['status'] == 'Approved') ? 'bx-check-circle' : (($row['status'] == 'Rejected') ? 'bx-x-circle' : 'bx-time-five');
                
                $title = 'Consolidated Payment';
                if ($row['bill_type'] == 'rent') $title = 'Rent Payment';
                if ($row['bill_type'] == 'electricity') $title = 'Electricity Bill';
                
                $all_bills[] = [
                    'filter_type' => strtolower($row['status']),
                    'color' => $color,
                    'icon' => $icon,
                    'title' => $title,
                    'subtitle' => 'UTR: ' . ($row['transaction_id'] ?: 'N/A'),
                    'month' => $row['month'] ?: 'Multiple',
                    'bill_date' => date('d M Y', strtotime($row['p_date'])),
                    'due_date' => '-',
                    'amount' => (float)$row['amount'],
                    'status' => $row['status'],
                    'pdate' => date('d M Y', strtotime($row['p_date'])),
                    'p_ts' => strtotime($row['p_date']),
                    'payment_mode' => $row['payment_mode'] ?: 'Online'
                ];
            }
        }

        // 2. Fetch admin manual transactions from payments (where sys_tx_id not in notifications)
        $not_in_clause = "";
        if (count($sys_tx_ids) > 0) {
            $not_in_clause = "AND (sys_tx_id IS NULL OR sys_tx_id = '' OR sys_tx_id NOT IN (" . implode(',', $sys_tx_ids) . "))";
        } else {
            $not_in_clause = "AND (sys_tx_id IS NULL OR sys_tx_id = '')";
        }

        $q_m = mysqli_query($conn, "
            SELECT 
                DATE(payment_date) as p_date,
                MAX(payment_date) as full_date,
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
        
        if ($q_m) {
            while ($row = mysqli_fetch_assoc($q_m)) {
                $all_bills[] = [
                    'filter_type' => 'approved',
                    'color' => 'green',
                    'icon' => 'bx-check-double',
                    'title' => 'Admin Manual Payment',
                    'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline'),
                    'month' => $row['period'] ?: 'Multiple',
                    'bill_date' => date('d M Y', strtotime($row['p_date'])),
                    'due_date' => '-',
                    'amount' => (float)$row['amount'],
                    'status' => 'Paid',
                    'pdate' => date('d M Y', strtotime($row['p_date'])),
                    'p_ts' => strtotime($row['full_date'] ?: $row['p_date']),
                    'payment_mode' => $row['payment_mode'] ?: 'Offline'
                ];
            }
        }

        usort($all_bills, function($a, $b) {
            return $b['p_ts'] - $a['p_ts'];
        });
"""

for filepath in files_to_update:
    if not os.path.exists(filepath):
        print(f"File not found: {filepath}")
        continue
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # The exact target to replace is everything between:
    #         $all_bills = [];
    #         $payments_q = mysqli_query($conn, "
    # AND
    #         });
    
    start_marker = "$all_bills = [];"
    end_marker = "});"
    
    start_idx = content.find(start_marker)
    if start_idx == -1:
        print(f"Start marker not found in {filepath}")
        continue
        
    end_idx = content.find(end_marker, start_idx)
    if end_idx == -1:
        print(f"End marker not found in {filepath}")
        continue
        
    end_idx += len(end_marker)
    
    new_content = content[:start_idx] + new_logic.strip() + content[end_idx:]
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print(f"Updated {filepath}")
