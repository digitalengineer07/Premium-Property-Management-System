<?php
// admin/save-bill.php - Save generated electricity bill
ob_start(); // Buffer output to prevent headers already sent issues
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
require_once "../db.php";
session_start();

// Ensure output is ONLY JSON
if (ob_get_length()) ob_clean(); 
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security validation failed']);
    exit;
}

// Get POST data
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$bill_date = $_POST['bill_date'] ?? date('Y-m-d');
$bill_month = $_POST['bill_month'] ?? '';
$block_floor = $_POST['block_floor'] ?? '';
$previous_reading = (int)($_POST['previous_reading'] ?? 0);
$current_reading = (int)($_POST['current_reading'] ?? 0);
$rate_per_unit = (float)($_POST['rate_per_unit'] ?? 8.0);
$rent_amount = (float)($_POST['rent_amount'] ?? 0);
$maintenance = (float)($_POST['maintenance'] ?? 0);
$dues = (float)($_POST['dues'] ?? 0);
$extra_charges = (float)($_POST['extra_charges'] ?? 0);
$extra_charges_desc = $_POST['extra_charges_desc'] ?? '';

// Validate
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

if ($current_reading <= 0) {
    echo json_encode(['success' => false, 'message' => 'Current reading is required']);
    exit;
}

// Calculate values
$units_consumed = max(0, $current_reading - $previous_reading);
$electricity_amount = $units_consumed * $rate_per_unit;
$total_amount = $electricity_amount + $rent_amount + $maintenance + $dues + $extra_charges;

// Convert month to readable format (e.g., "2025-01" to "January 2025")
$month_display = $bill_month;
if ($bill_month) {
    $date_obj = DateTime::createFromFormat('Y-m', $bill_month);
    if ($date_obj) {
        $month_display = $date_obj->format('F Y');
    }
}

// Handle file upload (supports Crop + Original)
$meter_screenshot = null; // This will store the cropped version for main display
$meter_screenshot_orig = null;
$meter_screenshot_thumb = null;

$upload_dir = '../uploads/meter_readings/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

// 1. Handle Original Image
if (isset($_FILES['meter_original']) && $_FILES['meter_original']['error'] === UPLOAD_ERR_OK) {
    $file_ext = strtolower(pathinfo($_FILES['meter_original']['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (in_array($file_ext, $allowed_exts)) {
        $file_name_orig = 'meter_' . $user_id . '_' . time() . '_orig.' . $file_ext;
        $target_path_orig = $upload_dir . $file_name_orig;
        
        if (move_uploaded_file($_FILES['meter_original']['tmp_name'], $target_path_orig)) {
            chmod($target_path_orig, 0644);
            $meter_screenshot_orig = $file_name_orig;
            if (!isset($_FILES['meter_crop']) || $_FILES['meter_crop']['error'] !== UPLOAD_ERR_OK) {
                $meter_screenshot = $file_name_orig; // Fallback so image isn't completely lost
            }
        }
    }
}

// 2. Handle Cropped Image
if (isset($_FILES['meter_crop']) && $_FILES['meter_crop']['error'] === UPLOAD_ERR_OK) {
    $file_name_crop = 'meter_' . $user_id . '_' . time() . '_crop.jpg';
    $target_path_crop = $upload_dir . $file_name_crop;
    
    if (move_uploaded_file($_FILES['meter_crop']['tmp_name'], $target_path_crop)) {
        chmod($target_path_crop, 0644);
        $meter_screenshot = $file_name_crop;
        
        // Generate Thumbnail (300px wide)
        $thumb_name = 'meter_' . $user_id . '_' . time() . '_thumb.jpg';
        $thumb_path = $upload_dir . $thumb_name;
        
        if (extension_loaded('gd')) {
            $src_img = @imagecreatefromjpeg($target_path_crop);
            if ($src_img) {
                $width = imagesx($src_img);
                $height = imagesy($src_img);
                $thumb_width = 300;
                $thumb_height = floor($height * ($thumb_width / $width));
                
                $tmp_img = imagecreatetruecolor($thumb_width, $thumb_height);
                imagecopyresampled($tmp_img, $src_img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
                if (imagejpeg($tmp_img, $thumb_path, 80)) {
                    chmod($thumb_path, 0644);
                    $meter_screenshot_thumb = $thumb_name;
                }
                imagedestroy($src_img);
                imagedestroy($tmp_img);
            }
        }
    }
} elseif (isset($_FILES['meter_screenshot']) && $_FILES['meter_screenshot']['error'] === UPLOAD_ERR_OK) {
    // Fallback for non-cropped uploads (legacy support)
    $file_ext = strtolower(pathinfo($_FILES['meter_screenshot']['name'], PATHINFO_EXTENSION));
    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $file_name = 'meter_' . $user_id . '_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['meter_screenshot']['tmp_name'], $target_path)) {
            chmod($target_path, 0644);
            $meter_screenshot = $file_name;
        }
    }
}

// Insert into electricity table
$stmt = mysqli_prepare($conn, 
    "INSERT INTO electricity (
        user_id, month, payment_date, units, previous_reading, current_reading, 
        units_consumed, rate_per_unit, amount, rent_amount, maintenance, 
        dues, total_amount, meter_screenshot, meter_screenshot_orig, meter_screenshot_thumb,
        status, created_at, extra_charges, extra_charges_desc
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Due', NOW(), ?, ?)"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "issiiiiddddddsssds", 
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
    $meter_screenshot,
    $meter_screenshot_orig,
    $meter_screenshot_thumb,
    $extra_charges,
    $extra_charges_desc
);

if (mysqli_stmt_execute($stmt)) {
    $bill_id = mysqli_insert_id($conn);
    
    // Reset pending adjustment since it's now incorporated into this bill's "dues"
    mysqli_query($conn, "UPDATE users SET pending_adjustment = 0 WHERE id = $user_id");
    
    // Clean buffer and send success
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Bill generated successfully',
        'bill_id' => $bill_id,
        'total_amount' => $total_amount
    ]);
    exit;
} else {
    $error_msg = mysqli_stmt_error($stmt) ?: mysqli_error($conn);
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $error_msg
    ]);
    exit;
}
