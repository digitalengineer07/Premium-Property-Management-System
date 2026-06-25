<?php
// admin/manage-recharges.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Ensure table exists
$createTable = "CREATE TABLE IF NOT EXISTS meter_recharges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10, 2) NOT NULL,
    recharge_date DATE NOT NULL,
    recharge_time TIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

$success = "";
$error = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM meter_recharges WHERE id = $id");
    $success = "Recharge record deleted.";
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recharge'])) {
    $amount = (float)$_POST['amount'];
    $date = $_POST['recharge_date'];
    $time = $_POST['recharge_time'];
    $notes = trim($_POST['notes'] ?? '');

    if ($amount <= 0 || !$date || !$time) {
        $error = "Please provide valid amount, date and time.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO meter_recharges (amount, recharge_date, recharge_time, notes) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "dsss", $amount, $date, $time, $notes);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Recharge added successfully!";
        } else {
</html>
