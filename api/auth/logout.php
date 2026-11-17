<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';
$token = api_request_token();
$hash = hash('sha256', $token);
$stmt = mysqli_prepare($conn, 'UPDATE api_sessions SET revoked_at=NOW() WHERE token_hash=?');
mysqli_stmt_bind_param($stmt, 's', $hash);
mysqli_stmt_execute($stmt);
header('Cache-Control: no-store');
echo json_encode(['status'=>'success']);
