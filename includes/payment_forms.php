<?php
function payment_form_nonce(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $nonce = bin2hex(random_bytes(16));
    $_SESSION['payment_forms'][$nonce] = time();
    if (count($_SESSION['payment_forms']) > 200) $_SESSION['payment_forms'] = array_slice($_SESSION['payment_forms'], -200, null, true);
    return $nonce;
}

function payment_form_reference(string $prefix): string {
    $nonce = $_POST['payment_request_id'] ?? '';
    if (!preg_match('/^[a-f0-9]{32}$/D', $nonce) || empty($_SESSION['payment_forms'][$nonce])) throw new RuntimeException('Payment form expired. Reload the page.');
    return $prefix . $nonce;
}
