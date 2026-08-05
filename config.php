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
