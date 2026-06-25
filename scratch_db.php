<?php
require_once "db.php";
$res = mysqli_query($conn, "SHOW COLUMNS FROM login_logs LIKE 'logout_time'");
if (mysqli_num_rows($res) == 0) {
    mysqli_query($conn, "ALTER TABLE login_logs ADD COLUMN logout_time DATETIME NULL");
    echo "Added logout_time\n";
} else {
    echo "logout_time already exists\n";
}
