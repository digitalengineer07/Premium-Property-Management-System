<?php
// admin/delete-doc.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($user_id <= 0 || !in_array($type, ['aadhaar', 'agreement', 'electricity'])) {
    die("Invalid request.");
}

if ($type === 'aadhaar') {
    $stmt = mysqli_prepare($conn, "SELECT aadhaar_file FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($data && !empty($data['aadhaar_file'])) {
        $file_path = "../" . $data['aadhaar_file'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        $stmt2 = mysqli_prepare($conn, "UPDATE users SET aadhaar_file = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $user_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }
} elseif ($type === 'agreement') {
    $stmt = mysqli_prepare($conn, "SELECT agreement_document FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($data && !empty($data['agreement_document'])) {
        $rel_path = $data['agreement_document'];
        $file_path = (strpos($rel_path, 'uploads/') === 0) ? "../" . $rel_path : "../uploads/agreements/" . $rel_path;
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        $stmt2 = mysqli_prepare($conn, "UPDATE users SET agreement_document = NULL, agreement_upload_date = NULL, agreement_expiry_date = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $user_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }
} elseif ($type === 'electricity') {
    $stmt = mysqli_prepare($conn, "SELECT electricity_document FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($data && !empty($data['electricity_document'])) {
        $file_path = "../" . $data['electricity_document'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        $stmt2 = mysqli_prepare($conn, "UPDATE users SET electricity_document = NULL, electricity_upload_date = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $user_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }
}

header("Location: view-renter.php?id=" . $user_id . "&success=doc_deleted");
exit;
