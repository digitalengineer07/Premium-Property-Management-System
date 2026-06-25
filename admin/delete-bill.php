<?php
// admin/delete-bill.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Check if electricity bill is paid
    $e_q = mysqli_query($conn, "SELECT status FROM electricity WHERE id = $id");
    if ($e_q && $row = mysqli_fetch_assoc($e_q)) {
        if ($row['status'] == 'Paid') {
            die("<script>alert('Error: Cannot delete a fully Paid electricity bill to protect accounting integrity.'); window.history.back();</script>");
        }
    }

    // Check if rent bill is paid
    $r_q = mysqli_query($conn, "SELECT status FROM rent WHERE id = $id");
    if ($r_q && $row = mysqli_fetch_assoc($r_q)) {
        if ($row['status'] == 'Paid') {
            die("<script>alert('Error: Cannot delete a fully Paid rent bill to protect accounting integrity.'); window.history.back();</script>");
        }
    }

    // Delete associated payments first
    $stmt1 = mysqli_prepare($conn, "DELETE FROM payments WHERE bill_type = 'electricity' AND bill_id = ?");
    mysqli_stmt_bind_param($stmt1, "i", $id);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    // Delete the bill
    $stmt2 = mysqli_prepare($conn, "DELETE FROM electricity WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    // Also try to delete from rent just in case they are completely separate (legacy)
    $stmt3 = mysqli_prepare($conn, "DELETE FROM payments WHERE bill_type = 'rent' AND bill_id = ?");
    mysqli_stmt_bind_param($stmt3, "i", $id);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);

    $stmt4 = mysqli_prepare($conn, "DELETE FROM rent WHERE id = ?");
    mysqli_stmt_bind_param($stmt4, "i", $id);
    mysqli_stmt_execute($stmt4);
    mysqli_stmt_close($stmt4);
}

// Redirect back to the previous page
$referrer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: " . $referrer);
exit;
