<?php
require_once "../config/database.php";
require_once __DIR__ . '/../../includes/api_auth.php';
$api_identity = api_require_auth($conn, 'admin');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Basic Authentication via Headers
$headers = ['Authorization' => 'Bearer ' . api_request_token()];
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access. No token provided."]);
    exit;
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

if ($current_reading < 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Current reading must be greater than 0"]);
    exit;
}

if ($current_reading < $previous_reading) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Current reading cannot be less than previous reading"]);
    exit;
}

// Ensure $bill_month is provided and valid before proceeding with advanced validations
if (empty($bill_month)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Bill month is required']);
    exit;
}

$incoming_date = DateTime::createFromFormat('!Y-m', $bill_month);
if (!$incoming_date || $incoming_date->format('Y-m') !== $bill_month) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid bill month format']);
    exit;
}

// Fetch the latest bill for chronological validation
$latest_query = mysqli_query($conn, "SELECT month, current_reading FROM electricity WHERE user_id = $user_id ORDER BY id DESC LIMIT 1");
if ($latest_query && mysqli_num_rows($latest_query) > 0) {
    $latest_bill = mysqli_fetch_assoc($latest_query);
    $latest_month_str = $latest_bill['month'];
    $latest_date = DateTime::createFromFormat('!F Y', $latest_month_str) ?: DateTime::createFromFormat('!Y-m', $latest_month_str);
    
    if ($latest_date) {
        $latest_ts = (int)$latest_date->format('Ym');
        $incoming_ts = (int)$incoming_date->format('Ym');
        
        // 1. Prevent Past or Current Month Generation
        if ($incoming_ts <= $latest_ts) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Bill generation for previous or current months is not allowed because newer bills already exist. Please generate bills in chronological order.']);
            exit;
        }
        
        // 2. Prevent Skipped Months
        $expected_next = clone $latest_date;
        $expected_next->modify('+1 month');
        $expected_ts = (int)$expected_next->format('Ym');
        
        if ($incoming_ts > $expected_ts) {
            $skipped_month_name = $expected_next->format('F Y');
            $incoming_month_name = $incoming_date->format('F');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "The bill for $skipped_month_name has not been generated yet. Please generate the $skipped_month_name bill before creating the $incoming_month_name bill to maintain accurate billing records."]);
            exit;
        }
        
        // 3. Validate Meter Reading Sequence
        if ($current_reading < (int)$latest_bill['current_reading']) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Current meter reading cannot be less than the previous recorded reading (' . $latest_bill['current_reading'] . ' units).']);
            exit;
        }
    }
}

require_once __DIR__ . '/../../includes/billing.php';
try {
    accounting_query($conn, 'START TRANSACTION');
    $validated = accounting_validate_bill($conn, $user_id, (array)$data);
    $units_consumed = $validated['units'];
    $electricity_amount = $validated['values']['amount'];
    $total_amount = $validated['values']['total_amount'];
} catch (Throwable $failure) {
    mysqli_rollback($conn);
    http_response_code(400);
    $message = $failure instanceof InvalidArgumentException ? $failure->getMessage() : 'Unable to validate invoice.';
    echo json_encode(['success'=>false, 'status'=>'error', 'message'=>$message]);
    exit;
}

// Convert month string if needed (e.g., from '2025-01' to 'January 2025')
$month_display = $bill_month;
if (strpos($bill_month, '-') !== false && strlen($bill_month) === 7) {
    $date_obj = DateTime::createFromFormat('!Y-m', $bill_month);
    if ($date_obj) {
        $month_display = $date_obj->format('F Y');
    }
}

// Ensure the connection works
try {
    // Start transaction
    // Transaction started before validation and resident locking.

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
    require_once __DIR__ . '/../../admin/allocate_payment.php';
    recalculate_bill_status($conn, 'electricity', $bill_id);
    $rent_period = $validated['rent_month'];
    accounting_query($conn, "UPDATE electricity SET rent_month='$rent_period', due_date=DATE_ADD(payment_date, INTERVAL 10 DAY) WHERE id=$bill_id");

    
    
    // Legacy pending_adjustment logic removed

    require_once __DIR__ . '/../../admin/allocate_payment.php';
    $wallet = mysqli_fetch_assoc(accounting_query($conn, "SELECT advance_payment FROM users WHERE id=$user_id"))['advance_payment'];
    if (accounting_paise($wallet) > 0) {
        $ref = 'SYS_ADV_' . bin2hex(random_bytes(8));
        allocate_bulk_payment($conn, $user_id, $wallet, 'Advance Wallet Auto-Deduction', $ref, $ref, null, true);
    }
    mysqli_commit($conn);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Bill generated successfully',
        'bill_id' => $bill_id,
        'total_amount' => $total_amount
    ]);

} catch (Throwable $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save bill'
    ]);
}
?>
