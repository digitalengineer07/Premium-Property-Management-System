<?php
// Run from CLI after backing up the database. Never rewrites historical amounts.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/accounting_store.php';
foreach ([
    ['users', 'rent_billing_mode', "ENUM('advance','arrears') NULL"],
    ['electricity', 'rent_month', 'VARCHAR(7) NULL'],
] as [$table, $column, $definition]) {
    if (!mysqli_num_rows(accounting_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'"))) {
        accounting_query($conn, "ALTER TABLE $table ADD COLUMN $column $definition");
    }
}
foreach (['payments'=>['total_amount'], 'rent'=>['rent_amount'], 'electricity'=>['amount']] as $table=>$columns) {
    foreach ($columns as $column) accounting_query($conn, "ALTER TABLE $table MODIFY $column DECIMAL(12,2) NOT NULL DEFAULT 0");
}
echo "Migration complete. Set each existing resident's rent_billing_mode to advance or arrears after checking their agreement. Historical invoices were not changed.\n";
