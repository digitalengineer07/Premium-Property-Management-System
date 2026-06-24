<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $move_out_date = isset($_POST['move_out_date']) ? mysqli_real_escape_string($conn, $_POST['move_out_date']) : '';

    if ($user_id <= 0 || empty($move_out_date)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data provided']);
        exit;
    }

    // Verify user exists and is currently active
    $check_q = mysqli_query($conn, "SELECT id, status FROM users WHERE id = $user_id");
    if (mysqli_num_rows($check_q) === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    $user_data = mysqli_fetch_assoc($check_q);
    if ($user_data['status'] === 'moved_out') {
        echo json_encode(['success' => false, 'error' => 'User is already moved out']);
        exit;
    }

    // Update the user to moved_out status
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'moved_out', move_out_date = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $move_out_date, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Renter successfully moved out. Their history has been preserved.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
