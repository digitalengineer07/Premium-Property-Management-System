<?php
// Run from CLI after backing up the database. Never rewrites historical amounts.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
// Refuse implicit targets: environment and credentials alone cannot select a migration DB.
$options = getopt('', ['database:', 'backup:']);
$target = $options['database'] ?? '';
if (!preg_match('/^[A-Za-z0-9_]+$/D', $target)) {
    fwrite(STDERR, "Usage: php migrations/20261117_accounting.php --database=NAME --backup=PATH\n"); exit(1);
}
if (strpos($target, 'test') === false && (empty($options['backup']) || !is_file($options['backup']) || filesize($options['backup']) === 0)) {
    fwrite(STDERR, "A non-empty backup file is required for a non-test database.\n"); exit(1);
}
putenv('RMS_DB_NAME=' . $target);
require_once __DIR__ . '/../includes/database_connection.php';
$conn = rms_database_connection();
if (mysqli_fetch_row(mysqli_query($conn, 'SELECT DATABASE()'))[0] !== $target) throw new RuntimeException('Migration target mismatch. No changes made.');
require_once __DIR__ . '/../includes/accounting_schema.php';
accounting_migrate($conn);
echo "Migration complete on $target. Historical invoice amounts were not changed.\n";
