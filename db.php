<?php
require_once __DIR__ . "/config.php";
// db.php - database connection and optional session security settings

// Apply session ini settings only if no session is active.
if (session_status() === PHP_SESSION_NONE) {
    // Hardening session settings for 30 minutes
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    // ini_set('session.cookie_path', '/');
    ini_set('session.cookie_lifetime', 3600); // 1 hour
    ini_set('session.gc_maxlifetime', 3600);  // 1 hour
    // ini_set('session.cookie_secure', 1); // Enable only if using HTTPS
}

// --- ENVIRONMENT DETECTION ---
$is_cli = php_sapi_name() === 'cli';
$is_localhost = $is_cli || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') || (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1' || strpos($_SERVER['REMOTE_ADDR'], '10.') === 0)) || (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], '192.168.') !== false);

// Require secure credentials file if it exists
if (file_exists(__DIR__ . '/db_credentials.php')) {
    require_once __DIR__ . '/db_credentials.php';
} else {
    // Fallback defaults if the file is missing
    $DB_LOCAL_HOST = 'localhost'; $DB_LOCAL_USER = 'root'; $DB_LOCAL_PASS = ''; $DB_LOCAL_NAME = 'renter_system';
    $DB_PROD_HOST = 'localhost'; $DB_PROD_USER = 'root'; $DB_PROD_PASS = ''; $DB_PROD_NAME = 'renter_system';
}

if ($is_localhost) {
    // Local XAMPP Settings
    $DB_HOST = $DB_LOCAL_HOST;
    $DB_USER = $DB_LOCAL_USER;
    $DB_PASS = $DB_LOCAL_PASS; 
    $DB_NAME = $DB_LOCAL_NAME;
} else {
    // HOSTINGER / PRODUCTION SETTINGS
    $DB_HOST = $DB_PROD_HOST;
    $DB_USER = $DB_PROD_USER;
    $DB_PASS = $DB_PROD_PASS;
    $DB_NAME = $DB_PROD_NAME;
}

// Try to connect to DB, but suppress explicit throw error warning so my UI can show instead
if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); }
$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    // If connection fails, show a clean error instead of a blank page
    header("Content-Type: text/html; charset=utf-8");
    ?>
    <div style="font-family:sans-serif; text-align:center; padding:50px; background:#f8fafc; color:#1e293b; min-height:100vh;">
        <div style="max-width:500px; margin:auto; background:white; padding:40px; border-radius:16px; box-shadow:0 10px 25px rgba(0,0,0,0.05); border:1px solid #e2e8f0;">
            <div style="font-size:48px; margin-bottom:20px;">⚙️</div>
            <h2 style="margin:0 0 10px;">Database Connection Error</h2>
            <p style="color:#64748b; font-size:15px; line-height:1.6;">The system could not connect to the database. If you just uploaded to <strong>Hostinger</strong>, please ensure your credentials in <code>db.php</code> are correct.</p>
            <div style="background:#fff1f1; padding:15px; border-radius:8px; font-family:monospace; font-size:13px; margin:20px 0; text-align:left; color:#ef4444; border: 1px solid #fee2e2;">
                <strong>Error Details:</strong><br>
                <?php echo mysqli_connect_error(); ?>
            </div>
            <div style="text-align: left; font-size: 13px; color: #475569; background: #f8fafc; padding: 15px; border-radius: 8px;">
                <strong>Checklist for Hostinger:</strong>
                <ul style="margin: 10px 0 0; padding-left: 20px;">
                    <li>Database name starts with <code>uNNNNNNNN_</code></li>
                    <li>Username starts with <code>uNNNNNNNN_</code></li>
                    <li>Host should be <code>localhost</code></li>
                    <li>Imported the SQL file via phpMyAdmin?</li>
                </ul>
            </div>
        </div>
    </div>
    <?php
    exit;
}

mysqli_set_charset($conn, "utf8mb4");

// Set Timezone to India (IST)
date_default_timezone_set('Asia/Kolkata');
mysqli_query($conn, "SET time_zone = '+05:30'");

// --- SECURITY & SESSION HELPERS ---

/**
 * Generates or retrieves a CSRF token for the current session.
 */
function getCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Verifies if the provided token matches the session CSRF token.
 */
function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf'], $token);
}

/**
 * Sanitize output for HTML context (XSS Prevention)
 */
function s($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Update "Last Seen" for residents (throttled to once every 5 mins)
function updateLastSeen($conn) {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $last_update = $_SESSION['last_seen_update'] ?? 0;
        if (time() - $last_update > 300) { // 300 seconds = 5 minutes
            @mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = $uid");
            $_SESSION['last_seen_update'] = time();
        }
    }
}
// Try to update last seen if session is already active
if (session_status() === PHP_SESSION_ACTIVE) {
    updateLastSeen($conn);
    getCsrfToken(); // Pre-generate CSRF token
}

// --- SILENT SCHEMA UPDATER (V36) ---
// Automatically deploy missing columns without crashing the live server.
if ($conn) {
    // Arrays of [Table] => [Column => Definition]
    $schema_updates = [
        'users' => [
            'aadhaar_file' => "varchar(255) DEFAULT NULL",
            'agreement_document' => "varchar(255) DEFAULT NULL",
            'agreement_upload_date' => "datetime DEFAULT NULL",
            'agreement_expiry_date' => "date DEFAULT NULL",
            'pending_adjustment' => "decimal(10,2) DEFAULT '0.00'",
            'advance_payment' => "decimal(10,2) DEFAULT '0.00'",
            'advance_updated_at' => "timestamp NULL DEFAULT NULL",
            'fixed_rent' => "decimal(10,2) DEFAULT '0.00'",
            'fixed_maintenance' => "decimal(10,2) DEFAULT '0.00'",
            'rent_maint_updated_at' => "timestamp NULL DEFAULT NULL",
            'rent_maint_updated_by' => "int(11) DEFAULT NULL",
            'base_reading' => "int(11) DEFAULT '0'"
        ],
        'electricity' => [
            'extra_charges' => "decimal(10,2) DEFAULT '0.00'",
            'extra_charges_desc' => "varchar(255) DEFAULT NULL",
            'meter_screenshot_orig' => "varchar(255) DEFAULT NULL",
            'meter_screenshot_thumb' => "varchar(255) DEFAULT NULL",
            'bill_file' => "varchar(255) DEFAULT NULL",
            'elec_status' => "varchar(20) DEFAULT 'Due'",
            'rent_status' => "varchar(20) DEFAULT 'Due'"
        ],
        'payment_notifications' => [
            'verified_by' => "varchar(100) DEFAULT NULL",
            'verified_at' => "datetime DEFAULT NULL"
        ]
    ];

    // Ensure payment_notifications table exists
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payment_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        bill_type VARCHAR(50) NOT NULL,
        bill_id INT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_id VARCHAR(50) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'UPI',
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        admin_note TEXT,
        verified_by VARCHAR(100) DEFAULT NULL,
        verified_at DATETIME DEFAULT NULL,
        is_dismissed TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    foreach ($schema_updates as $table => $columns) {
        foreach ($columns as $col => $def) {
            $check = @mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");
            if ($check && mysqli_num_rows($check) == 0) {
                @mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$col` $def");
            }
        }
    }
    
    @mysqli_query($conn, "ALTER TABLE payment_notifications MODIFY COLUMN bill_type VARCHAR(50) NOT NULL");
    @mysqli_query($conn, "ALTER TABLE payments MODIFY COLUMN bill_type VARCHAR(50) NOT NULL");
    
    $check_tx = @mysqli_query($conn, "SHOW COLUMNS FROM `payments` LIKE 'transaction_id'");
    if ($check_tx && mysqli_num_rows($check_tx) == 0) {
        @mysqli_query($conn, "ALTER TABLE `payments` ADD COLUMN `transaction_id` VARCHAR(100) DEFAULT NULL");
    }

    @mysqli_query($conn, "UPDATE electricity SET elec_status = 'Paid' WHERE elec_status = 'Due' AND status = 'Paid'");
    @mysqli_query($conn, "UPDATE electricity SET rent_status = 'Paid' WHERE rent_status = 'Due' AND status = 'Paid'");
}
