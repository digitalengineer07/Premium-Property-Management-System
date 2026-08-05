<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT id, name, security_deposit, onboarding_completed, advance_payment FROM users WHERE onboarding_completed = 0 AND security_deposit > 0");
echo "Users with Onboarding = 0 and Security Deposit > 0:\n";
while($r = mysqli_fetch_assoc($q)) {
    $uid = $r['id'];
    $sec = $r['security_deposit'];
    $chk = mysqli_query($conn, "SELECT SUM(paid_amount) as tp FROM payments WHERE user_id = $uid AND bill_type = 'security_deposit'");
    $tp = mysqli_fetch_assoc($chk)['tp'] ?? 0;
    if ($tp == 0) {
        echo "User {$r['name']} (ID: $uid) has deposit $sec but NO payment ledger entry!\n";
    }
}
?>
