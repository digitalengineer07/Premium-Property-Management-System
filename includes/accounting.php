<?php
// Database-independent accounting rules. All arithmetic is in integer paise.
function accounting_paise($value): int {
    $value = trim((string)$value);
    if (!preg_match('/^(-?)(\d{1,10})(?:\.(\d{1,2}))?$/D', $value, $m)) {
        throw new InvalidArgumentException('Amount must be a decimal with at most two decimal places.');
    }
    return ($m[1] === '-' ? -1 : 1) * ((int)$m[2] * 100 + (int)str_pad($m[3] ?? '', 2, '0'));
}

function accounting_decimal(int $paise): string {
    return ($paise < 0 ? '-' : '') . intdiv(abs($paise), 100) . '.' . str_pad((string)(abs($paise) % 100), 2, '0', STR_PAD_LEFT);
}

function accounting_month(string $month): string {
    foreach (['Y-m', 'F Y', 'M Y'] as $format) {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $month);
        if ($date && $date->format($format) === $month) return $date->format('Y-m');
    }
    throw new InvalidArgumentException('Invalid billing month.');
}

function accounting_applied(array $payment): int {
    return accounting_paise($payment['paid_amount'] ?? 0)
        - (($payment['adjustment_type'] ?? '') === 'extra' ? max(0, accounting_paise($payment['adjustment_amount'] ?? 0)) : 0);
}

function accounting_status(int $charge, int $paid): string {
    return $paid >= $charge ? 'Paid' : ($paid > 0 ? 'Partial' : 'Due');
}

// Allocation priority is electricity, rent, maintenance, extras, then opening dues.
function accounting_components(array $bill, int $paid): array {
    $result = [];
    foreach (['amount', 'rent_amount', 'maintenance', 'extra_charges', 'dues'] as $field) {
        $charge = accounting_paise($bill[$field] ?? 0);
        $used = min(max(0, $paid), max(0, $charge));
        $result[$field] = max(0, $charge - $used);
        $paid -= $used;
    }
    return $result;
}

function accounting_plan(array $bills, int $received, ?string $target = null): array {
    if ($received <= 0) throw new InvalidArgumentException('Payment must be greater than zero.');
    $target = $target === null ? null : accounting_month($target);
    foreach ($bills as &$bill) $bill['period'] = accounting_month($bill['month']);
    unset($bill);
    usort($bills, function ($a, $b) {
        return [$a['period'], $a['type'] === 'electricity' ? 0 : 1, (int)$a['id'], $a['type']]
            <=> [$b['period'], $b['type'] === 'electricity' ? 0 : 1, (int)$b['id'], $b['type']];
    });
    $entries = [];
    foreach ($bills as $bill) {
        if ($received === 0) break;
        if ($target !== null && $target !== $bill['period']) continue;
        $used = min($received, max(0, $bill['due_paise']));
        if ($used === 0) continue;
        $bill['allocated_paise'] = $used;
        $entries[] = $bill;
        $received -= $used;
    }
    return ['entries' => $entries, 'credit_paise' => $received];
}
