<?php
// admin/utils_mailer.php
require_once __DIR__ . "/../db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

/**
 * Configure and return a new PHPMailer instance using Hostinger SMTP
 */
function get_phpmailer_instance() {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        // Load credentials from ignored file to prevent leaks
        require_once __DIR__ . '/../smtp_credentials.php';

        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $SMTP_USER;
        $mail->Password   = $SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Set default sender
        $mail->setFrom($SMTP_USER, 'Madhav Kunj Administration');
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer configuration failed: {$mail->ErrorInfo}");
        return null;
    }
}

/**
 * Sends a notification when a new bill is generated.
 */
function send_new_bill_notification($to_email, $renter_name, $bill_month, $amount_due) {
    if (empty($to_email)) return false;

    $mail = get_phpmailer_instance();
    if (!$mail) return false;

    try {
        $mail->addAddress($to_email, $renter_name);
        $mail->Subject = "New Bill Generated - $bill_month - Madhav Kunj";
        $mail->isHTML(true);

        $message = "
        <html>
        <head>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
                .header { background: #624BFF; color: white; padding: 40px 20px; text-align: center; }
                .content { padding: 40px; }
                .footer { text-align: center; color: #64748b; font-size: 12px; padding: 30px; background: #f8fafc; }
                .btn { display: inline-block; padding: 14px 28px; background: #624BFF; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 700; margin-top: 25px; }
                .bill-box { background: #f1f5f9; padding: 20px; border-radius: 12px; margin: 25px 0; border-left: 4px solid #624BFF; }
                .total { font-size: 24px; font-weight: 800; color: #ef4444; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0; font-size: 28px;'>Madhav Kunj</h1>
                    <p style='margin-top:10px; opacity: 0.9;'>New Bill Generated</p>
                </div>
                <div class='content'>
                    <h2 style='margin-top:0;'>Hi " . htmlspecialchars($renter_name) . ",</h2>
                    <p>Your rent and electricity bill for <strong>" . htmlspecialchars($bill_month) . "</strong> has been generated.</p>
                    
                    <div class='bill-box'>
                        <p style='margin-bottom:10px;'>Total Amount Due:</p>
                        <div class='total'>₹" . number_format($amount_due, 2) . "</div>
                    </div>
                    
                    <p style='margin-top:30px;'>You can view the detailed breakdown and pay your bill from your resident dashboard.</p>
                    
                    <center><a href='https://succorkart.in/login.php' class='btn'>Log In to Dashboard</a></center>
                </div>
                <div class='footer'>
                    <p><strong>Madhav Kunj Administration</strong></p>
                    <p>&copy; " . date('Y') . " All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $message;
        $mail->AltBody = "Hi $renter_name, your bill for $bill_month has been generated. Total due: ₹$amount_due. Please login to your dashboard to view.";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Failed to send new bill email: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends a professional HTML email reminder to a renter.
 */
function send_payment_reminder_email($to_email, $renter_name, $overdue_bills, $amount_due, $pdf_file_path = null) {
    if (empty($to_email)) return false;

    $subject = "Action Required: Payment Reminder for Madhav Kunj";
    
    // HTML Template
    $message = "
    <html>
    <head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { background: #624BFF; color: white; padding: 40px 20px; text-align: center; }
            .content { padding: 40px; }
            .footer { text-align: center; color: #64748b; font-size: 12px; padding: 30px; background: #f8fafc; }
            .btn { display: inline-block; padding: 14px 28px; background: #624BFF; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 700; margin-top: 25px; }
            .bill-list { background: #f1f5f9; padding: 20px; border-radius: 12px; margin: 25px 0; border-left: 4px solid #624BFF; }
            .total { font-size: 24px; font-weight: 800; color: #ef4444; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 28px;'>Madhav Kunj</h1>
                <p style='margin-top:10px; opacity: 0.9;'>Payment Notification System</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($renter_name) . ",</h2>
                <p>This is a friendly reminder that you have outstanding payments on your account. To maintain accurate accounting and service continuity, please clear your dues.</p>
                
                <div class='bill-list'>
                    <p style='margin-top:0; font-weight:700; color:#624BFF;'>Pending Bills:</p>
                    " . implode("<br>", array_map(function($b) { return "• " . htmlspecialchars($b); }, $overdue_bills)) . "
                </div>
                
                <p style='margin-bottom:10px;'>Total Amount Due:</p>
                <div class='total'>₹" . number_format($amount_due, 2) . "</div>
                
                <p style='margin-top:30px;'>Please clear these payments by visiting your dashboard. If you have already paid, please ignore this email or upload your receipt for verification.</p>
                
                <center><a href='https://succorkart.in/login.php' class='btn'>Log In to Dashboard</a></center>
            </div>
            <div class='footer'>
                <p><strong>Madhav Kunj</strong></p>
                <p>&copy; " . date('Y') . " All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $boundary = md5(time());

    $headers  = "From: Madhav Kunj <madhavkunj@succorkart.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";

    // Build the email body
    $body = "--" . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=iso-8859-1\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n\r\n";

    // Handle PDF Attachment if it exists
    if ($pdf_file_path && file_exists(__DIR__ . '/../' . $pdf_file_path)) {
        $file_content = file_get_contents(__DIR__ . '/../' . $pdf_file_path);
        $encoded_file = chunk_split(base64_encode($file_content));
        $filename = basename($pdf_file_path);

        $body .= "--" . $boundary . "\r\n";
        $body .= "Content-Type: application/pdf; name=\"" . $filename . "\"\r\n";
        $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= $encoded_file . "\r\n\r\n";
    }

    $body .= "--" . $boundary . "--";

    return @mail($to_email, $subject, $body, $headers);
}

/**
 * Logs a reminder event in the database.
 */
function log_reminder($conn, $user_id, $bill_id, $bill_type, $month, $type = 'Auto', $status = 'Sent') {
    $stmt = mysqli_prepare($conn, "INSERT INTO payment_reminders (user_id, bill_id, bill_type, month, remind_type, status) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iissss", $user_id, $bill_id, $bill_type, $month, $type, $status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Sends a password reset OTP email.
 */
function send_password_reset_otp($to_email, $renter_name, $otp) {
    if (empty($to_email)) return false;

    $subject = "Password Reset Verification Code - Madhav Kunj";
    
    $message = "
    <html>
    <head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { background: #624BFF; color: white; padding: 30px 20px; text-align: center; }
            .content { padding: 40px; text-align: center; }
            .footer { text-align: center; color: #64748b; font-size: 12px; padding: 30px; background: #f8fafc; }
            .otp-box { background: #f8fafc; padding: 20px; border-radius: 12px; margin: 25px 0; border: 2px dashed #cbd5e1; font-size: 32px; font-weight: 800; letter-spacing: 5px; color: #624BFF; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 24px;'>Madhav Kunj</h1>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($renter_name) . ",</h2>
                <p>We received a request to reset your password. Use the verification code below to proceed.</p>
                
                <div class='otp-box'>" . htmlspecialchars($otp) . "</div>
                
                <p style='margin-bottom:10px; font-size: 14px; color: #64748b;'>This code expires in 15 minutes. If you didn't request a password reset, you can safely ignore this email.</p>
            </div>
            <div class='footer'>
                <p><strong>Madhav Kunj</strong></p>
                <p>&copy; " . date('Y') . " All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: Madhav Kunj <madhavkunj@succorkart.in>\r\n";

    return @mail($to_email, $subject, $message, $headers);
}

/**
 * Sends a payment receipt email with optional PDF.
 */
function send_payment_receipt_email($to_email, $renter_name, $details, $amount_paid, $pdf_file_path = null) {
    if (empty($to_email)) return false;

    $subject = "Payment Receipt - Madhav Kunj";
    
    // HTML Template
    $message = "
    <html>
    <head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { background: #10B981; color: white; padding: 40px 20px; text-align: center; }
            .content { padding: 40px; }
            .footer { text-align: center; color: #64748b; font-size: 12px; padding: 30px; background: #f8fafc; }
            .btn { display: inline-block; padding: 14px 28px; background: #10B981; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 700; margin-top: 25px; }
            .bill-list { background: #f1f5f9; padding: 20px; border-radius: 12px; margin: 25px 0; border-left: 4px solid #10B981; }
            .total { font-size: 24px; font-weight: 800; color: #10B981; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 28px;'>Madhav Kunj</h1>
                <p style='margin-top:10px; opacity: 0.9;'>Payment Receipt</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($renter_name) . ",</h2>
                <p>Thank you! Your payment has been successfully received and verified. Your account balance has been updated.</p>
                
                <div class='bill-list'>
                    <p style='margin-top:0; font-weight:700; color:#10B981;'>Payment Details:</p>
                    " . implode("<br>", array_map(function($b) { return "• " . htmlspecialchars($b); }, $details)) . "
                </div>
                
                <p style='margin-bottom:10px;'>Amount Paid:</p>
                <div class='total'>₹" . number_format($amount_paid, 2) . "</div>
                
                <p style='margin-top:30px;'>Thank you for your prompt payment!</p>
                
                <center><a href='https://succorkart.in/login.php' class='btn'>Log In to Dashboard</a></center>
            </div>
            <div class='footer'>
                <p><strong>Madhav Kunj</strong></p>
                <p>&copy; " . date('Y') . " All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $boundary = md5(time());

    $headers  = "From: Madhav Kunj <madhavkunj@succorkart.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";

    // Build the email body
    $body = "--" . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=iso-8859-1\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n\r\n";

    // Handle PDF Attachment if it exists
    if ($pdf_file_path && file_exists(__DIR__ . '/../' . $pdf_file_path)) {
        $file_content = file_get_contents(__DIR__ . '/../' . $pdf_file_path);
        $encoded_file = chunk_split(base64_encode($file_content));
        $filename = basename($pdf_file_path);

        $body .= "--" . $boundary . "\r\n";
        $body .= "Content-Type: application/pdf; name=\"" . $filename . "\"\r\n";
        $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= $encoded_file . "\r\n\r\n";
    }

    $body .= "--" . $boundary . "--";

    return @mail($to_email, $subject, $body, $headers);
}

/**
 * Sends an official announcement email to a renter.
 */
function send_announcement_email($to_email, $renter_name, $announcement_title, $announcement_msg, $priority) {
    if (empty($to_email)) return false;

    $subject = "Official Announcement: " . $announcement_title;
    
    $priorityColor = '#3b82f6'; // Normal (Blue)
    if ($priority == 'High') $priorityColor = '#f59e0b'; // Amber
    if ($priority == 'Urgent') $priorityColor = '#ef4444'; // Red
    
    // HTML Template
    $message = "
    <html>
    <head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { background: {$priorityColor}; color: white; padding: 40px 20px; text-align: center; }
            .badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px; text-transform: uppercase; font-weight: 700; display: inline-block; margin-bottom: 15px; letter-spacing: 1px; }
            .content { padding: 40px; }
            .footer { text-align: center; color: #64748b; font-size: 12px; padding: 30px; background: #f8fafc; }
            .btn { display: inline-block; padding: 14px 28px; background: {$priorityColor}; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 700; margin-top: 25px; }
            .announcement-box { background: #f8fafc; padding: 25px; border-radius: 12px; margin: 25px 0; border-left: 4px solid {$priorityColor}; color: #334155; font-size: 15px; white-space: pre-wrap; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <span class='badge'>{$priority} Priority</span>
                <h1 style='margin:0; font-size: 24px; line-height: 1.3;'>{$announcement_title}</h1>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($renter_name) . ",</h2>
                <p>A new official announcement has been posted by the administration. Please read the message below carefully:</p>
                
                <div class='announcement-box'>" . htmlspecialchars($announcement_msg) . "</div>
                
                <p style='margin-top:30px;'>For more details, you can always visit your resident dashboard.</p>
                
                <center><a href='https://succorkart.in/login.php' class='btn'>Log In to Dashboard</a></center>
            </div>
            <div class='footer'>
                <p><strong>Madhav Kunj</strong></p>
                <p>This is an automated system announcement.</p>
                <p>&copy; " . date('Y') . " All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: Madhav Kunj <madhavkunj@succorkart.in>\r\n";

    return @mail($to_email, $subject, $message, $headers);
}

/**
 * Sends a welcome email to a new renter.
 */
function send_welcome_email($to_email, $renter_name) {
    if (empty($to_email)) return false;

    $subject = "Welcome to Madhav Kunj - Your Resident Dashboard";
    
    $message = "
    <html>
    <head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { background: #10B981; color: white; padding: 40px 20px; text-align: center; }
            .content { padding: 40px; }
            .footer { text-align: center; color: #64748b; font-size: 12px; padding: 30px; background: #f8fafc; }
            .btn { display: inline-block; padding: 14px 28px; background: #10B981; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 700; margin-top: 25px; }
            .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; margin: 25px 0; border-left: 4px solid #10B981; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 28px;'>Welcome to Madhav Kunj!</h1>
                <p style='margin-top:10px; opacity: 0.9;'>We are thrilled to have you here.</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($renter_name) . ",</h2>
                <p>Welcome to Madhav Kunj! We have created an account for you on our resident portal where you can manage your tenancy digitally.</p>
                
                <div class='info-box'>
                    <p style='margin-top:0; font-weight:700; color:#10B981;'>What you can do in your dashboard:</p>
                    • <strong>Check Rent & Bills:</strong> View your monthly rent and electricity statements.<br>
                    • <strong>Payment Status:</strong> Check your due amounts and payment history.<br>
                    • <strong>Notify Admin:</strong> Send payment receipts easily by submitting your UTR.<br>
                    • <strong>Support:</strong> Raise queries or reach out for help anytime.
                </div>
                
                <p style='margin-top:30px;'>Get started by logging into your dashboard. If you need any assistance, feel free to contact the management.</p>
                
                <center><a href='https://succorkart.in/login.php' class='btn'>Log In to Dashboard</a></center>
            </div>
            <div class='footer'>
                <p><strong>Madhav Kunj</strong></p>
                <p>&copy; " . date('Y') . " All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: Madhav Kunj <madhavkunj@succorkart.in>\r\n";

    return @mail($to_email, $subject, $message, $headers);
}

/**
 * Sends a thank you and farewell email to a renter upon moving out.
 */
function send_move_out_thank_you_email($to_email, $renter_name) {
    if (empty($to_email)) return false;

    $subject = "Thank You for Staying at Madhav Kunj!";
    
    $message = "
    <html>
    <head>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f1f5f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { background: #6366F1; color: white; padding: 40px 20px; text-align: center; }
            .content { padding: 40px; }
            .footer { text-align: center; color: #64748b; font-size: 12px; padding: 30px; background: #f8fafc; }
            .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; margin: 25px 0; border-left: 4px solid #6366F1; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 28px;'>Farewell from Madhav Kunj!</h1>
                <p style='margin-top:10px; opacity: 0.9;'>We hope you had a great stay.</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($renter_name) . ",</h2>
                <p>We are writing to confirm that your move-out process has been completed successfully in our system.</p>
                
                <div class='info-box'>
                    <p style='margin-top:0; color:#334155;'>Thank you for choosing Madhav Kunj as your home. We truly appreciate you as a resident and hope your time with us was comfortable and pleasant. We wish you the very best in your future endeavors!</p>
                </div>
                
                <p style='margin-top:30px;'>Please note that your access to the resident dashboard has now been archived. If you ever need past payment receipts or have any questions regarding your deposit or account, please feel free to reach out to the management directly.</p>
                
                <p>Safe travels and best wishes!</p>
            </div>
            <div class='footer'>
                <p><strong>Madhav Kunj Administration</strong></p>
                <p>&copy; " . date('Y') . " All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: Madhav Kunj <madhavkunj@succorkart.in>\r\n";

    return @mail($to_email, $subject, $message, $headers);
}
?>
