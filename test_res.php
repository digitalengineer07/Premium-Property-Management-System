<?php
require 'c:/xampp/htdocs/renter-system/config.php';
\ = 'localhost';
\ = 'root';
\ = '';
\ = 'renter_system';
\ = mysqli_connect(\, \, \, \);

\ = 1;
\ = mysqli_query(\, "SELECT 
    IFNULL(SUM(amount),0) as elec_total, 
    IFNULL(SUM(rent_amount),0) as rent_total,
    IFNULL(SUM(maintenance),0) as maint_total,
    IFNULL(SUM(dues),0) as other_dues_total
    FROM electricity WHERE user_id = \ AND status != 'Paid'");
\ = mysqli_fetch_assoc(\);
print_r(\);
?>
