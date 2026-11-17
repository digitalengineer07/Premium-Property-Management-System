<?php
// Opaque bearer sessions. Only a hash is stored; never trust client identity claims.
function api_issue_token($conn, int $id, string $role, string $passwordHash): string {
    if (!in_array($role, ['admin','resident'], true)) throw new InvalidArgumentException('Invalid role.');
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $fingerprint = hash('sha256', $passwordHash);
    $stmt = mysqli_prepare($conn, 'INSERT INTO api_sessions(token_hash,account_id,role,password_fingerprint,expires_at) VALUES(?,?,?,?,DATE_ADD(NOW(),INTERVAL 30 DAY))');
    if (!$stmt) throw new RuntimeException('Authentication migration required.');
    mysqli_stmt_bind_param($stmt, 'siss', $hash, $id, $role, $fingerprint);
    if (!mysqli_stmt_execute($stmt)) throw new RuntimeException('Unable to establish session.');
    return $token;
}

function api_validate_token($conn, string $token, ?string $role = null): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
    $hash = hash('sha256', $token);
    $stmt = mysqli_prepare($conn, 'SELECT account_id,role,password_fingerprint FROM api_sessions WHERE token_hash=? AND expires_at>NOW() AND revoked_at IS NULL');
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    $session = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$session || ($role !== null && $session['role'] !== $role)) return null;
    $table = $session['role'] === 'admin' ? 'admin' : 'users';
    $id = (int)$session['account_id'];
    $result = mysqli_query($conn, "SELECT * FROM $table WHERE id=$id");
    $account = $result ? mysqli_fetch_assoc($result) : null;
    if (!$account || !hash_equals($session['password_fingerprint'], hash('sha256', $account['password']))) return null;
    if ($table === 'users' && ($account['status'] ?? 'active') !== 'active') return null;
    return ['id'=>$id, 'role'=>$session['role'], 'username'=>$account['username']];
}

function api_request_token(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if (!$header && function_exists('getallheaders')) {
        foreach (getallheaders() as $key=>$value) if (strcasecmp($key, 'Authorization') === 0) $header = $value;
    }
    if (preg_match('/^Bearer\s+(\S+)$/i', trim($header), $match)) return $match[1];
    // Compatibility for existing native clients; these are still server-validated tokens.
    return trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
}

function api_require_auth($conn, string $role): array {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
    $identity = api_validate_token($conn, api_request_token(), $role);
    if (!$identity) {
        http_response_code(401);
        echo json_encode(['status'=>'error','message'=>'Session expired or invalid. Please sign in again.']);
        exit;
    }
    return $identity;
}
