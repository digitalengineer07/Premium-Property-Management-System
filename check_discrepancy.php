<?php
require 'db.php';
$q = mysqli_query($conn, "SELECT id, name, room_no, pending_adjustment FROM users WHERE pending_adjustment != 0");
if (mysqli_num_rows($q) == 0) {
    echo "NO_ISSUES";
} else {
    echo "ISSUES_FOUND:\n";
    while ($r = mysqli_fetch_assoc($q)) {
        echo "Room: {$r['room_no']}, Name: {$r['name']}, Pending Adj: {$r['pending_adjustment']}\n";
    }
}
