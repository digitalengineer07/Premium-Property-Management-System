<?php
require_once "../config/database.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

$user_id = isset($data->user_id) ? (int)$data->user_id : 0;
$bill_date = $data->bill_date ?? date('Y-m-d');
$bill_month = $data->bill_month ?? '';
$previous_reading = (int)($data->previous_reading ?? 0);
$current_reading = (int)($data->current_reading ?? 0);
$rate_per_unit = (float)($data->rate_per_unit ?? 8.0);
$rent_amount = (float)($data->rent_amount ?? 0);
$maintenance = (float)($data->maintenance ?? 0);
$dues = (float)($data->dues ?? 0);
$extra_charges = (float)($data->extra_charges ?? 0);
$extra_charges_desc = $data->extra_charges_desc ?? '';

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid user ID"]);
    exit;
}

if ($current_reading <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Current reading must be greater than 0"]);
    exit;
}

if ($current_reading < $previous_reading) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Current reading cannot be less than previous reading"]);
    exit;
}

// Calculate totals securely on the backend
$units_consumed = $current_reading - $previous_reading;
$electricity_amount = $units_consumed * $rate_per_unit;
$total_amount = $electricity_amount + $rent_amount + $maintenance + $dues + $extra_charges;

// Convert month string if needed (e.g., from '2025-01' to 'January 2025')
$month_display = $bill_month;
if (strpos($bill_month, '-') !== false && strlen($bill_month) === 7) {
    $date_obj = DateTime::createFromFormat('Y-m', $bill_month);
    if ($date_obj) {
        $month_display = $date_obj->format('F Y');
    }
}

// Ensure the connection works
try {
    // Start transaction
    mysqli_begin_transaction($conn);

    $stmt = mysqli_prepare($conn, 
        "INSERT INTO electricity (
            user_id, month, payment_date, units, previous_reading, current_reading, 
            units_consumed, rate_per_unit, amount, rent_amount, maintenance, 
            dues, total_amount, status, created_at, extra_charges, extra_charges_desc
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Due', NOW(), ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "issiiiiddddddds", 
        $user_id, 
        $month_display, 
        $bill_date,
        $units_consumed,
        $previous_reading,
        $current_reading,
        $units_consumed,
        $rate_per_unit,
        $electricity_amount,
        $rent_amount,
        $maintenance,
        $dues,
        $total_amount,
        $extra_charges,
        $extra_charges_desc
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }

    $bill_id = mysqli_insert_id($conn);
    
    // Reset pending adjustment since dues are included in this bill
    $update_stmt = mysqli_prepare($conn, "UPDATE users SET pending_adjustment = 0 WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "i", $user_id);
    mysqli_stmt_execute($update_stmt);

    mysqli_commit($conn);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Bill generated successfully',
        'bill_id' => $bill_id,
        'total_amount' => $total_amount
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save bill',
        'error' => $e->getMessage()
    ]);
}
?>
