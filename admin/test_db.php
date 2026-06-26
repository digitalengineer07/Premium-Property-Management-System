<?php
session_start();
$_SESSION['admin'] = 'a';
$_GET['endpoint'] = 'resident_performance';
require 'api_reports_saas.php';
