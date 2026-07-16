<?php
require 'db.php';
$stmt = mysqli_prepare($conn, "UPDATE users SET name='Vijay Jii', phone='1234567890', email='', whatsapp='', room_no='', about='', profile_pic='uploads/profiles/test.jpg', dob=NULL, gender=NULL, address=NULL, emergency_contact_name=NULL, emergency_contact_relation=NULL, emergency_contact_phone=NULL, emergency_contact_address=NULL WHERE id=1");
if (!$stmt) {
    echo "Prepare failed: " . mysqli_error($conn) . "\n";
} else {
    $res = mysqli_stmt_execute($stmt);
    if (!$res) {
        echo "Execute failed: " . mysqli_stmt_error($stmt) . "\n";
    } else {
        echo "Execute Success\n";
    }
}
?>
