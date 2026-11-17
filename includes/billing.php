<?php
require_once __DIR__ . '/accounting_store.php';

// Call within the invoice transaction; the resident row serializes bill creation.
function accounting_validate_bill($conn, int $user, array $input): array {
    $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM users WHERE id=$user FOR UPDATE"));
    if (!$profile) throw new InvalidArgumentException('Resident not found.');
    $mode = $profile['rent_billing_mode'] ?? null;
    if (!in_array($mode, ['advance', 'arrears'], true)) throw new InvalidArgumentException('Set the resident rent billing mode (advance or arrears) before billing.');
    $month = accounting_month($input['bill_month']);
    if (!empty($profile['joining_date']) && $month < substr($profile['joining_date'], 0, 7)) throw new InvalidArgumentException('Cannot bill a period before the resident joined.');
    $issue = DateTimeImmutable::createFromFormat('!Y-m-d', $input['bill_date']);
    if (!$issue || $issue->format('Y-m-d') !== $input['bill_date']) throw new InvalidArgumentException('Invalid issue date.');
    if ($month > $issue->format('Y-m') || $month > date('Y-m')) throw new InvalidArgumentException('Cannot bill future electricity consumption.');
    $rows = accounting_query($conn, "SELECT month,current_reading FROM electricity WHERE user_id=$user FOR UPDATE");
    $latest = null;
    while ($row = mysqli_fetch_assoc($rows)) {
        $period = accounting_month($row['month']);
        if ($period >= $month) throw new InvalidArgumentException('This month or a later month has already been billed.');
        if ($latest === null || $period > $latest['period']) $latest = ['period'=>$period, 'reading'=>(int)$row['current_reading']];
    }
    if ($latest && (new DateTimeImmutable($latest['period'].'-01'))->modify('+1 month')->format('Y-m') !== $month) throw new InvalidArgumentException('Generate the missing month first.');
    $previous = $latest ? $latest['reading'] : (int)$profile['base_reading'];
    if (!preg_match('/^\d{1,10}$/D', (string)$input['current_reading']) || (float)$input['current_reading'] > 2147483647 || (int)$input['current_reading'] < $previous) throw new InvalidArgumentException('Current reading must be a valid meter value at least equal to the last reading.');
    if ((int)$input['previous_reading'] !== $previous) throw new InvalidArgumentException('Previous reading has changed. Reload the resident before billing.');
    $units = (int)$input['current_reading'] - $previous;
    $values = [];
    foreach (['rate_per_unit','rent_amount','maintenance','extra_charges','dues'] as $field) {
        $values[$field] = accounting_paise($input[$field] ?? 0);
        if ($values[$field] < 0) throw new InvalidArgumentException('Charges cannot be negative.');
    }
    if ($values['dues'] !== 0) throw new InvalidArgumentException('Prior dues remain on their original invoices. Do not add them to a new bill.');
    $values['amount'] = $units * $values['rate_per_unit'];
    $values['total_amount'] = $values['amount'] + $values['rent_amount'] + $values['maintenance'] + $values['extra_charges'];
    if ($values['total_amount'] > 9999999999) throw new InvalidArgumentException('Invoice exceeds the supported amount.');
    $rentMonth = (new DateTimeImmutable($month.'-01'))->modify($mode === 'advance' ? '+1 month' : '+0 month')->format('Y-m');
    return ['units'=>$units, 'rent_month'=>$rentMonth, 'values'=>array_map('accounting_decimal', $values)];
}
