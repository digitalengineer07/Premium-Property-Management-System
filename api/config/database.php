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

if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); }

// Require the central root DB connection which already handles local vs prod
require_once __DIR__ . "/../../db.php";

if (!$conn) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Connection Error"]);
    exit();
}

mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('Asia/Kolkata');
mysqli_query($conn, "SET time_zone = '+05:30'");
?>
