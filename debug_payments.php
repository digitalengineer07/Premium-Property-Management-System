<?php
require 'db.php';
$res = mysqli_query($conn, 'SELECT * FROM payments ORDER BY id DESC LIMIT 5');
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
