<?php
require 'db.php';
// Check current advance and security deposit
$res = mysqli_query($conn, 'SELECT id, name, advance_payment, security_deposit FROM users');
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
