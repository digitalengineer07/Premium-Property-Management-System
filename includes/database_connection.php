<?php
function rms_database_connection() {
    if (file_exists(__DIR__ . '/../db_credentials.php')) require __DIR__ . '/../db_credentials.php';
    // Deployment choice comes from configuration, never a client-controlled Host header.
    $production = getenv('RMS_ENV') === 'production';
    $host = getenv('RMS_DB_HOST') ?: ($production ? ($DB_PROD_HOST ?? '') : ($DB_LOCAL_HOST ?? '127.0.0.1'));
    $user = getenv('RMS_DB_USER') ?: ($production ? ($DB_PROD_USER ?? '') : ($DB_LOCAL_USER ?? 'root'));
    $password = getenv('RMS_DB_PASSWORD');
    if ($password === false) $password = $production ? ($DB_PROD_PASS ?? '') : ($DB_LOCAL_PASS ?? '');
    $database = getenv('RMS_DB_NAME') ?: ($production ? ($DB_PROD_NAME ?? '') : ($DB_LOCAL_NAME ?? 'renter_system'));
    $port = (int)(getenv('RMS_DB_PORT') ?: 3306);
    if (!$host || !$user || !$database) throw new RuntimeException('Database configuration missing.');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_connect($host, $user, $password, $database, $port);
    mysqli_set_charset($conn, 'utf8mb4');
    date_default_timezone_set('Asia/Kolkata');
    mysqli_query($conn, "SET time_zone='+05:30'");
    // Existing presentation code handles optional legacy tables with false returns.
    mysqli_report(MYSQLI_REPORT_OFF);
    return $conn;
}
