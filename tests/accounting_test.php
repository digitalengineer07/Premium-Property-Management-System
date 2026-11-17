<?php
require_once __DIR__ . '/../includes/accounting.php';
$checks = 0;
function check($expected, $actual, $label) {
    global $checks;
    $checks++;
    if ($expected !== $actual) throw new RuntimeException($label . ': ' . var_export($actual, true));
}
check(424800, accounting_applied(['paid_amount' => '4248.00', 'adjustment_amount' => '-6200.00', 'adjustment_type' => 'remaining']), 'Deficit is not a payment');
check(1000000, accounting_applied(['paid_amount' => '12000', 'adjustment_amount' => '2000', 'adjustment_type' => 'extra']), 'Excess belongs to wallet');
check('Partial', accounting_status(100, 99), 'Do not write off one paise');
check('2026-01', accounting_month('January 2026'), 'Legacy month');
check('2026-01', accounting_month('Jan 2026'), 'Onboarding month');
$bills = [];
foreach (['March 2026', 'January 2026', 'February 2026'] as $i => $month) {
    $bills[] = ['id' => $i + 1, 'month' => $month, 'type' => 'electricity', 'due_paise' => 1080000];
}
$plan = accounting_plan($bills, 2200000);
check(['January 2026', 'February 2026', 'March 2026'], array_column($plan['entries'], 'month'), 'Oldest invoices first');
check([1080000, 1080000, 40000], array_column($plan['entries'], 'allocated_paise'), 'Three months partial settlement');
check(0, $plan['credit_paise'], 'No invented credit');
check(1, accounting_plan($bills, 3240001)['credit_paise'], 'Preserve one paise credit');
check(1, accounting_plan($bills, 1)['entries'][0]['allocated_paise'], 'Allocate one paise');
check(['February 2026'], array_column(accounting_plan($bills, 10000, '2026-02')['entries'], 'month'), 'Target month normalization');
check(['amount' => 0, 'rent_amount' => 600000, 'maintenance' => 50000, 'extra_charges' => 20000, 'dues' => 0], accounting_components(['amount' => '800', 'rent_amount' => '10000', 'maintenance' => '500', 'extra_charges' => '200'], 480000), 'Component conservation');
foreach (['2026-13', 'garbage'] as $month) {
    try { accounting_month($month); throw new RuntimeException('Accepted invalid month'); }
    catch (InvalidArgumentException $e) { $checks++; }
}
foreach (['NaN', '1.001', '1e3'] as $amount) {
    try { accounting_paise($amount); throw new RuntimeException('Accepted invalid amount'); }
    catch (InvalidArgumentException $e) { $checks++; }
}
check('0.01', accounting_decimal(1), 'Decimal serialization');
echo "$checks accounting checks passed\n";
