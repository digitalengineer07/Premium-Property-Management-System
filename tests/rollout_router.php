<?php
// Local test server only, locked to the restored database.
if (PHP_SAPI !== 'cli-server' || getenv('RMS_DB_NAME') !== 'renter_rollout_test') { http_response_code(503); exit; }
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('~(^|/)(\.|tests/|tools/|includes/|migrations/)|credentials\.php|/config\.php|/db\.php~', $path)) { http_response_code(403); exit; }
return false;
