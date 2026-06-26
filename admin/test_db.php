<?php
session_start();
$_SESSION['admin'] = 'a';
$_GET['endpoint'] = 'kpi';
require 'api_reports_saas.php';
