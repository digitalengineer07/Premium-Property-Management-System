<?php
require 'db.php';
$q = mysqli_query($conn, 'DESCRIBE payments'); 
while ($r = mysqli_fetch_assoc($q)) { 
    echo $r['Field'] . ' '; 
}
?>
