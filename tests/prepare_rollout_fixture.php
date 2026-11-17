<?php
if (PHP_SAPI !== 'cli') exit;
putenv('RMS_DB_NAME=renter_rollout_test');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../admin/allocate_payment.php';
if (mysqli_fetch_row(accounting_query($conn,'SELECT DATABASE()'))[0] !== 'renter_rollout_test') throw new RuntimeException('Test target mismatch.');
$suffix = bin2hex(random_bytes(5));
$password = bin2hex(random_bytes(20));
$hash = password_hash($password,PASSWORD_DEFAULT);
$admin = 'rollout_admin_' . $suffix;
$stmt=mysqli_prepare($conn,'INSERT INTO admin(username,password) VALUES(?,?)');
mysqli_stmt_bind_param($stmt,'ss',$admin,$hash); mysqli_stmt_execute($stmt);
$residents=[];
foreach (['advance'=>'20000','arrears'=>'10000'] as $mode=>$amount) {
    $username='rollout_'.$mode.'_'.$suffix; $room=$mode==='advance' ? 9901 : 9902;
    $stmt=mysqli_prepare($conn,"INSERT INTO users(username,password,name,room_no,fixed_rent,fixed_maintenance,security_deposit,joining_date,base_reading,status,must_change_password) VALUES(?,?,'Rollout Test',?,10000,500,10000,'2026-01-01',0,'active',0)");
    mysqli_stmt_bind_param($stmt,'ssi',$username,$hash,$room); mysqli_stmt_execute($stmt);
    $id=mysqli_insert_id($conn);
    accounting_onboarding($conn,$id,$amount,'Cash','fixture-'.$username,'fixture-'.$username);
    $residents[$mode]=['id'=>$id,'username'=>$username];
}
file_put_contents(__DIR__.'/../.rollout-backups/http-fixture.json',json_encode(['admin'=>$admin,'password'=>$password,'residents'=>$residents]));
echo "Synthetic HTTP fixtures created only in renter_rollout_test.\n";
