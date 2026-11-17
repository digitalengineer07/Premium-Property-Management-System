<?php
require_once __DIR__ . '/accounting_store.php';

function accounting_report($conn, ?string $period = null): array {
    $period = $period === null ? null : accounting_month($period);
    $fields = ['amount','rent_amount','maintenance','extra_charges','dues'];
    $billed = $paid = array_fill_keys($fields, 0);
    $invoices = [];
    foreach (['rent','electricity'] as $table) {
        $rows = accounting_query($conn, "SELECT * FROM $table");
        while ($bill = mysqli_fetch_assoc($rows)) {
            $month = accounting_month($bill['month']);
            $amountPaid = accounting_bill_paid($conn, (int)$bill['user_id'], $table, (int)$bill['id']);
            if ($table === 'rent') $bill = array_merge(array_fill_keys($fields, '0'), $bill);
            $remaining = accounting_components($bill, $amountPaid);
            foreach ($fields as $key) {
                $chargeMonth = $key === 'rent_amount' && !empty($bill['rent_month']) ? accounting_month($bill['rent_month']) : $month;
                if ($period !== null && $chargeMonth !== $period) continue;
                $charge = accounting_paise($bill[$key] ?? 0);
                $billed[$key] += $charge;
                $paid[$key] += $charge - $remaining[$key];
            }
            $invoices[] = ['user_id'=>(int)$bill['user_id'], 'id'=>(int)$bill['id'], 'type'=>$table, 'month'=>$month,
                'due_paise'=>array_sum($remaining), 'due_date'=>$bill['due_date'] ?? null];
        }
    }
    $cash = $deposits = $advanceReceipts = 0;
    $rows = accounting_query($conn, 'SELECT * FROM payments ORDER BY payment_date,id');
    while ($p = mysqli_fetch_assoc($rows)) {
        if ($period !== null && substr($p['payment_date'], 0, 7) !== $period) continue;
        if (in_array(strtolower($p['payment_mode'] ?? ''), ['wallet','advance wallet auto-deduction','advance credit','extra from bill'], true)) continue;
        $amount = accounting_paise($p['paid_amount'] ?? 0);
        $cash += $amount;
        if ($p['bill_type'] === 'security_deposit') $deposits += $amount;
        if ($p['bill_type'] === 'advance') $advanceReceipts += $amount;
    }
    $gross = $net = $wallet = $dueResidents = 0;
    $active = [];
    $users = accounting_query($conn, "SELECT id,advance_payment FROM users WHERE status='active'");
    while ($u = mysqli_fetch_assoc($users)) {
        $uid = (int)$u['id'];
        $active[$uid] = true;
        $balance = accounting_balances($conn, $uid, $period);
        $gross += $balance['gross_paise'];
        $net += $balance['net_paise'];
        $wallet += $balance['credit_paise'];
        if ($balance['net_paise'] > 0) $dueResidents++;
    }
    return ['billed'=>$billed,'applied'=>$paid,'cash_paise'=>$cash,'deposit_receipts_paise'=>$deposits,
        'advance_receipts_paise'=>$advanceReceipts,'gross_paise'=>$gross,'net_paise'=>$net,'wallet_paise'=>$wallet,
        'due_residents'=>$dueResidents,'active_users'=>$active,'invoices'=>$invoices];
}

function accounting_report_periods($conn): array {
    $periods = [];
    foreach (['rent','electricity'] as $table) {
        $rows = accounting_query($conn, "SELECT * FROM $table");
        while ($r = mysqli_fetch_assoc($rows)) {
            $periods[accounting_month($r['month'])] = true;
            if (!empty($r['rent_month'])) $periods[accounting_month($r['rent_month'])] = true;
        }
    }
    $keys = array_keys($periods);
    sort($keys);
    return $keys;
}

function accounting_report_range(string $range): array {
    $today = new DateTimeImmutable('today');
    $start = null;
    switch ($range) {
        case 'today': $start = $today; break;
        case 'this_week': $start = $today->modify('monday this week'); break;
        case 'this_month': $start = $today->modify('first day of this month'); break;
        case 'last_3_months': $start = $today->modify('first day of this month')->modify('-2 months'); break;
        case 'ytd': $start = $today->setDate((int)$today->format('Y'),1,1); break;
        case 'all_time': break;
        default: throw new InvalidArgumentException('Unknown report range.');
    }
    return [$start ? $start->format('Y-m-d') : null, $today->format('Y-m-d')];
}

function accounting_cash_in_range($conn, ?string $start, string $end): array {
    $cash = $deposits = $advances = 0;
    $rows = accounting_query($conn, 'SELECT bill_type,paid_amount,payment_mode,payment_date FROM payments');
    while ($p = mysqli_fetch_assoc($rows)) {
        if (($start !== null && $p['payment_date'] < $start) || $p['payment_date'] > $end) continue;
        if (in_array(strtolower($p['payment_mode'] ?? ''), ['wallet','advance wallet auto-deduction','advance credit','extra from bill'], true)) continue;
        $amount = accounting_paise($p['paid_amount'] ?? 0);
        $cash += $amount;
        if ($p['bill_type'] === 'security_deposit') $deposits += $amount;
        if ($p['bill_type'] === 'advance') $advances += $amount;
    }
    return ['cash'=>$cash,'deposits'=>$deposits,'advances'=>$advances];
}

function accounting_open_items($conn): array {
    $report = accounting_report($conn);
    $users=[];
    $rows=accounting_query($conn,"SELECT id,name,room_no,advance_payment FROM users WHERE status='active'");
    while($u=mysqli_fetch_assoc($rows)) $users[(int)$u['id']]=$u+['wallet'=>accounting_paise($u['advance_payment'])];
    usort($report['invoices'],fn($a,$b)=>[$a['month'],$a['id'],$a['type']] <=> [$b['month'],$b['id'],$b['type']]);
    $open=[];
    foreach($report['invoices'] as $invoice) {
        $uid=$invoice['user_id'];
        if (!isset($users[$uid])) continue;
        $used=min(max(0,$users[$uid]['wallet']),$invoice['due_paise']);
        $users[$uid]['wallet']-=$used;
        $invoice['original_due_paise']=$invoice['due_paise'];
        $invoice['due_paise']-=$used;
        if ($invoice['due_paise']>0) $open[]=$invoice+['name'=>$users[$uid]['name'],'room_no'=>$users[$uid]['room_no']];
    }
    return $open;
}
