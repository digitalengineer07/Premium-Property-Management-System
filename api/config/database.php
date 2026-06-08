<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../config.php";

$is_localhost = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || strpos($_SERVER['SERVER_NAME'], '192.168.') !== false);

if ($is_localhost) {
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = ''; 
    $DB_NAME = 'renter_system';
} else {
    $DB_HOST = 'localhost';
    $DB_USER = 'u123456789_root';
    $DB_PASS = 'Your_DB_Password';
    $DB_NAME = 'u123456789_renter';
}

if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); }
$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Connection Error"]);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('Asia/Kolkata');
mysqli_query($conn, "SET time_zone = '+05:30'");
?>
