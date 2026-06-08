<?php
// config.php - Global configuration for the Rent Manager System

define('HOUSE_NAME', 'Madhav kunj');
define('HOUSE_ADDRESS', 'Vastu Estate colony Madhav Kunj apartment behind RPS School');
define('OWNER_NAME', 'Mr. Pramesh Kumar');

// You can add more global settings here
define('SYSTEM_NAME', 'Rent Manager');
define('DEFAULT_RATE', 8.00);

// Secret key for automated tasks (Cron Jobs)
define('CRON_KEY', 'rms_auth_' . md5(HOUSE_NAME . '2024'));
