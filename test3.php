<?php
require 'db.php';
$q = mysqli_query($conn, 'DESCRIBE users');
while($r = mysqli_fetch_assoc($q)) echo $r['Field'] . ' - ' . $r['Type'] . "\n";
