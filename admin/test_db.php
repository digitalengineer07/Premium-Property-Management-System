<?php
require '../db.php';
$q = "
    SELECT u.id, u.name, u.room_no, u.photo,
           IFNULL((SELECT SUM(rent_amount + maintenance + extra_charges + total_amount) FROM electricity WHERE user_id = u.id AND status='Paid'), 0) +
           IFNULL((SELECT SUM(rent_amount) FROM rent WHERE user_id = u.id AND status='Paid'), 0) as total_paid,
           IFNULL((SELECT SUM(total_amount) FROM electricity WHERE user_id = u.id AND status='Due'), 0) +
           IFNULL((SELECT SUM(rent_amount) FROM rent WHERE user_id = u.id AND status='Due'), 0) as total_due
    FROM users u
    WHERE u.status='active'
    ORDER BY total_paid DESC LIMIT 4
";
$res = mysqli_query($conn, $q);
if(!$res) echo mysqli_error($conn);
