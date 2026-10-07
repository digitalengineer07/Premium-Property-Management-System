<?php
// config.php - Global configuration for the Rent Manager System

define('HOUSE_NAME', 'Madhav Kunj');
define('HOUSE_ADDRESS', 'Vastu Estate colony behind RPS residents school, Patna Bihar- 801503');
define('OWNER_NAME', 'Mr. Pramesh Kumar');
define('CURRENCY', '₹');
define('PAYMENT_SECRET_KEY', 'xK9$p#2Lm@8vQ!4wZn*7yT&5cX^3jR');

// You can add more global settings here
define('SYSTEM_NAME', 'Rent Manager');
define('DEFAULT_RATE', 8.00);

// Secret key for automated tasks (Cron Jobs)
define('CRON_KEY', 'rms_auth_' . md5(HOUSE_NAME . '2024'));

/**
 * Format any number or amount into Indian Currency Format with proper comma grouping
 * e.g. 1000 => 1,000.00 | 100000 => 1,00,000.00 | 110318.5 => 1,10,318.50
 */
if (!function_exists('format_inr')) {
    function format_inr($amount, $decimals = 2) {
        if ($amount === null || $amount === '') return ($decimals > 0 ? '0.' . str_repeat('0', $decimals) : '0');
        $is_negative = false;
        $amount = (float)$amount;
        if ($amount < 0) {
            $is_negative = true;
            $amount = abs($amount);
        }
        $formatted = number_format($amount, $decimals, '.', '');
        $parts = explode('.', $formatted);
        $int_part = $parts[0];
        $dec_part = isset($parts[1]) && $decimals > 0 ? '.' . $parts[1] : '';
        if (strlen($int_part) > 3) {
            $last3 = substr($int_part, -3);
            $rest = substr($int_part, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $int_part = $rest . ',' . $last3;
        }
        return ($is_negative ? '-' : '') . $int_part . $dec_part;
    }
}
