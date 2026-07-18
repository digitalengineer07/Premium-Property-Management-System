<?php
require 'db.php';
mysqli_query($conn, "ALTER TABLE payment_notifications MODIFY bill_type VARCHAR(20) NOT NULL");
mysqli_query($conn, "ALTER TABLE payment_notifications ADD COLUMN month VARCHAR(50) DEFAULT NULL");
echo "DB Updated";
