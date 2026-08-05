<?php
// Script to safely refactor payment approvals

$desktop_file = 'c:\\xampp\\htdocs\\renter-system\\renter\\views\\desktop\\payment-approvals_desktop.php';
$mobile_file = 'c:\\xampp\\htdocs\\renter-system\\renter\\views\\mobile\\payment-approvals_mobile.php';
$main_file = 'c:\\xampp\\htdocs\\renter-system\\renter\\payment-approvals.php';

// 1. Refactor Desktop File
$desktop_content = file_get_contents($desktop_file);
$desktop_lines = explode("\n", $desktop_content);
$new_desktop_lines = array_slice($desktop_lines, 0, 3); // Keep first 3 lines (PHP opening)
$desktop_content_start = 0;
for ($i = 0; $i < count($desktop_lines); $i++) {
    if (strpos($desktop_lines[$i], '<div class="top-header"') !== false) {
        $desktop_content_start = $i;
        break;
    }
}
$desktop_content_end = 0;
for ($i = count($desktop_lines) - 1; $i >= 0; $i--) {
    if (strpos($desktop_lines[$i], '</main>') !== false) {
        $desktop_content_end = $i - 1;
        break;
    }
}
$new_desktop_lines = array_merge($new_desktop_lines, array_slice($desktop_lines, $desktop_content_start, $desktop_content_end - $desktop_content_start + 1));
file_put_contents($desktop_file, implode("\n", $new_desktop_lines));


// 2. Refactor Mobile File
$mobile_content = file_get_contents($mobile_file);
$mobile_lines = explode("\n", $mobile_content);
$new_mobile_lines = array_slice($mobile_lines, 0, 3); // Keep first 3 lines
$mobile_content_start = 0;
for ($i = 0; $i < count($mobile_lines); $i++) {
    if (strpos($mobile_lines[$i], '<div class="m-approvals-wrapper') !== false) {
        $mobile_content_start = $i;
        break;
    }
}
$mobile_content_end = 0;
for ($i = count($mobile_lines) - 1; $i >= 0; $i--) {
    if (strpos($mobile_lines[$i], '</body>') !== false) {
        $mobile_content_end = $i - 1;
        break;
    }
}
$new_mobile_lines = array_merge($new_mobile_lines, array_slice($mobile_lines, $mobile_content_start, $mobile_content_end - $mobile_content_start + 1));
file_put_contents($mobile_file, implode("\n", $new_mobile_lines));

echo "Refactored views!\n";
?>
