<?php
require 'db.php';
print_r(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT * FROM rent LIMIT 1')));
print_r(mysqli_fetch_assoc(mysqli_query($conn, 'SELECT * FROM electricity LIMIT 1')));
