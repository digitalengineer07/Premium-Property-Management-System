<?php
// admin/delete-bill.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if (!verifyCsrfToken($_GET['csrf'] ?? '')) {
    die("<script>alert('Security validation failed. Access denied.'); window.history.back();</script>");
}

$type = $_GET['type'] ?? 'electricity';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    require_once __DIR__ . '/../includes/accounting_store.php';
    try {
        accounting_atomic($conn, function () use ($conn, $id, $type) {
            $table = $type === 'rent' ? 'rent' : 'electricity';
            $bill = mysqli_fetch_assoc(accounting_query($conn, "SELECT user_id FROM $table WHERE id=$id"));
            if (!$bill) throw new RuntimeException('Bill not found.');
            $user = (int)$bill['user_id'];
            accounting_query($conn, "SELECT id FROM users WHERE id=$user FOR UPDATE");
            $types = $table === 'rent' ? "'rent'" : "'electricity','elec_rent'";
            if (mysqli_num_rows(accounting_query($conn, "SELECT id FROM payments WHERE bill_id=$id AND bill_type IN ($types) LIMIT 1"))) throw new RuntimeException('Cannot delete an invoice with payment history.');
            if ($table === 'electricity' && mysqli_num_rows(accounting_query($conn, "SELECT id FROM electricity WHERE user_id=$user AND id>$id LIMIT 1"))) throw new RuntimeException('Only the latest unpaid invoice can be removed; later meter readings depend on it.');
            accounting_query($conn, "DELETE FROM $table WHERE id=$id");
        });
    } catch (Throwable $failure) {
        http_response_code(409);
        exit(htmlspecialchars($failure->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}

// Redirect back to the previous page
$referrer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: " . $referrer);
exit;
