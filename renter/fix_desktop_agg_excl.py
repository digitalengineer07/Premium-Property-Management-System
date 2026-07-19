import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'

target = """                        // First generate aggregates
                        $monthly_aggregates = [];
                        foreach($all_bills as $bill) {
                            $p = $bill['period'];
                            if(!isset($monthly_aggregates[$p])) {
                                $monthly_aggregates[$p] = [
                                    'period' => $p,
                                    'amount' => 0,
                                    'remaining_amount' => 0,
                                    'due_date' => $bill['due_date'],
                                    'paid_on' => '-',
                                    'has_unpaid' => false,
                                    'has_partial' => false,
                                    'has_paid' => false,
                                ];
                            }
                            $monthly_aggregates[$p]['amount'] += (float)$bill['amount'];
                            $monthly_aggregates[$p]['remaining_amount'] += isset($bill['remaining_amount']) ? (float)$bill['remaining_amount'] : (float)$bill['amount'];"""

replacement = """                        // First generate aggregates
                        $monthly_aggregates = [];
                        foreach($all_bills as $bill) {
                            $p = $bill['period'];
                            if(!isset($monthly_aggregates[$p])) {
                                $monthly_aggregates[$p] = [
                                    'period' => $p,
                                    'amount' => 0,
                                    'remaining_amount' => 0,
                                    'due_date' => $bill['due_date'],
                                    'paid_on' => '-',
                                    'has_unpaid' => false,
                                    'has_partial' => false,
                                    'has_paid' => false,
                                ];
                            }
                            
                            // EXCLUDE arrears from the monthly aggregate sum
                            if (isset($bill['filter_type']) && $bill['filter_type'] === 'other' && isset($bill['type']) && $bill['type'] === 'elec_rent') {
                                // Do not add arrears/dues amount to the "Total Payment" row for this month
                            } else {
                                $monthly_aggregates[$p]['amount'] += (float)$bill['amount'];
                                $monthly_aggregates[$p]['remaining_amount'] += isset($bill['remaining_amount']) ? (float)$bill['remaining_amount'] : (float)$bill['amount'];
                            }"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath} desktop aggregators exclusion")
    else:
        print(f"Target not found in {filepath} desktop aggregators exclusion")
else:
    print(f"File not found: {filepath}")
