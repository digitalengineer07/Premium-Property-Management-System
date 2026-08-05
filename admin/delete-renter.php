<?php
// admin/delete-renter.php
require_once "../db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security validation failed']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// 1. Protect Accounting Integrity: Check for any financial records
$p_q = mysqli_query($conn, "SELECT id FROM payments WHERE user_id = $id LIMIT 1");
if ($p_q && mysqli_num_rows($p_q) > 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete a resident with existing financial records to protect accounting integrity. Please use the "Move Out" button instead.']);
    exit;
}

// Start transaction for full cascaded purge
mysqli_begin_transaction($conn);

try {
    // 1. Delete bills
    mysqli_query($conn, "DELETE FROM electricity WHERE user_id = $id");
    mysqli_query($conn, "DELETE FROM rent WHERE user_id = $id");
    
    // 2. Delete non-financial traces (since financial is confirmed 0)
    mysqli_query($conn, "DELETE FROM payment_notifications WHERE user_id = $id");
    mysqli_query($conn, "DELETE FROM app_notifications WHERE user_id = $id");
    
    // Fetch and delete query attachments before deleting DB rows
    $query_q = mysqli_query($conn, "SELECT attachment FROM queries WHERE user_id = $id AND attachment IS NOT NULL AND attachment != ''");
    while ($q_row = mysqli_fetch_assoc($query_q)) {
        if (!empty($q_row['attachment']) && file_exists("../" . $q_row['attachment'])) {
            unlink("../" . $q_row['attachment']);
        }
    }
    mysqli_query($conn, "DELETE FROM queries WHERE user_id = $id");

    // Fetch and delete uploaded documents before deleting DB rows
    $doc_q = mysqli_query($conn, "SELECT file_path FROM documents WHERE user_id = $id");
    while ($doc = mysqli_fetch_assoc($doc_q)) {
        if (!empty($doc['file_path']) && file_exists("../" . $doc['file_path'])) {
            unlink("../" . $doc['file_path']);
        }
    }
    mysqli_query($conn, "DELETE FROM documents WHERE user_id = $id");

    // 3. Delete user profile and their profile picture
    $user_q = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = $id");
    if ($user_q && $u_row = mysqli_fetch_assoc($user_q)) {
        if (!empty($u_row['profile_pic']) && file_exists("../" . $u_row['profile_pic'])) {
            unlink("../" . $u_row['profile_pic']);
        }
    }
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");

    mysqli_commit($conn);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>
