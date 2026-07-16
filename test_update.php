<?php
require 'c:/xampp/htdocs/renter-system/db.php';
$_POST = [
    'save_profile' => 1,
    'name' => 'Vijay',
    'phone' => '1234567890',
    'email' => 'v@v.com',
    'whatsapp' => '1234567890',
    'room_no' => '0',
    'about' => 'me',
    'dob' => '2000-01-01',
    'gender' => 'Male',
    'address' => 'Addr',
    'emergency_contact_name' => 'Niiku Test',
    'emergency_contact_relation' => 'Brother',
    'emergency_contact_phone' => '9999999999',
    'emergency_contact_address' => 'Patna'
];
$user_id = 1;

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$room_no = trim($_POST['room_no'] ?? '');
$about = trim($_POST['about'] ?? '');
$dob = empty($_POST['dob']) ? null : $_POST['dob'];
$gender = empty($_POST['gender']) ? null : $_POST['gender'];
$address = empty($_POST['address']) ? null : $_POST['address'];
$emg_name = empty($_POST['emergency_contact_name']) ? null : $_POST['emergency_contact_name'];
$emg_rel = empty($_POST['emergency_contact_relation']) ? null : $_POST['emergency_contact_relation'];
$emg_phone = empty($_POST['emergency_contact_phone']) ? null : $_POST['emergency_contact_phone'];
$emg_addr = empty($_POST['emergency_contact_address']) ? null : $_POST['emergency_contact_address'];

$stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, email=?, whatsapp=?, room_no=?, about=?, dob=?, gender=?, address=?, emergency_contact_name=?, emergency_contact_relation=?, emergency_contact_phone=?, emergency_contact_address=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "sssssssssssssi", $name, $phone, $email, $whatsapp, $room_no, $about, $dob, $gender, $address, $emg_name, $emg_rel, $emg_phone, $emg_addr, $user_id);
mysqli_stmt_execute($stmt);
echo mysqli_error($conn) . "\n";
echo "Affected: " . mysqli_stmt_affected_rows($stmt) . "\n";
?>
