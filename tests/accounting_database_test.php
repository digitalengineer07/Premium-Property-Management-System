<?php
// Dedicated test server only. Temporary tables disappear when this connection closes.
if (PHP_SAPI !== 'cli') exit;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect('127.0.0.1', 'root', '', null, (int)(getenv('ACCOUNTING_TEST_PORT') ?: 3306));
mysqli_query($conn, 'CREATE DATABASE IF NOT EXISTS accounting_test');
mysqli_select_db($conn, 'accounting_test');
function generate_payment_hash($user, $amount, $ref) { return hash('sha256', "$user:$amount:$ref"); }
require_once __DIR__ . '/../admin/allocate_payment.php';
require_once __DIR__ . '/../includes/billing.php';
require_once __DIR__ . '/accounting_test.php';
accounting_query($conn, "CREATE TEMPORARY TABLE users (id INT PRIMARY KEY, advance_payment DECIMAL(12,2) DEFAULT 0, security_deposit DECIMAL(12,2) DEFAULT 10000, fixed_rent DECIMAL(12,2) DEFAULT 10000, joining_date DATE DEFAULT '2026-01-01', rent_billing_mode VARCHAR(20) NULL, onboarding_completed INT DEFAULT 0, base_reading INT DEFAULT 0, billing_mode_source VARCHAR(100) NULL) ENGINE=InnoDB");
accounting_query($conn, "CREATE TEMPORARY TABLE rent (id INT PRIMARY KEY AUTO_INCREMENT,user_id INT,month VARCHAR(20),rent_amount DECIMAL(12,2),status VARCHAR(20),due_date DATE,paid_date DATE) ENGINE=InnoDB");
accounting_query($conn, "CREATE TEMPORARY TABLE electricity (id INT PRIMARY KEY AUTO_INCREMENT,user_id INT,month VARCHAR(20),amount DECIMAL(12,2) DEFAULT 0,rent_amount DECIMAL(12,2) DEFAULT 0,maintenance DECIMAL(12,2) DEFAULT 0,dues DECIMAL(12,2) DEFAULT 0,extra_charges DECIMAL(12,2) DEFAULT 0,total_amount DECIMAL(12,2),status VARCHAR(20),elec_status VARCHAR(20),rent_status VARCHAR(20),paid_date DATE,current_reading INT) ENGINE=InnoDB");
accounting_query($conn, "CREATE TEMPORARY TABLE payments (id INT PRIMARY KEY AUTO_INCREMENT,user_id INT,bill_type VARCHAR(50),bill_id INT,month VARCHAR(50),total_amount DECIMAL(12,2),payment_mode VARCHAR(50),paid_amount DECIMAL(12,2),payment_date DATE,payment_time TIME,transaction_id VARCHAR(100),sys_tx_id VARCHAR(100),verification_hash VARCHAR(64),adjustment_type VARCHAR(30),adjustment_amount DECIMAL(12,2) DEFAULT 0) ENGINE=InnoDB");
accounting_query($conn, "CREATE TEMPORARY TABLE accounting_events(id INT AUTO_INCREMENT PRIMARY KEY,event_type VARCHAR(50),user_id INT,actor_id INT,reference VARCHAR(100),detail LONGTEXT) ENGINE=InnoDB");
accounting_query($conn, "CREATE TEMPORARY TABLE accounting_receipts(reference VARCHAR(100) PRIMARY KEY,user_id INT,received DECIMAL(12,2),payment_mode VARCHAR(50),external_reference VARCHAR(100) UNIQUE) ENGINE=InnoDB");
accounting_query($conn, "CREATE TEMPORARY TABLE accounting_reversals(original_reference VARCHAR(100) PRIMARY KEY,reversal_reference VARCHAR(100),reason VARCHAR(500)) ENGINE=InnoDB");
accounting_query($conn, 'INSERT INTO users(id) VALUES(1),(2),(3),(4)');
accounting_onboarding($conn, 1, '20000', 'Cash', 'join1', 'join1');
check('advance', mysqli_fetch_row(accounting_query($conn, 'SELECT rent_billing_mode FROM users WHERE id=1'))[0], 'Two months selects advance mode');
check('Paid', mysqli_fetch_row(accounting_query($conn, 'SELECT status FROM rent WHERE user_id=1'))[0], 'Joining rent has a paid invoice');
check(0, accounting_balances($conn, 1)['net_paise'], 'Joining rent not demanded again');
accounting_onboarding($conn, 2, '10000', 'Cash', 'join2', 'join2');
check('arrears', mysqli_fetch_row(accounting_query($conn, 'SELECT rent_billing_mode FROM users WHERE id=2'))[0], 'Deposit-only selects arrears');
check(0, accounting_balances($conn, 2)['credit_paise'], 'Security deposit is not wallet credit');
$input = ['bill_month'=>'2026-01','bill_date'=>'2026-02-03','current_reading'=>'100','previous_reading'=>'0','rate_per_unit'=>'8','rent_amount'=>'10000','maintenance'=>'500','extra_charges'=>'0','dues'=>'0'];
check('2026-02', accounting_atomic($conn, fn()=>accounting_validate_bill($conn, 1, $input))['rent_month'], 'January utilities with February advance rent');
check('2026-01', accounting_atomic($conn, fn()=>accounting_validate_bill($conn, 2, $input))['rent_month'], 'January utilities with January arrears rent');
check('11300.00', accounting_atomic($conn, fn()=>accounting_validate_bill($conn, 1, $input))['values']['total_amount'], 'Meter and bill calculation');
foreach (['January 2026','February 2026','March 2026'] as $month) accounting_query($conn, "INSERT INTO electricity(user_id,month,amount,rent_amount,total_amount,status,current_reading) VALUES(2,'$month',800,10000,10800,'Due',100)");
allocate_bulk_payment($conn, 2, '22000', 'UPI', 'utr1', 'receipt1');
check(['Paid','Paid','Partial'], array_column(mysqli_fetch_all(accounting_query($conn, 'SELECT status FROM electricity ORDER BY id'), MYSQLI_ASSOC), 'status'), 'Database multi-month statuses');
check(1040000, accounting_balances($conn, 2)['net_paise'], 'Three-month arrears after partial payment');
check(false, allocate_bulk_payment($conn, 2, '22000', 'UPI', 'utr1', 'receipt1'), 'Receipt replay is idempotent');
check(1040000, accounting_balances($conn, 2)['net_paise'], 'Replay leaves balance unchanged');
allocate_bulk_payment($conn, 2, '10400.01', 'Cash', 'cash2', 'receipt2');
check(1, accounting_balances($conn, 2)['credit_paise'], 'Paise overpayment saved');
accounting_query($conn, "INSERT INTO rent(user_id,month,rent_amount,status) VALUES(2,'April 2026',1,'Due')");
allocate_bulk_payment($conn, 2, '0.01', 'wallet', 'wallet1', 'wallet1', null, true);
check(99, accounting_balances($conn, 2)['net_paise'], 'Wallet transfers exactly one paise');
check('Partial', mysqli_fetch_row(accounting_query($conn, 'SELECT status FROM rent WHERE user_id=2'))[0], 'One paise does not settle one rupee');
accounting_specific_payment($conn, 2, 'advance', 0, '50', 'Cash', 'cash3', 'receipt3');
try {
    accounting_atomic($conn, function () use ($conn) {
        allocate_bulk_payment($conn, 2, '50', 'wallet', 'fail', 'fail', null, true);
        throw new RuntimeException('Simulated failure');
    });
} catch (RuntimeException $e) {}
check(5000, accounting_balances($conn, 2)['credit_paise'], 'Failed outer transaction restores wallet');
check(0, (int)mysqli_fetch_row(accounting_query($conn, "SELECT COUNT(*) FROM payments WHERE sys_tx_id='fail'"))[0], 'Failed transaction leaves no postings');
try { accounting_specific_payment($conn, 3, 'electricity', 1, '50', 'Cash', 'wrong', 'wrong'); throw new Exception('Ownership check absent'); }
catch (RuntimeException $e) { $checks++; }
check(0, accounting_balances($conn, 3)['credit_paise'], 'Wrong resident cannot pay another invoice');
foreach ([['dues'=>'10800'], ['rate_per_unit'=>'-8'], ['current_reading'=>'-1'], ['previous_reading'=>'99'], ['bill_month'=>'2026-13'], ['rent_amount'=>'10000.001']] as $change) {
    try {
        accounting_atomic($conn, fn()=>accounting_validate_bill($conn, 1, array_merge($input, $change)));
        throw new Exception('Invalid invoice accepted');
    } catch (InvalidArgumentException $e) { $checks++; }
}
check(0, accounting_atomic($conn, fn()=>accounting_validate_bill($conn, 1, array_merge($input, ['current_reading'=>'0'])))['units'], 'Zero consumption is valid');
accounting_query($conn, "INSERT INTO electricity(user_id,month,amount,rent_amount,total_amount,status) VALUES(4,'January 2026',448,10000,10448,'Partial')");
$legacyBill = mysqli_insert_id($conn);
accounting_query($conn, "INSERT INTO payments(user_id,bill_type,bill_id,paid_amount,adjustment_type,adjustment_amount) VALUES(4,'electricity',$legacyBill,4248,'remaining',-6200)");
check(620000, accounting_balances($conn, 4)['net_paise'], 'Legacy negative adjustment does not extinguish arrears');
check(620000, accounting_balances($conn, 4, '2026-01')['net_paise'], 'Monthly view deducts receipt once');
// Replay and duplicate external-reference protection.
try { allocate_bulk_payment($conn, 2, '22001', 'UPI', 'utr1', 'receipt1'); throw new Exception('Changed replay accepted'); }
catch (RuntimeException $e) { $checks++; }
try { allocate_bulk_payment($conn, 3, '100', 'UPI', 'utr1', 'other-reference'); throw new Exception('Duplicate UTR accepted'); }
catch (RuntimeException $e) { $checks++; }
check(0, accounting_balances($conn, 3)['credit_paise'], 'Duplicate UTR leaves other resident unchanged');
accounting_onboarding($conn, 3, '0', 'Cash', 'zero', 'zero');
check(null, mysqli_fetch_row(accounting_query($conn, 'SELECT rent_billing_mode FROM users WHERE id=3'))[0], 'No joining receipt means no invented mode');
accounting_onboarding($conn, 3, '20000', 'UPI', 'new-join-utr', 'later-joining');
check('advance', mysqli_fetch_row(accounting_query($conn, 'SELECT rent_billing_mode FROM users WHERE id=3'))[0], 'Deferred verified joining payment selects advance automatically');
$firstPayment = (int)mysqli_fetch_row(accounting_query($conn,"SELECT id FROM payments WHERE sys_tx_id='later-joining' ORDER BY id LIMIT 1"))[0];
accounting_reverse_receipt($conn, $firstPayment, 'Test mistaken receipt');
check(1000000, accounting_balances($conn,3)['net_paise'], 'Receipt reversal reopens the joining invoice');
check('0.00', mysqli_fetch_row(accounting_query($conn,"SELECT SUM(paid_amount) FROM payments WHERE user_id=3 AND bill_type='security_deposit'"))[0], 'Receipt reversal also reverses its deposit split');
try { accounting_reverse_receipt($conn, $firstPayment, 'Repeated'); throw new Exception('Repeated reversal accepted'); }
catch (RuntimeException $e) { $checks++; }
check(1000000, accounting_balances($conn,3)['net_paise'], 'Repeated reversal cannot double the debt');

// Opaque authentication sessions, role restrictions, expiry and password-change revocation.
require_once __DIR__ . '/../includes/api_auth.php';
accounting_query($conn,"ALTER TABLE users ADD username VARCHAR(50), ADD password VARCHAR(255), ADD status VARCHAR(20) DEFAULT 'active'");
accounting_query($conn,"CREATE TEMPORARY TABLE api_sessions(token_hash CHAR(64) PRIMARY KEY,account_id INT,role VARCHAR(20),password_fingerprint CHAR(64),expires_at DATETIME,revoked_at DATETIME NULL) ENGINE=InnoDB");
accounting_query($conn,"UPDATE users SET username='test',password='hash-one' WHERE id=1");
$token = api_issue_token($conn, 1, 'resident', 'hash-one');
check(1, api_validate_token($conn, $token, 'resident')['id'], 'Issued session identifies actual account');
check(null, api_validate_token($conn, $token, 'admin'), 'Resident cannot call admin API');
check(null, api_validate_token($conn, base64_encode(json_encode(['id'=>1,'role'=>'admin']))), 'Forged legacy identity rejected');
check(null, api_validate_token($conn, str_repeat('a',64)), 'Unissued opaque token rejected');
accounting_query($conn,"UPDATE users SET password='hash-two' WHERE id=1");
check(null, api_validate_token($conn, $token), 'Password change invalidates old sessions');
$token = api_issue_token($conn, 1, 'resident', 'hash-two');
accounting_query($conn,"UPDATE api_sessions SET expires_at='2000-01-01'");
check(null, api_validate_token($conn, $token), 'Expired session rejected');
$token = api_issue_token($conn, 1, 'resident', 'hash-two');
accounting_query($conn,"UPDATE users SET status='inactive' WHERE id=1");
check(null, api_validate_token($conn, $token), 'Inactive account cannot use existing session');
echo "$checks total unit and database checks passed\n";
