<?php
// includes/mailer.php — PHPMailer Helper for Tomorrow Vote OTP Verification

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends a 6-digit OTP to the voter's registered email address with auto-failover.
 * Tries Port 465 (SMTPS) first, then falls back to Port 587 (STARTTLS) for resilience on cloud platforms like Railway.
 *
 * @param string $recipientEmail
 * @param string $recipientName
 * @param string $otpCode
 * @return bool
 * @throws Exception
 */
function sendVoterLoginOtp($recipientEmail, $recipientName, $otpCode) {
    // Port configurations to try: Port 465 (SSL/SMTPS) first, followed by Port 587 (STARTTLS)
    $attempts = [
        ['port' => (int)SMTP_PORT, 'secure' => (SMTP_PORT == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS)],
        ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS],
        ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS]
    ];

    // Deduplicate attempts
    $uniqueAttempts = [];
    foreach ($attempts as $att) {
        $key = $att['port'] . '-' . $att['secure'];
        if (!isset($uniqueAttempts[$key])) {
            $uniqueAttempts[$key] = $att;
        }
    }

    $lastError = '';

    foreach ($uniqueAttempts as $att) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = $att['secure'];
            $mail->Port       = $att['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 8; // Fast timeout for immediate failover

            // Relax strict SSL peer validation for cloud containers and self-contained environments
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "{$otpCode} is your Tomorrow Vote login verification code";

            $escapedName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
            $escapedOtp = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');

            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
                    .email-wrapper { max-width: 560px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); }
                    .email-header { background: linear-gradient(135deg, #0b224d 0%, #1059d6 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
                    .email-header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
                    .email-header p { margin: 6px 0 0 0; font-size: 13px; color: #bfdbfe; font-weight: 500; }
                    .email-body { padding: 32px 28px; color: #1e293b; line-height: 1.6; }
                    .greeting { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
                    .text { font-size: 14px; color: #475569; margin-bottom: 24px; }
                    .otp-box { background: #f1f5f9; border: 2px dashed #93c5fd; border-radius: 12px; text-align: center; padding: 20px; margin-bottom: 24px; }
                    .otp-code { font-family: 'Courier New', Courier, monospace; font-size: 38px; font-weight: 900; letter-spacing: 8px; color: #1059d6; }
                    .otp-hint { font-size: 12px; font-weight: 600; color: #64748b; margin-top: 8px; }
                    .warning-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #991b1b; margin-bottom: 24px; }
                    .email-footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 28px; text-align: center; font-size: 12px; color: #94a3b8; }
                </style>
            </head>
            <body>
                <div class='email-wrapper'>
                    <div class='email-header'>
                        <h1>Tomorrow Vote</h1>
                        <p>Secure Student Council Online Voting System</p>
                    </div>
                    <div class='email-body'>
                        <div class='greeting'>Hello, {$escapedName}!</div>
                        <div class='text'>
                            You are signing in to your institutional student voter account on Tomorrow Vote. Use the verification code below to verify your login:
                        </div>
                        <div class='otp-box'>
                            <div class='otp-code'>{$escapedOtp}</div>
                            <div class='otp-hint'>Valid for 10 minutes. Do not share this code with anyone.</div>
                        </div>
                        <div class='warning-box'>
                            <strong>Security Notice:</strong> If you did not attempt to sign in to your Tomorrow Vote account, someone may be attempting to access your student voter credentials. Please notify your election administrator immediately.
                        </div>
                    </div>
                    <div class='email-footer'>
                        &copy; 2026 Tomorrow Vote Institutional Student Council. All rights reserved.
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->AltBody = "Hello {$recipientName},\n\nYour Tomorrow Vote login verification code is: {$otpCode}\n\nThis code will expire in 10 minutes.\nIf you did not request this code, please contact your election administrator.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            $lastError = $mail->ErrorInfo ?: $e->getMessage();
            error_log("PHPMailer failed on port {$att['port']}: " . $lastError);
        }
    }

    throw new Exception("Unable to connect to mail server: " . $lastError);
}
