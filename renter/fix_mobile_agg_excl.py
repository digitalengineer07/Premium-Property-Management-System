import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-payments_mobile.php'

target = """            if(!isset($mobile_aggregates[$m])) {
                $mobile_aggregates[$m] = [
                    'item_type' => 'aggregate',
                    'month' => $m,
                    'amount' => 0,
                    'remaining_amount' => 0,
                    'status' => 'Paid',
                    'has_unpaid' => false,
                    'has_partial' => false,
                    'has_paid' => false
                ];
            }
            $mobile_aggregates[$m]['amount'] += (float)$t['amount'];
            $mobile_aggregates[$m]['remaining_amount'] += isset($t['remaining_amount']) ? (float)$t['remaining_amount'] : (float)$t['amount'];"""

replacement = """            if(!isset($mobile_aggregates[$m])) {
                $mobile_aggregates[$m] = [
                    'item_type' => 'aggregate',
                    'month' => $m,
                    'amount' => 0,
                    'remaining_amount' => 0,
                    'status' => 'Paid',
                    'has_unpaid' => false,
                    'has_partial' => false,
                    'has_paid' => false
                ];
            }
            
            // EXCLUDE arrears from the monthly aggregate sum
            if (isset($t['split_type']) && $t['split_type'] === 'dues_only') {
                // Do not add arrears/dues amount to the "Total Payment" row for this month
            } else {
                $mobile_aggregates[$m]['amount'] += (float)$t['amount'];
                $mobile_aggregates[$m]['remaining_amount'] += isset($t['remaining_amount']) ? (float)$t['remaining_amount'] : (float)$t['amount'];
            }"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath} mobile aggregators exclusion")
    else:
        print(f"Target not found in {filepath} mobile aggregators exclusion")
else:
    print(f"File not found: {filepath}")
