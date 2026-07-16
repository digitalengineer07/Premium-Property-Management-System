<?php
require 'db.php';
$stmt = mysqli_prepare($conn, "SELECT p.*, a.username as admin_name FROM payments p LEFT JOIN admin a ON p.recorded_by = a.id WHERE p.user_id = 1 ORDER BY p.id DESC");
if (!$stmt) {
    echo "SQL ERROR: " . mysqli_error($conn);
} else {
    echo "SQL OK";
}
?>
