<?php
$files = glob("c:/xampp/htdocs/renter-system/renter/*.php");
$bell_block = file_get_contents("bell_block.txt");
$dismiss_script = file_get_contents("dismiss_script.txt");

// clean up the bell_block.txt and dismiss_script.txt output which might have PowerShell formatting
$bell_block = preg_replace('/^> /m', '', $bell_block);
$bell_block = preg_replace('/^renter\\\\dashboard\.php:\d+:/m', '', $bell_block);
$bell_block = trim($bell_block);

$dismiss_script = preg_replace('/^> /m', '', $dismiss_script);
$dismiss_script = preg_replace('/^renter\\\\dashboard\.php:\d+:/m', '', $dismiss_script);
$dismiss_script = trim($dismiss_script);

foreach($files as $f) {
    if(basename($f) == 'dashboard.php' || basename($f) == 'profile.php' || basename($f) == 'fix_notifications.php') continue;
    
    $c = file_get_contents($f);
    
    // Pattern to find the old static bell icon block
    $pattern = '/<div class="icon-btn"[^>]*>\s*<i class=\'bx bx-bell\'><\/i>\s*<\?php if \(\$unread_count > 0\): \?>\s*<span[^>]*><\?php echo \$unread_count; \?><\/span>\s*<\?php endif; \?>\s*<\/div>/is';
    
    if (preg_match($pattern, $c)) {
        // Replace with the notification wrapper
        $c = preg_replace($pattern, $bell_block, $c);
        
        // Ensure dismissNotification JS exists
        if (strpos($c, 'function dismissNotification') === false) {
            // inject before </body>
            $c = str_replace('</body>', "\n    <script>\n" . $dismiss_script . "\n    </script>\n</body>", $c);
        }
        
        // Also ensure click-outside logic exists
        if (strpos($c, 'const bell = document.querySelector(\'.bell-icon\');') === false) {
            $click_outside = "
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notifDropdown');
            const bell = document.querySelector('.bell-icon');
            if (dropdown && dropdown.style.display === 'block') {
                if (!dropdown.contains(event.target) && !bell.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            }
        });
            ";
            // inject after dismissNotification or before </body>
            $c = str_replace('</body>', $click_outside . "\n</body>", $c);
        }

        // Add the CSS for .notification-wrapper if not present
        if (strpos($c, '.notification-wrapper {') === false) {
            $css = "
        .notification-wrapper { position: relative; }
        #notifDropdown {
            position: absolute;
            top: 110%;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            border: 1px solid var(--border);
            z-index: 1000;
            overflow: hidden;
            animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
            ";
            $c = str_replace('</style>', $css . "\n    </style>", $c);
        }

        file_put_contents($f, $c);
        echo "Updated: " . basename($f) . "\n";
    }
}
?>
