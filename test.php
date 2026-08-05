<?php session_start(); $_SESSION['user_id']=1; ob_start(); require 'renter/profile.php'; file_put_contents('test_profile.html', ob_get_clean()); echo 'Done';
