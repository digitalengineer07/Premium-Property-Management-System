<?php
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
include 'db.php';
$tables = ['users','rent','electricity','payments','payment_notifications','login_logs','payment_reminders'];
foreach($tables as $t) {
    $r = mysqli_query($conn, "DESCRIBE $t");
    if($r){
        echo "--- $t ---\n";
        while($row = mysqli_fetch_assoc($r)) {
            echo $row['Field'] . ' ' . $row['Type'] . "\n";
        }
    } else {
        echo "--- $t (MISSING) ---\n";
    }
}
