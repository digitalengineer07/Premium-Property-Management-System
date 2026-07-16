<?php
$pic = "uploads/profiles/test.jpg";
var_dump(file_exists("../" . $pic));
var_dump(file_exists(__DIR__ . "/../" . $pic));
?>
