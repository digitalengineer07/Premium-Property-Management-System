<?php
$pages = [
    'dashboard.php',
    'profile.php',
    'notices.php',
    'queries.php',
    'documents.php',
    'my-bills.php',
    'my-payments.php',
    'payment-history.php',
    'electricity-record.php'
];

foreach ($pages as $p) {
    if (!file_exists($p)) continue;
    $c = file_get_contents($p);

    // 1. Remove dummy assignments to $unread_count that might overwrite fetch_notifications.php
    $c = preg_replace('/^\s*\$unread_count\s*=\s*\d+;.*$/m', '', $c);
    $c = preg_replace('/^\s*\$unread_count\s*=\s*\$new_notices;.*$/m', '', $c);
    $c = preg_replace('/^\s*\$unread_count\s*=\s*count\(\$unread_notifications\);.*$/m', '', $c);
    $c = preg_replace('/^\s*\$unread_notifications\s*=\s*\[\];.*$/m', '', $c);

    // 2. Add require_once "fetch_notifications.php"; if not present
    if (strpos($c, 'fetch_notifications.php') === false) {
        // Find where $user_id is defined: e.g. $user_id = (int) $_SESSION['user_id'];
        if (preg_match('/(\$user_id\s*=\s*\(int\)\s*\$_SESSION\[\'user_id\'\];)/', $c)) {
            $c = preg_replace('/(\$user_id\s*=\s*\(int\)\s*\$_SESSION\[\'user_id\'\];)/', "$1\nrequire_once \"fetch_notifications.php\";", $c, 1);
        } elseif (preg_match('/(require_once\s+[\'"]\.\.\/db\.php[\'"];)/', $c)) {
            $c = preg_replace('/(require_once\s+[\'"]\.\.\/db\.php[\'"];)/', "$1\nrequire_once \"fetch_notifications.php\";", $c, 1);
        }
    }

    file_put_contents($p, $c);
    echo "Unified notifications backend for: $p\n";
}
?>
