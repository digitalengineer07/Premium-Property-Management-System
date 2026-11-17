<?php
require_once __DIR__ . '/../db.php';
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
if (!isset($_SESSION['admin'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../includes/accounting_reports.php';
try {
    [$start,$end] = accounting_report_range($_GET['range'] ?? 'this_month');
    $report = accounting_report($conn);
    $cash = accounting_cash_in_range($conn, $start, $end);
    $periods = array_values(array_filter(accounting_report_periods($conn), fn($m)=>($start === null || $m >= substr($start,0,7)) && $m <= substr($end,0,7)));
    $selected = array_fill_keys(['amount','rent_amount','maintenance','extra_charges','dues'], 0);
    $series = [];
    foreach ($periods as $period) {
        $r = accounting_report($conn, $period);
        foreach ($selected as $key=>$_) $selected[$key] += $r['applied'][$key];
        $series[] = ['month'=>$period,'rent'=>$r['applied']['rent_amount']/100,'electricity'=>$r['applied']['amount']/100,
            'other'=>($r['applied']['maintenance']+$r['applied']['extra_charges']+$r['applied']['dues'])/100];
    }
    $ages = [0,0,0,0]; $tenants = [[],[],[],[]]; $overdue = [];
    $wallets = [];
    $rows = accounting_query($conn, "SELECT id,advance_payment FROM users WHERE status='active'");
    while ($u = mysqli_fetch_assoc($rows)) $wallets[(int)$u['id']] = accounting_paise($u['advance_payment']);
    usort($report['invoices'], fn($a,$b)=>[$a['month'],$a['id']] <=> [$b['month'],$b['id']]);
    foreach ($report['invoices'] as $invoice) {
        $uid = $invoice['user_id'];
        if (!isset($wallets[$uid])) continue;
        $offset = min(max(0,$wallets[$uid]),$invoice['due_paise']);
        $wallets[$uid] -= $offset;
        $due = $invoice['due_paise']-$offset;
        if ($due <= 0) continue;
        $days = $invoice['due_date'] ? (int)floor((strtotime($end)-strtotime($invoice['due_date']))/86400) : 0;
        $bucket = $days <= 7 ? 0 : ($days <= 30 ? 1 : ($days <= 60 ? 2 : 3));
        $ages[$bucket] += $due; $tenants[$bucket][$uid] = true;
        if ($days > 0) $overdue[$uid] = true;
    }
    switch ($_GET['endpoint'] ?? 'kpi') {
        case 'kpi':
            $result = ['total_rent'=>$cash['cash']/100,'rent_growth'=>'Verified ledger receipts','electricity_profit'=>null,
                'elec_growth'=>'Supplier costs not recorded','outstanding'=>$report['net_paise']/100,'out_growth'=>'Current balance',
                'active_residents'=>count($report['active_users']),'res_growth'=>'','overdue_tenants'=>count($overdue),'overdue_growth'=>'Current balance',
                'deposit_receipts'=>$cash['deposits']/100,'advance_receipts'=>$cash['advances']/100]; break;
        case 'revenue_chart': $result = $series; break;
        case 'distribution_donut': $result = ['Rent'=>$selected['rent_amount']/100,'Electricity'=>$selected['amount']/100,'Maintenance'=>$selected['maintenance']/100,'Extra / opening dues'=>($selected['extra_charges']+$selected['dues'])/100]; break;
        case 'receivables_aging':
            $result = []; $labels = ['Current / 0–7 Days','8–30 Days','31–60 Days','60+ Days']; $colors = ['#10B981','#F59E0B','#F97316','#EF4444'];
            foreach ($ages as $i=>$value) $result[] = ['bracket'=>$labels[$i],'amount'=>$value/100,'tenants'=>count($tenants[$i]),'color'=>$colors[$i],'progress'=>array_sum($ages) ? round(100*$value/array_sum($ages)) : 0];
            $result[] = ['total'=>array_sum($ages)/100]; break;
        case 'resident_performance':
            $result = [];
            $users = accounting_query($conn, "SELECT id,name,room_no FROM users WHERE status='active'");
            while ($u = mysqli_fetch_assoc($users)) {
                $balance = accounting_balances($conn, (int)$u['id']); $paid = max(0,$balance['paid_paise']); $due = $balance['net_paise'];
                $pct = $paid+$due ? round(100*$paid/($paid+$due)) : 100;
                $result[] = ['name'=>htmlspecialchars($u['name'],ENT_QUOTES,'UTF-8'),'room'=>htmlspecialchars($u['room_no']), 'photo'=>'../assets/img/default-avatar.png',
                    'paid'=>$paid/100,'due'=>$due/100,'percentage'=>$pct,'status'=>$due ? 'Outstanding' : 'Settled','statusColor'=>$due ? '#CA8A04' : '#059669','statusBg'=>$due ? '#FEF9C3' : '#DCFCE7'];
            }
            usort($result,fn($a,$b)=>$b['due'] <=> $a['due']); $result = array_slice($result,0,4); break;
        case 'electricity_insights':
            $values = [];
            $rows = accounting_query($conn,'SELECT month,units_consumed FROM electricity');
            while ($r = mysqli_fetch_assoc($rows)) if (in_array(accounting_month($r['month']),$periods,true)) $values[] = (int)$r['units_consumed'];
            $result = ['avg_units'=>$values ? round(array_sum($values)/count($values)) : 0,'highest'=>$values ? max($values) : 0,'lowest'=>$values ? min($values) : 0,'margin'=>'Not recorded','expense'=>null,'profit'=>null]; break;
        case 'electricity_bar':
            $result = [];
            $rows = accounting_query($conn, 'SELECT u.room_no,e.month,e.units_consumed FROM electricity e JOIN users u ON u.id=e.user_id');
            $units = [];
            while ($r = mysqli_fetch_assoc($rows)) if (in_array(accounting_month($r['month']),$periods,true)) $units[$r['room_no']] = ($units[$r['room_no']] ?? 0)+(int)$r['units_consumed'];
            arsort($units);
            foreach (array_slice($units,0,8,true) as $room=>$value) $result[] = ['label'=>'Room '.$room,'units'=>$value]; break;
        case 'top_defaulters':
            $result = [];
            foreach ($overdue as $uid=>$_) {
                $u = mysqli_fetch_assoc(accounting_query($conn,"SELECT name,room_no FROM users WHERE id=$uid"));
                $days = 0;
                foreach ($report['invoices'] as $i) if ($i['user_id']===$uid && $i['due_paise']>0 && $i['due_date']) $days=max($days,(int)floor((strtotime($end)-strtotime($i['due_date']))/86400));
                $result[] = ['name'=>htmlspecialchars($u['name'],ENT_QUOTES,'UTF-8'),'room'=>htmlspecialchars($u['room_no']),'photo'=>'../assets/img/default-avatar.png',
                    'due'=>accounting_balances($conn,$uid)['net_paise']/100,'days_overdue'=>$days];
            }
            usort($result,fn($a,$b)=>$b['due'] <=> $a['due']); $result=array_slice($result,0,5); break;
        case 'anomalies':
        case 'high_usage_alerts': $result = []; break; // No measured comparison means no invented alert.
        case 'expense_donut': $result = ['Maintenance'=>['value'=>0,'color'=>'#624BFF'],'Salaries'=>['value'=>0,'color'=>'#10B981'],'Utilities'=>['value'=>0,'color'=>'#F59E0B'],'Other Expenses'=>['value'=>0,'color'=>'#3B82F6'],'Total'=>null]; break;
        case 'recent_activity':
            $result = []; $rows = accounting_query($conn,'SELECT id,user_id,paid_amount,payment_date FROM payments ORDER BY id DESC LIMIT 8');
            while ($p = mysqli_fetch_assoc($rows)) $result[] = ['type'=>'payment','title'=>'Ledger posting #'.(int)$p['id'],'desc'=>'Account #'.(int)$p['user_id'].' · ₹'.number_format((float)$p['paid_amount'],2),'time'=>$p['payment_date'],'icon'=>'bx-rupee','color'=>'#10B981']; break;
        case 'ai_insights': $result = [count($overdue).' residents have overdue balances after available wallet credit.',
            'Cash received includes deposits and advance receipts; it is not the same as earned rent.',
            'Supplier costs have not been recorded, so profit and expense figures are unavailable.',
            'Historical discrepancies are listed in the accounting audit.']; break;
        default: http_response_code(400); $result = ['error'=>'Unknown report endpoint'];
    }
    echo json_encode($result);
} catch (Throwable $failure) {
    error_log($failure->getMessage()); http_response_code(500); echo json_encode(['error'=>'Unable to calculate report.']);
}
