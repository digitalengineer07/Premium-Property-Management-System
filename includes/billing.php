<?php
require_once __DIR__ . '/accounting_store.php';

// Call within the invoice transaction; the resident row serializes bill creation.
function accounting_validate_bill($conn, int $user, array $input, int $excludeId = 0): array {
    $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM users WHERE id=$user FOR UPDATE"));
    if (!$profile) throw new InvalidArgumentException('Resident not found.');
    $mode = $profile['rent_billing_mode'] ?? null;
    if (!in_array($mode, ['advance', 'arrears'], true)) throw new InvalidArgumentException('Set the resident rent billing mode (advance or arrears) before billing.');
    $month = accounting_month($input['bill_month']);
    if (!empty($profile['joining_date']) && $month < substr($profile['joining_date'], 0, 7)) throw new InvalidArgumentException('Cannot bill a period before the resident joined.');
    $issue = DateTimeImmutable::createFromFormat('!Y-m-d', $input['bill_date']);
    if (!$issue || $issue->format('Y-m-d') !== $input['bill_date']) throw new InvalidArgumentException('Invalid issue date.');
    if ($month > $issue->format('Y-m') || $month > date('Y-m')) throw new InvalidArgumentException('Cannot bill future electricity consumption.');
    $rows = accounting_query($conn, "SELECT month,current_reading FROM electricity WHERE user_id=$user AND id<>$excludeId FOR UPDATE");
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
    if ($values['rent_amount'] > 0) {
        $rents = accounting_query($conn, "SELECT month FROM rent WHERE user_id=$user");
        while ($r = mysqli_fetch_assoc($rents)) if (accounting_month($r['month']) === $rentMonth) throw new InvalidArgumentException('Rent for this period is already charged on a standalone invoice.');
    }
    return ['units'=>$units, 'rent_month'=>$rentMonth, 'values'=>array_map('accounting_decimal', $values)];
}

function accounting_update_bill($conn, int $id, int $user, array $input, ?string $file = null): void {
    require_once __DIR__ . '/../admin/allocate_payment.php';
    accounting_atomic($conn, function () use ($conn, $id, $user, $input, $file) {
        accounting_query($conn, "SELECT id FROM users WHERE id=$user FOR UPDATE");
        $old = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM electricity WHERE id=$id AND user_id=$user FOR UPDATE"));
        if (!$old) throw new RuntimeException('Invoice not found for this resident.');
        if (mysqli_num_rows(accounting_query($conn, "SELECT id FROM payments WHERE bill_id=$id AND bill_type IN ('electricity','elec_rent') LIMIT 1"))) throw new RuntimeException('An invoice with receipt history cannot be edited. Reverse/correct its accounting through an adjustment.');
        if (accounting_paise($old['dues'] ?? 0) !== 0) throw new RuntimeException('Historical carried dues must be reconciled before this invoice can change.');
        $input['bill_month'] = accounting_month($input['month']);
        if ($input['bill_month'] !== accounting_month($old['month'])) throw new RuntimeException('Invoice period cannot change.');
        $input['bill_date'] = $old['payment_date'] ?: substr($old['created_at'], 0, 10);
        $input['dues'] = 0;
        $v = accounting_validate_bill($conn, $user, $input, $id);
        $fields = [];
        foreach (['amount','rent_amount','maintenance','extra_charges','total_amount','rate_per_unit'] as $key) $fields[] = "$key=" . $v['values'][$key];
        $fields[] = 'previous_reading=' . (int)$input['previous_reading'];
        $fields[] = 'current_reading=' . (int)$input['current_reading'];
        $fields[] = 'units=' . $v['units'];
        $fields[] = 'units_consumed=' . $v['units'];
        $fields[] = "rent_month='" . $v['rent_month'] . "'";
        $fields[] = "extra_charges_desc='" . mysqli_real_escape_string($conn, $input['extra_charges_desc'] ?? '') . "'";
        if ($file !== null) $fields[] = "bill_file='" . mysqli_real_escape_string($conn, $file) . "'";
        accounting_query($conn, 'UPDATE electricity SET ' . implode(',', $fields) . " WHERE id=$id");
        recalculate_bill_status($conn, 'electricity', $id);
        accounting_event($conn, 'invoice_edited', $user, 'bill-' . $id, ['before'=>$old,'after'=>$v]);
    });
}
