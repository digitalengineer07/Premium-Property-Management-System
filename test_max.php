<?php
require 'c:/xampp/htdocs/renter-system/config.php';
\ = mysqli_connect('localhost', 'root', '', 'renter_system');
\ = mysqli_query(\, "SELECT MAX(STR_TO_DATE(CONCAT('01 ', month), '%d %M %Y')) as max_date FROM electricity");
\ = mysqli_fetch_assoc(\);
echo \['max_date'];
