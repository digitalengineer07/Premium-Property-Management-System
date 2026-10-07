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
    if ($type === 'rent') {
        // Check if rent bill is paid
        $r_q = mysqli_query($conn, "SELECT status FROM rent WHERE id = $id");
        if ($r_q && $row = mysqli_fetch_assoc($r_q)) {
            if ($row['status'] == 'Paid' || $row['status'] == 'Partial') {
                die("<script>alert('Error: Cannot delete a bill that has associated payments (Paid or Partial) to protect accounting integrity. Please reverse the payments first.'); window.history.back();</script>");
            }
        }

        // Delete associated payments
        $stmt1 = mysqli_prepare($conn, "DELETE FROM payments WHERE bill_type = 'rent' AND bill_id = ?");
        mysqli_stmt_bind_param($stmt1, "i", $id);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        // Delete the bill
        $stmt2 = mysqli_prepare($conn, "DELETE FROM rent WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

    } else {
        // Electricity / Combined bill
        $e_q = mysqli_query($conn, "SELECT status, elec_status, rent_status FROM electricity WHERE id = $id");
        if ($e_q && $row = mysqli_fetch_assoc($e_q)) {
            if ($row['status'] == 'Paid' || $row['status'] == 'Partial' || $row['elec_status'] == 'Paid' || $row['elec_status'] == 'Partial' || $row['rent_status'] == 'Paid' || $row['rent_status'] == 'Partial') {
                die("<script>alert('Error: Cannot delete a bill that has associated payments (Paid or Partial) to protect accounting integrity. Please reverse the payments first.'); window.history.back();</script>");
            }
        }

        // Delete associated electricity and elec_rent payments
        $stmt1 = mysqli_prepare($conn, "DELETE FROM payments WHERE bill_type IN ('electricity', 'elec_rent') AND bill_id = ?");
        mysqli_stmt_bind_param($stmt1, "i", $id);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        // Delete the bill
        $stmt2 = mysqli_prepare($conn, "DELETE FROM electricity WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }
}

// Redirect back to the previous page
$referrer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: " . $referrer);
exit;
