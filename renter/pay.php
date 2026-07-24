<?php
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Direct payment marking is disabled. All payments must be verified by an admin via payment notifications.']);
exit;
?>
