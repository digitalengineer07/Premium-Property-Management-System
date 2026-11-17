<?php
// Copy to db_credentials.php (git-ignored), or use environment variables.
$DB_LOCAL_HOST = '127.0.0.1';
$DB_LOCAL_NAME = 'renter_system';
$DB_LOCAL_USER = 'root';
$DB_LOCAL_PASS = '';
$DB_PROD_HOST = 'localhost';
$DB_PROD_NAME = 'YOUR_DATABASE';
$DB_PROD_USER = 'YOUR_DATABASE_USER';
$DB_PROD_PASS = 'YOUR_DATABASE_PASSWORD';
// Preserve the existing value on upgrades so old receipt verification still works.
// For a fresh installation generate a cryptographically random secret.
$RMS_PAYMENT_SECRET = 'YOUR_RECEIPT_SIGNING_SECRET';
