<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database_connection.php';
require_once __DIR__ . '/includes/payment_forms.php';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
}
try {
    $conn = rms_database_connection();
} catch (Throwable $failure) {
    error_log('Database connection failed: ' . $failure->getMessage());
    http_response_code(503);
    exit('Database unavailable. Please contact the administrator.');
}
// --- SECURITY & SESSION HELPERS ---

if (!function_exists('getCsrfToken')) {
    function getCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf']) || !is_string($token) || $token === '') return false;
        return hash_equals($_SESSION['csrf'], $token);
    }
}

if (!function_exists('s')) {
    function s($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('e')) {
    function e($conn, $text) {
        if ($text === null) return '';
        return mysqli_real_escape_string($conn, trim((string)$text));
    }
}

if (!function_exists('generate_payment_hash')) {
    function generate_payment_hash($user_id, $amount, $sys_tx_id) {
        if (!defined('PAYMENT_SECRET_KEY') || PAYMENT_SECRET_KEY === '') throw new RuntimeException('Configure the receipt signing secret before recording payments.');
        $formatted_amount = number_format((float)$amount, 2, '.', '');
        return hash_hmac('sha256', $user_id . '_' . $formatted_amount . '_' . $sys_tx_id, PAYMENT_SECRET_KEY);
    }
}

if (!function_exists('updateLastSeen')) {
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
}

// Try to update last seen if session is already active
if (session_status() === PHP_SESSION_ACTIVE) {
    updateLastSeen($conn);
    getCsrfToken(); // Pre-generate CSRF token
}

