<?php
// includes/mailer.php — Mailer Helper for Tomorrow Vote OTP Verification

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Builds HTML email body for OTP verification.
 */
function buildOtpEmailHtml($recipientName, $otpCode) {
    $escapedName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $escapedOtp = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');

    return "
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
}

/**
 * Dispatches email via an HTTPS Webhook relay (e.g. Google Apps Script Web App).
 * Operates over Port 443 HTTPS which is never blocked by cloud firewalls.
 */
function sendViaHttpsWebhook($webhookUrl, $recipientEmail, $recipientName, $subject, $htmlBody, $otpCode) {
    $payload = json_encode([
        'to' => $recipientEmail,
        'name' => $recipientName,
        'subject' => $subject,
        'html' => $htmlBody,
        'otp' => $otpCode,
        'secret' => 'TomorrowVote2026'
    ]);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Webhook curl error: " . $curlErr);
    }
    if ($httpCode >= 200 && $httpCode < 400) {
        return true;
    }
    throw new Exception("Webhook returned HTTP " . $httpCode . ": " . substr($response, 0, 100));
}

/**
 * Dispatches email via Brevo HTTPS REST API (Port 443).
 */
function sendViaBrevo($apiKey, $recipientEmail, $recipientName, $subject, $htmlBody) {
    $payload = json_encode([
        'sender' => [
            'name' => SMTP_FROM_NAME,
            'email' => SMTP_FROM_EMAIL
        ],
        'to' => [
            ['email' => $recipientEmail, 'name' => $recipientName]
        ],
        'subject' => $subject,
        'htmlContent' => $htmlBody
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Brevo curl error: " . $curlErr);
    }
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }
    throw new Exception("Brevo API error (" . $httpCode . "): " . substr($response, 0, 100));
}

/**
 * Dispatches email via raw SMTP with fast failover between Port 465 (SSL) and Port 587 (TLS).
 */
function sendViaSmtp($recipientEmail, $recipientName, $subject, $htmlBody, $otpCode) {
    $attempts = [
        ['port' => (int)SMTP_PORT, 'secure' => (SMTP_PORT == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS)],
        ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS],
        ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS]
    ];

    // Deduplicate
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
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = $att['secure'];
            $mail->Port       = $att['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 3; // Fast timeout to quickly detect blocked cloud egress ports

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = "Hello {$recipientName},\n\nYour Tomorrow Vote login verification code is: {$otpCode}\n\nThis code will expire in 10 minutes.\nIf you did not request this code, please contact your election administrator.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            $lastError = $mail->ErrorInfo ?: $e->getMessage();
            error_log("PHPMailer failed on port {$att['port']}: " . $lastError);
        }
    }

    throw new Exception("Unable to connect to mail server (SMTP Error: " . $lastError . ")");
}

/**
 * Master OTP email dispatch function.
 * Tries HTTPS Webhook / Brevo first if configured, then falls back to direct SMTP.
 *
 * @param string $recipientEmail
 * @param string $recipientName
 * @param string $otpCode
 * @return bool
 * @throws Exception
 */
function sendVoterLoginOtp($recipientEmail, $recipientName, $otpCode) {
    $subject = "{$otpCode} is your Tomorrow Vote login verification code";
    $htmlBody = buildOtpEmailHtml($recipientName, $otpCode);

    // 1. If HTTPS Webhook URL is defined (bypasses cloud host SMTP blocking over port 443)
    if (!empty(MAIL_WEBHOOK_URL)) {
        return sendViaHttpsWebhook(MAIL_WEBHOOK_URL, $recipientEmail, $recipientName, $subject, $htmlBody, $otpCode);
    }

    // 2. If Brevo HTTPS API key is defined
    if (!empty(BREVO_API_KEY)) {
        return sendViaBrevo(BREVO_API_KEY, $recipientEmail, $recipientName, $subject, $htmlBody);
    }

    // 3. Fall back to standard SMTP (Port 465 SSL, then Port 587 STARTTLS)
    return sendViaSmtp($recipientEmail, $recipientName, $subject, $htmlBody, $otpCode);
}

/**
 * Builds institutional HTML email body for Official Ballot Receipt with Password-Protected PDF notice.
 */
function buildBallotReceiptEmailHtml($recipientName, $studentId, $voteTimestamp, $verificationHash, $attachmentFilename) {
    $escapedName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $escapedStudentId = htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8');
    $escapedTimestamp = htmlspecialchars($voteTimestamp, ENT_QUOTES, 'UTF-8');
    $escapedHash = htmlspecialchars($verificationHash, ENT_QUOTES, 'UTF-8');
    $escapedFilename = htmlspecialchars($attachmentFilename, ENT_QUOTES, 'UTF-8');

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
            .email-wrapper { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 18px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06); }
            .email-header { background: linear-gradient(135deg, #0b224d 0%, #1059d6 100%); padding: 32px 24px; text-align: center; color: #ffffff; }
            .email-header .badge { display: inline-block; background: rgba(255, 255, 255, 0.2); padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 10px; }
            .email-header h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
            .email-header p { margin: 6px 0 0 0; font-size: 13px; color: #bfdbfe; font-weight: 500; }
            .email-body { padding: 32px 28px; color: #1e293b; line-height: 1.6; }
            .greeting { font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
            .text { font-size: 14px; color: #475569; margin-bottom: 20px; }
            .status-badge { display: inline-block; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 800; margin-bottom: 22px; }
            .security-box { background: #eff6ff; border: 2px solid #bfdbfe; border-radius: 14px; padding: 22px; margin-bottom: 24px; }
            .security-title { font-size: 15px; font-weight: 800; color: #1e40af; margin-bottom: 8px; }
            .security-password-box { background: #ffffff; border: 1.5px dashed #93c5fd; border-radius: 10px; padding: 14px 18px; margin: 12px 0; text-align: center; }
            .password-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
            .password-value { font-family: 'Courier New', Courier, monospace; font-size: 24px; font-weight: 900; color: #1059d6; letter-spacing: 2px; margin-top: 4px; }
            .security-desc { font-size: 13px; color: #1e40af; margin: 0; line-height: 1.5; }
            .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 13px; }
            .meta-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
            .meta-table td.label { font-weight: 700; color: #64748b; width: 35%; }
            .meta-table td.value { font-weight: 600; color: #0f172a; word-break: break-all; }
            .hash-val { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #334155; }
            .notice-box { background: #f8fafc; border-left: 4px solid #64748b; padding: 12px 16px; border-radius: 6px; font-size: 12px; color: #475569; margin-bottom: 20px; }
            .email-footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 28px; text-align: center; font-size: 12px; color: #94a3b8; line-height: 1.5; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <div class='email-header'>
                <div class='badge'>Official Election Document</div>
                <h1>Bestlink College of the Philippines</h1>
                <p>Supreme Student Council Elections 2026 — Tomorrow Vote</p>
            </div>
            <div class='email-body'>
                <div class='greeting'>Mabuhay, {$escapedName}!</div>
                <div class='text'>
                    Thank you for participating in the Supreme Student Council Elections 2026. Your official ballot has been securely submitted, encrypted, and recorded in the election database.
                </div>
                <div class='status-badge'>
                    &#10004; Ballot Verified &amp; Cryptographically Recorded
                </div>

                <div class='security-box'>
                    <div class='security-title'>
                        &#128274; Password-Protected Ballot Attachment
                    </div>
                    <p style='font-size: 13px; color: #334155; margin: 0 0 10px 0;'>
                        Attached to this email is an official certified copy of your cast ballot (<strong>{$escapedFilename}</strong>). For your privacy and electoral security, the document is <strong>password-protected</strong>.
                    </p>
                    <div class='security-password-box'>
                        <div class='password-label'>File Password (Your Student ID)</div>
                        <div class='password-value'>{$escapedStudentId}</div>
                    </div>
                    <p class='security-desc'>
                        When opening the PDF document in any viewer (Adobe Acrobat, Chrome, Android, iOS), enter your <strong>Student ID</strong> above when prompted for a password.
                    </p>
                </div>

                <table class='meta-table'>
                    <tr>
                        <td class='label'>Voter Name</td>
                        <td class='value'>{$escapedName}</td>
                    </tr>
                    <tr>
                        <td class='label'>Student ID</td>
                        <td class='value'>{$escapedStudentId}</td>
                    </tr>
                    <tr>
                        <td class='label'>Submission Time</td>
                        <td class='value'>{$escapedTimestamp}</td>
                    </tr>
                    <tr>
                        <td class='label'>Verification Hash</td>
                        <td class='value hash-val'>{$escapedHash}</td>
                    </tr>
                </table>

                <div class='notice-box'>
                    <strong>Official COMELEC Notice:</strong> Please retain this certified receipt and attached document for your personal records. The Commission on Elections recognizes this document as valid cryptographic proof of ballot submission.
                </div>
            </div>
            <div class='email-footer'>
                &copy; 2026 Tomorrow Vote — Bestlink College of the Philippines Student Council.<br>
                This is an automated institutional notification. Please do not reply directly to this email.
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Dispatches email with PDF attachment via HTTPS Webhook relay.
 */
function sendViaHttpsWebhookWithAttachment($webhookUrl, $recipientEmail, $recipientName, $subject, $htmlBody, $attachmentContent, $attachmentFilename, $studentId) {
    $payload = json_encode([
        'to' => $recipientEmail,
        'name' => $recipientName,
        'subject' => $subject,
        'html' => $htmlBody,
        'student_id' => $studentId,
        'attachment_filename' => $attachmentFilename,
        'attachment_base64' => base64_encode($attachmentContent),
        'secret' => 'TomorrowVote2026'
    ]);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Webhook curl error: " . $curlErr);
    }
    if ($httpCode >= 200 && $httpCode < 400) {
        return true;
    }
    throw new Exception("Webhook returned HTTP " . $httpCode . ": " . substr($response, 0, 100));
}

/**
 * Dispatches email with PDF attachment via Brevo HTTPS API.
 */
function sendViaBrevoWithAttachment($apiKey, $recipientEmail, $recipientName, $subject, $htmlBody, $attachmentContent, $attachmentFilename) {
    $payload = json_encode([
        'sender' => [
            'name' => SMTP_FROM_NAME,
            'email' => SMTP_FROM_EMAIL
        ],
        'to' => [
            ['email' => $recipientEmail, 'name' => $recipientName]
        ],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
        'attachment' => [
            [
                'name' => $attachmentFilename,
                'content' => base64_encode($attachmentContent)
            ]
        ]
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Brevo curl error: " . $curlErr);
    }
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }
    throw new Exception("Brevo API error (" . $httpCode . "): " . substr($response, 0, 100));
}

/**
 * Dispatches email with password-protected PDF attachment via SMTP with multi-port failover.
 */
function sendBallotReceiptViaSmtp($recipientEmail, $recipientName, $subject, $htmlBody, $pdfBinaryContent, $pdfFilename, $studentId) {
    $attempts = [
        ['port' => (int)SMTP_PORT, 'secure' => (SMTP_PORT == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS)],
        ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS],
        ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS]
    ];

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
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = $att['secure'];
            $mail->Port       = $att['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 5;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(SMTP_FROM_EMAIL, 'Tomorrow Vote — Elections 2026');
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->addReplyTo(SMTP_FROM_EMAIL, 'Tomorrow Vote — Elections 2026');

            // Attach password-protected PDF directly from memory
            $mail->addStringAttachment($pdfBinaryContent, $pdfFilename, 'base64', 'application/pdf');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = "Mabuhay {$recipientName},\n\nYour official ballot for the Supreme Student Council Elections 2026 has been recorded.\n\nYour password-protected ballot receipt is attached as: {$pdfFilename}\nTo open the PDF, enter your Student ID: {$studentId}\n\nThank you for exercising your right to vote!";

            $mail->send();
            return true;
        } catch (Exception $e) {
            $lastError = $mail->ErrorInfo ?: $e->getMessage();
            error_log("PHPMailer ballot receipt failed on port {$att['port']}: " . $lastError);
        }
    }

    throw new Exception("Unable to deliver ballot receipt via SMTP: " . $lastError);
}

/**
 * Master Ballot Receipt email dispatch function.
 * Generates email and sends via Webhook / Brevo / SMTP with the password-protected PDF attached.
 *
 * @param string $recipientEmail
 * @param string $recipientName
 * @param string $studentId
 * @param string $voteTimestamp
 * @param string $verificationHash
 * @param string $pdfBinaryContent
 * @param string|null $pdfFilename
 * @return bool
 * @throws Exception
 */
function sendVoterBallotReceipt($recipientEmail, $recipientName, $studentId, $voteTimestamp, $verificationHash, $pdfBinaryContent, $pdfFilename = null) {
    if (empty($pdfFilename)) {
        $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentId);
        $pdfFilename = "TomorrowVote_BallotReceipt_{$cleanId}.pdf";
    }

    $subject = "Official Ballot Receipt — Supreme Student Council Elections 2026";
    $htmlBody = buildBallotReceiptEmailHtml($recipientName, $studentId, $voteTimestamp, $verificationHash, $pdfFilename);

    // 1. If HTTPS Webhook URL is defined
    if (!empty(MAIL_WEBHOOK_URL)) {
        try {
            return sendViaHttpsWebhookWithAttachment(MAIL_WEBHOOK_URL, $recipientEmail, $recipientName, $subject, $htmlBody, $pdfBinaryContent, $pdfFilename, $studentId);
        } catch (Exception $e) {
            error_log("Ballot receipt webhook delivery failed, falling back to SMTP: " . $e->getMessage());
        }
    }

    // 2. If Brevo HTTPS API key is defined
    if (!empty(BREVO_API_KEY)) {
        try {
            return sendViaBrevoWithAttachment(BREVO_API_KEY, $recipientEmail, $recipientName, $subject, $htmlBody, $pdfBinaryContent, $pdfFilename);
        } catch (Exception $e) {
            error_log("Ballot receipt Brevo delivery failed, falling back to SMTP: " . $e->getMessage());
        }
    }

    // 3. Fall back to standard SMTP (Port 465 SSL, then Port 587 STARTTLS)
    return sendBallotReceiptViaSmtp($recipientEmail, $recipientName, $subject, $htmlBody, $pdfBinaryContent, $pdfFilename, $studentId);
}

