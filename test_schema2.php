<?php
require 'db.php';

function print_schema($conn, $table) {
    $res = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    $row = mysqli_fetch_array($res);
    echo $row[1] . "\n\n";
}

print_schema($conn, 'rent');
print_schema($conn, 'electricity');
print_schema($conn, 'payments');
?>
