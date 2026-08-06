<?php
$c = file_get_contents('renter/payment-approvals.php');
$s = file_get_contents('extracted_styles.txt');
$c = str_replace('</head>', $s . "\n</head>", $c);
file_put_contents('renter/payment-approvals.php', $c);
echo "Styles injected successfully.\n";
