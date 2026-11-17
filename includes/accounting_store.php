<?php
require_once __DIR__ . '/accounting.php';

function accounting_event($conn, string $type, int $user, string $reference, array $detail): void {
    $actor = (int)($_SESSION['admin_id'] ?? 0);
    $json = json_encode($detail, JSON_THROW_ON_ERROR);
    $stmt = mysqli_prepare($conn, 'INSERT INTO accounting_events(event_type,user_id,actor_id,reference,detail) VALUES(?,?,?,?,?)');
    if (!$stmt) throw new RuntimeException('Accounting migration required.');
    mysqli_stmt_bind_param($stmt, 'siiss', $type, $user, $actor, $reference, $json);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Unable to save accounting audit event.');
}

function accounting_claim_receipt($conn, int $user, int $received, string $mode, string $external, string $reference): bool {
    if ($reference === '' || strlen($reference) > 100) throw new InvalidArgumentException('Invalid receipt reference.');
    $ref = mysqli_real_escape_string($conn, $reference);
    $prior = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM accounting_receipts WHERE reference='$ref' FOR UPDATE"));
    if ($prior) {
        if ((int)$prior['user_id'] !== $user || accounting_paise($prior['received']) !== $received || $prior['payment_mode'] !== $mode) throw new RuntimeException('Receipt reference already used for different details.');
        return false;
    }
    if (mysqli_num_rows(accounting_query($conn, "SELECT id FROM payments WHERE sys_tx_id='$ref' LIMIT 1"))) throw new RuntimeException('Receipt already exists in the historical ledger.');
    $external = trim($external);
    $uniqueExternal = in_array(strtolower($mode), ['upi','online','bank transfer','bank'], true) ? $external : null;
    if ($uniqueExternal !== null) {
        if ($external === '') throw new InvalidArgumentException('Bank/UPI transaction reference is required.');
        $safe = mysqli_real_escape_string($conn, $external);
        if (mysqli_num_rows(accounting_query($conn, "SELECT id FROM payments WHERE transaction_id='$safe' LIMIT 1"))) throw new RuntimeException('Transaction reference has already been posted.');
    }
    $decimal = accounting_decimal($received);
    $stmt = mysqli_prepare($conn, 'INSERT INTO accounting_receipts(reference,user_id,received,payment_mode,external_reference) VALUES(?,?,?,?,?)');
    mysqli_stmt_bind_param($stmt, 'sisss', $reference, $user, $decimal, $mode, $uniqueExternal);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Receipt or bank transaction was already processed.');
    accounting_event($conn, 'receipt', $user, $reference, ['amount'=>$decimal,'mode'=>$mode,'external_reference'=>$uniqueExternal]);
    return true;
}

function accounting_query($conn, string $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result === false) throw new RuntimeException('Accounting database operation failed: ' . mysqli_error($conn));
    return $result;
}

function accounting_atomic($conn, callable $work) {
    $nested = (int)mysqli_fetch_row(accounting_query($conn, 'SELECT @@in_transaction'))[0];
    $point = 'accounting_' . bin2hex(random_bytes(5));
    accounting_query($conn, $nested ? "SAVEPOINT $point" : 'START TRANSACTION');
    try {
        $result = $work();
        accounting_query($conn, $nested ? "RELEASE SAVEPOINT $point" : 'COMMIT');
        return $result;
    } catch (Throwable $error) {
        accounting_query($conn, $nested ? "ROLLBACK TO SAVEPOINT $point" : 'ROLLBACK');
        throw $error;
    }
}

function accounting_bill_paid($conn, int $user, string $table, int $id): int {
    $types = $table === 'rent' ? "'rent'" : "'electricity','elec_rent'";
    $row = mysqli_fetch_row(accounting_query($conn, "SELECT COALESCE(SUM(COALESCE(paid_amount,0) - CASE WHEN adjustment_type='extra' THEN GREATEST(0,COALESCE(adjustment_amount,0)) ELSE 0 END),0) FROM payments WHERE user_id=$user AND bill_id=$id AND bill_type IN ($types)"));
    return accounting_paise($row[0]);
}

function accounting_post($conn, int $user, string $type, int $id, string $month, int $total, string $mode, int $paid, string $transaction, string $reference, ?string $date = null, ?string $time = null) {
    $total = accounting_decimal($total);
    $paid = accounting_decimal($paid);
    $date = $date ?? date('Y-m-d');
    $time = $time ?? date('H:i:s');
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date || $date > date('Y-m-d')) throw new InvalidArgumentException('Invalid payment date.');
    $hash = generate_payment_hash($user, $paid, $reference);
    $stmt = mysqli_prepare($conn, 'INSERT INTO payments (user_id,bill_type,bill_id,month,total_amount,payment_mode,paid_amount,payment_date,payment_time,transaction_id,sys_tx_id,verification_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    if (!$stmt) throw new RuntimeException('Cannot prepare receipt.');
    mysqli_stmt_bind_param($stmt, 'isisssssssss', $user, $type, $id, $month, $total, $mode, $paid, $date, $time, $transaction, $reference, $hash);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Cannot save receipt: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
}

function accounting_balances($conn, int $user, ?string $month = null): array {
    $target = $month === null ? null : accounting_month($month);
    $totals = ['amount'=>0, 'rent_amount'=>0, 'maintenance'=>0, 'extra_charges'=>0, 'dues'=>0];
    $paidTotal = 0;
    foreach (['rent', 'electricity'] as $table) {
        $rows = accounting_query($conn, "SELECT * FROM $table WHERE user_id=$user");
        while ($bill = mysqli_fetch_assoc($rows)) {
            if ($target !== null && accounting_month($bill['month']) !== $target) continue;
            $paid = accounting_bill_paid($conn, $user, $table, (int)$bill['id']);
            $paidTotal += $paid;
            if ($table === 'rent') {
                $totals['rent_amount'] += max(0, accounting_paise($bill['rent_amount']) - $paid);
            } else {
                foreach (accounting_components($bill, $paid) as $key => $due) $totals[$key] += $due;
            }
        }
    }
    $row = mysqli_fetch_assoc(accounting_query($conn, "SELECT advance_payment FROM users WHERE id=$user"));
    $credit = max(0, accounting_paise($row['advance_payment'] ?? 0));
    return ['components'=>$totals, 'paid_paise'=>$paidTotal, 'gross_paise'=>array_sum($totals), 'credit_paise'=>$credit,
        'net_paise'=>max(0, array_sum($totals) - ($target === null ? $credit : 0))];
}

function accounting_specific_payment($conn, int $user, string $type, int $id, $amount, string $mode, string $transaction, string $reference, ?string $date = null, ?string $time = null) {
    return accounting_atomic($conn, function () use ($conn, $user, $type, $id, $amount, $mode, $transaction, $reference, $date, $time) {
        $received = accounting_paise($amount);
        if ($received <= 0) throw new InvalidArgumentException('Payment must be positive.');
        if (!in_array($type, ['rent', 'electricity', 'elec_rent', 'advance'], true)) throw new InvalidArgumentException('Invalid bill type.');
        if (!mysqli_fetch_assoc(accounting_query($conn, "SELECT id FROM users WHERE id=$user FOR UPDATE"))) throw new RuntimeException('Resident not found.');
        if (!accounting_claim_receipt($conn, $user, $received, $mode, $transaction, $reference)) return false;
        $used = 0;
        if ($type !== 'advance') {
            $table = $type === 'rent' ? 'rent' : 'electricity';
            $bill = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM $table WHERE id=$id AND user_id=$user FOR UPDATE"));
            if (!$bill) throw new RuntimeException('The bill does not belong to this resident.');
            $total = accounting_paise($bill[$table === 'rent' ? 'rent_amount' : 'total_amount']);
            $paid = accounting_bill_paid($conn, $user, $table, $id);
            $due = max(0, $total - $paid);
            $used = min($due, $received);
            if ($used > 0) {
                accounting_post($conn, $user, $type, $id, $bill['month'], $total, $mode, $used, $transaction, $reference, $date, $time);
                recalculate_bill_status($conn, $type, $id);
            }
        }
        $excess = $received - $used;
        if ($excess > 0) {
            accounting_post($conn, $user, 'advance', 0, 'Advance', $excess, $mode, $excess, $transaction, $reference, $date, $time);
            $credit = accounting_decimal($excess);
            accounting_query($conn, "UPDATE users SET advance_payment=advance_payment+$credit WHERE id=$user");
        }
        return true;
    });
}

function accounting_deposit_paid($conn, int $user): int {
    $row = mysqli_fetch_row(accounting_query($conn, "SELECT COALESCE(SUM(paid_amount),0) FROM payments WHERE user_id=$user AND bill_type='security_deposit'"));
    return accounting_paise($row[0]);
}

function accounting_record_existing_deposit($conn, int $user, string $amount, string $transaction, string $reference, string $date): void {
    accounting_atomic($conn, function () use ($conn, $user, $amount, $transaction, $reference, $date) {
        $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT security_deposit FROM users WHERE id=$user FOR UPDATE"));
        if (!$profile) throw new InvalidArgumentException('Resident not found.');
        $received = accounting_paise($amount);
        if ($received <= 0 || trim($transaction) === '' || strlen($transaction) > 100) throw new InvalidArgumentException('Enter a positive amount and an original receipt reference of up to 100 characters.');
        if (!accounting_claim_receipt($conn, $user, $received, 'Historical Deposit', $transaction, $reference)) return;
        $target = accounting_paise($profile['security_deposit']);
        $paid = accounting_deposit_paid($conn, $user);
        if ($received > $target - $paid) throw new InvalidArgumentException('Amount exceeds the remaining security deposit.');
        if (mysqli_num_rows(accounting_query($conn, "SELECT id FROM payments WHERE user_id=$user AND transaction_id='" . mysqli_real_escape_string($conn, $transaction) . "' LIMIT 1"))) {
            throw new InvalidArgumentException('This receipt is already in the ledger. Review its existing allocation.');
        }
        accounting_post($conn, $user, 'security_deposit', 0, 'Security Deposit', $received, 'Historical Deposit', $received, $transaction, $reference, $date);
        if ($paid + $received === $target) accounting_query($conn, "UPDATE users SET onboarding_completed=1 WHERE id=$user");
        accounting_event($conn, 'historical_deposit', $user, $reference, ['amount'=>accounting_decimal($received),'receipt'=>$transaction,'date'=>$date]);
    });
}

function accounting_onboarding($conn, int $user, $amount, string $mode, string $transaction, string $reference) {
    return accounting_atomic($conn, function () use ($conn, $user, $amount, $mode, $transaction, $reference) {
        $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM users WHERE id=$user FOR UPDATE"));
        if (!$profile) throw new RuntimeException('Resident not found.');
        $received = accounting_paise($amount);
        if ($received < 0) throw new InvalidArgumentException('Negative joining payment.');
        if ($received === 0) return true; // No payment evidence means billing mode remains undecided.
        if (!accounting_claim_receipt($conn, $user, $received, $mode, $transaction, $reference)) return false;
        $rent = accounting_paise($profile['fixed_rent']);
        $secPaid = accounting_deposit_paid($conn, $user);
        $depositDue = max(0, accounting_paise($profile['security_deposit']) - $secPaid);
        if (empty($profile['rent_billing_mode']) && !in_array($received, [$depositDue, $depositDue + $rent], true)) throw new InvalidArgumentException('Joining payment must cover the deposit, or the deposit plus the first month rent.');
        $sec = min($received, max(0, accounting_paise($profile['security_deposit']) - $secPaid));
        if ($sec > 0) accounting_post($conn, $user, 'security_deposit', 0, 'Security Deposit', $sec, $mode, $sec, $transaction, $reference);
        $left = $received - $sec;
        $billingMode = $profile['rent_billing_mode'] ?? ($left >= $rent && $rent > 0 ? 'advance' : 'arrears');
        if (empty($profile['rent_billing_mode']) && $billingMode === 'advance') {
            $month = (new DateTimeImmutable($profile['joining_date']))->format('F Y');
            $rentDecimal = accounting_decimal($rent);
            $stmt = mysqli_prepare($conn, "INSERT INTO rent(user_id,month,rent_amount,status,due_date) VALUES (?, ?, ?, 'Due', ?)");
            mysqli_stmt_bind_param($stmt, 'isss', $user, $month, $rentDecimal, $profile['joining_date']);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Unable to create joining rent invoice.');
            $bill = mysqli_insert_id($conn);
            accounting_post($conn, $user, 'rent', $bill, $month, $rent, $mode, $rent, $transaction, $reference);
            recalculate_bill_status($conn, 'rent', $bill);
            $left -= $rent;
        }
        if ($left > 0) {
            accounting_post($conn, $user, 'advance', 0, 'Advance', $left, $mode, $left, $transaction, $reference);
            $credit = accounting_decimal($left);
            accounting_query($conn, "UPDATE users SET advance_payment=advance_payment+$credit WHERE id=$user");
        }
        $complete = $sec + $secPaid >= accounting_paise($profile['security_deposit']) ? 1 : 0;
        $source = mysqli_real_escape_string($conn, 'joining:' . $reference);
        accounting_query($conn, "UPDATE users SET rent_billing_mode='$billingMode', billing_mode_source=COALESCE(billing_mode_source,'$source'), onboarding_completed=$complete WHERE id=$user");
        accounting_event($conn, 'joining_split', $user, $reference, ['mode'=>$billingMode,'security'=>accounting_decimal($sec),'rent'=>accounting_decimal($received-$sec-$left),'wallet'=>accounting_decimal($left)]);
        return true;
    });
}

function accounting_reverse_receipt($conn, int $paymentId, string $reason): void {
    if (trim($reason) === '') throw new InvalidArgumentException('A reversal reason is required.');
    accounting_atomic($conn, function () use ($conn, $paymentId, $reason) {
        $row = mysqli_fetch_assoc(accounting_query($conn, "SELECT * FROM payments WHERE id=$paymentId"));
        if (!$row) throw new RuntimeException('Receipt not found.');
        $user = (int)$row['user_id'];
        $profile = mysqli_fetch_assoc(accounting_query($conn, "SELECT advance_payment FROM users WHERE id=$user FOR UPDATE"));
        $original = $row['sys_tx_id'] ?: 'legacy-row-' . $paymentId;
        if (strpos($original, 'REV-') === 0) throw new RuntimeException('A reversal cannot itself be reversed.');
        $ref = mysqli_real_escape_string($conn, $original);
        if (mysqli_num_rows(accounting_query($conn, "SELECT original_reference FROM accounting_reversals WHERE original_reference='$ref'"))) throw new RuntimeException('Receipt has already been reversed.');
        $where = $row['sys_tx_id'] ? "user_id=$user AND sys_tx_id='$ref'" : "id=$paymentId";
        $rows = mysqli_fetch_all(accounting_query($conn, "SELECT * FROM payments WHERE $where FOR UPDATE"), MYSQLI_ASSOC);
        $walletDebit = 0;
        foreach ($rows as $payment) {
            if (in_array($payment['payment_mode'], ['wallet','Advance Wallet Auto-Deduction','Advance Credit'], true)) throw new RuntimeException('Reverse the source receipt only after its wallet allocations have been reconciled.');
            if ($payment['bill_type'] === 'advance') $walletDebit += accounting_paise($payment['paid_amount']);
            if (($payment['adjustment_type'] ?? '') === 'extra') $walletDebit += max(0, accounting_paise($payment['adjustment_amount'] ?? 0));
        }
        if ($walletDebit > accounting_paise($profile['advance_payment'])) throw new RuntimeException('Receipt credit was already consumed. Reconcile its allocations before reversal.');
        $reverse = 'REV-' . bin2hex(random_bytes(12));
        $stmt = mysqli_prepare($conn, 'INSERT INTO accounting_reversals(original_reference,reversal_reference,reason) VALUES(?,?,?)');
        mysqli_stmt_bind_param($stmt, 'sss', $original, $reverse, $reason);
        if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Cannot record reversal.');
        foreach ($rows as $payment) {
            $amount = accounting_applied($payment);
            accounting_post($conn, $user, $payment['bill_type'], (int)$payment['bill_id'], $payment['month'], -$amount, 'Reversal', -$amount, $original, $reverse);
            if (($payment['adjustment_type'] ?? '') === 'extra' && accounting_paise($payment['adjustment_amount'] ?? 0) > 0) {
                $excess = accounting_paise($payment['adjustment_amount']);
                accounting_post($conn, $user, 'advance', 0, 'Advance', -$excess, 'Reversal', -$excess, $original, $reverse);
            }
            if (in_array($payment['bill_type'], ['rent','electricity','elec_rent'], true) && (int)$payment['bill_id'] > 0) recalculate_bill_status($conn, $payment['bill_type'], $payment['bill_id']);
        }
        $debit = accounting_decimal($walletDebit);
        accounting_query($conn, "UPDATE users SET advance_payment=advance_payment-$debit WHERE id=$user");
        accounting_event($conn, 'receipt_reversed', $user, $reverse, ['original'=>$original, 'reason'=>$reason]);
    });
}
