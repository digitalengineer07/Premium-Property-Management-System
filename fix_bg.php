<?php
$file = 'renter/profile.php';
$content = file_get_contents($file);
$content = str_replace('background: #F8FAFC;', 'background: var(--bg-main);', $content);
file_put_contents($file, $content);
echo "Replaced successfully.";
?>
