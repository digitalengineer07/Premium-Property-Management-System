<?php
$c = file_get_contents('payment_modal.php');
// The file starts with a BOM possibly, or just UTF-16LE.
// If it starts with BOM (FF FE) or (FE FF), we should be careful.
// Let's just try iconv.
$c = iconv('UTF-16LE', 'UTF-8', $c);
file_put_contents('payment_modal.php', $c);
echo "Fixed encoding with iconv.\n";
