import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = """        usort($all_bills, function($a, $b) {
            return $b['p_ts'] - $a['p_ts'];
        });
"""

injection = """        usort($all_bills, function($a, $b) {
            return $b['p_ts'] - $a['p_ts'];
        });

        // KPI Calculations
        $total_all_amount = 0;
        $valid_payment_count = 0;
        foreach($all_bills as $b) {
            if (in_array(strtolower($b['status']), ['paid', 'approved'])) {
                $total_all_amount += $b['amount'];
                $valid_payment_count++;
            }
        }
        $avg_payment = $valid_payment_count > 0 ? ($total_all_amount / $valid_payment_count) : 0;
"""

if target in content:
    content = content.replace(target, injection)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Injected KPI calculations")
else:
    print("Target for KPI injection not found")
