<?php
// admin/add-rent.php - Unified SaaS UI
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

header('Location: bill-generator.php');
exit;
