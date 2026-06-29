<?php
$c = file_get_contents('payment_modal.php');
$c = mb_convert_encoding($c, 'UTF-8', 'UTF-16LE');
file_put_contents('payment_modal.php', $c);
echo "Fixed encoding.";
