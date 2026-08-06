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

// Removed accounting integrity block to allow admin to force-delete test records.

// Start transaction for full cascaded purge
mysqli_begin_transaction($conn);

function deleteIfExists($conn, $table, $id) {
    // Attempt delete, ignore errors if table doesn't exist
    try {
        mysqli_query($conn, "DELETE FROM `$table` WHERE user_id = $id");
    } catch (Throwable $e) {
        // Ignore missing table error
    }
}

try {
    // 1. Delete bills and payments
    deleteIfExists($conn, 'electricity', $id);
    deleteIfExists($conn, 'rent', $id);
    deleteIfExists($conn, 'payments', $id);
    deleteIfExists($conn, 'payment_requests', $id);
    deleteIfExists($conn, 'payment_reminders', $id);
    
    // 2. Delete non-financial traces
    deleteIfExists($conn, 'payment_notifications', $id);
    deleteIfExists($conn, 'app_notifications', $id);
    deleteIfExists($conn, 'welcome_logs', $id);
    
    // Fetch and delete query attachments before deleting DB rows
    try {
        $query_q = mysqli_query($conn, "SELECT attachment FROM queries WHERE user_id = $id AND attachment IS NOT NULL AND attachment != ''");
        if ($query_q) {
            while ($q_row = mysqli_fetch_assoc($query_q)) {
                if (!empty($q_row['attachment']) && file_exists("../" . $q_row['attachment'])) {
                    unlink("../" . $q_row['attachment']);
                }
            }
            deleteIfExists($conn, 'queries', $id);
        }
    } catch (Throwable $e) {}

    // Fetch and delete uploaded documents before deleting DB rows
    try {
        $doc_q = mysqli_query($conn, "SELECT file_path FROM documents WHERE user_id = $id");
        if ($doc_q) {
            while ($doc = mysqli_fetch_assoc($doc_q)) {
                if (!empty($doc['file_path']) && file_exists("../" . $doc['file_path'])) {
                    unlink("../" . $doc['file_path']);
                }
            }
            deleteIfExists($conn, 'documents', $id);
        }
    } catch (Throwable $e) {}

    // 3. Delete user profile and their profile picture
    $user_q = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = $id");
    if ($user_q && $u_row = mysqli_fetch_assoc($user_q)) {
        if (!empty($u_row['profile_pic']) && file_exists("../" . $u_row['profile_pic'])) {
            unlink("../" . $u_row['profile_pic']);
        }
    }
    
    if (!mysqli_query($conn, "DELETE FROM users WHERE id = $id")) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>
