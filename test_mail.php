<?php
// Test SMTP Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/admin/utils_mailer.php";

$mail = get_phpmailer_instance();
if (!$mail) {
    echo "Failed to get PHPMailer instance.\n";
    exit;
}

// Enable verbose debug output
$mail->SMTPDebug = 2; // SMTP::DEBUG_SERVER
$mail->Debugoutput = 'html';

try {
    $mail->addAddress('test@example.com', 'Test User'); // Using a dummy email just to test connection
    $mail->Subject = 'SMTP Test';
    $mail->Body = 'This is a test email to verify SMTP settings.';
    
    if ($mail->send()) {
        echo "Email sent successfully.\n";
    } else {
        echo "Email sending failed.\n";
    }
} catch (Exception $e) {
    echo "Exception occurred: " . $e->getMessage() . "\n";
    echo "Mailer Error: " . $mail->ErrorInfo . "\n";
}
