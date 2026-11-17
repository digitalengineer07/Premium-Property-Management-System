<?php
// Run from CLI after backing up the database. Never rewrites historical amounts.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/accounting_schema.php';
accounting_migrate($conn);
echo "Migration complete. Set each existing resident's rent_billing_mode to advance or arrears after checking their agreement. Historical invoices were not changed.\n";
