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
 * Builds institutional HTML email body for Official Ballot Receipt formatted as an online banking e-Statement (e-BS).
 */
function buildBallotReceiptEmailHtml($recipientName, $studentId, $voteTimestamp, $verificationHash, $attachmentFilename, $user = []) {
    $escapedName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $escapedStudentId = htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8');
    $escapedTimestamp = htmlspecialchars($voteTimestamp, ENT_QUOTES, 'UTF-8');
    $escapedHash = htmlspecialchars($verificationHash, ENT_QUOTES, 'UTF-8');
    $escapedFilename = htmlspecialchars($attachmentFilename, ENT_QUOTES, 'UTF-8');

    $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $studentId);
    $statementNo = "BS-2026-" . $cleanId . "-" . strtoupper(substr($verificationHash, 0, 6));

    $progSection = trim(($user['section'] ?? '') . ' ' . ($user['grade_level'] ?? ''));
    $escapedSection = htmlspecialchars(!empty($progSection) ? $progSection : 'BSIT College Department', ENT_QUOTES, 'UTF-8');

    $dobDigits = !empty($user['date_of_birth']) ? preg_replace('/[^0-9]/', '', $user['date_of_birth']) : '';
    $dobDisplay = !empty($dobDigits) ? htmlspecialchars($dobDigits, ENT_QUOTES, 'UTF-8') : '';

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Electronic Statement of Ballot</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
            .email-wrapper { max-width: 620px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08); }
            .bank-header { background: linear-gradient(135deg, #0b224d 0%, #173873 100%); padding: 30px 28px 24px 28px; text-align: left; color: #ffffff; border-bottom: 3px solid #d97706; }
            .bank-tag { display: inline-block; background: rgba(217, 119, 6, 0.25); border: 1px solid #f59e0b; color: #fef08a; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 800; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 12px; }
            .bank-title { margin: 0; font-size: 21px; font-weight: 900; letter-spacing: -0.4px; line-height: 1.3; }
            .bank-subtitle { margin: 6px 0 0 0; font-size: 13px; color: #bfdbfe; font-weight: 500; }
            .email-body { padding: 32px 28px; color: #1e293b; line-height: 1.6; }
            .salutation { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
            .intro-text { font-size: 14px; color: #475569; margin-bottom: 22px; }
            
            /* Bank Attachment Card */
            .attachment-pill { background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px; }
            .pdf-icon { width: 42px; height: 42px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; color: #dc2626; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 12px; font-family: monospace; flex-shrink: 0; }
            .att-details { flex: 1; }
            .att-name { font-size: 14px; font-weight: 800; color: #0f172a; word-break: break-all; }
            .att-meta { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px; }

            /* Security & Password Alert Box */
            .bank-password-box { background: #fffbeb; border: 2px solid #f59e0b; border-radius: 14px; padding: 22px; margin-bottom: 26px; }
            .bank-pw-title { font-size: 14px; font-weight: 900; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
            .pw-display-card { background: #ffffff; border: 2px dashed #d97706; border-radius: 10px; padding: 14px; text-align: center; margin: 14px 0; }
            .pw-label { font-size: 11px; font-weight: 800; color: #78350f; text-transform: uppercase; letter-spacing: 0.6px; }
            .pw-value { font-family: 'Courier New', Courier, monospace; font-size: 26px; font-weight: 900; color: #b45309; letter-spacing: 3px; margin-top: 4px; }
            .steps-list { margin: 12px 0 0 0; padding-left: 20px; font-size: 13px; color: #78350f; line-height: 1.6; }
            .steps-list li { margin-bottom: 6px; }

            /* Statement Overview Ledger */
            .statement-ledger { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 13px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
            .statement-ledger th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
            .statement-ledger td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; }
            .statement-ledger tr:last-child td { border-bottom: none; }
            .ledger-label { font-weight: 700; color: #475569; width: 38%; }
            .ledger-val { font-weight: 600; color: #0f172a; word-break: break-all; }
            .mono-text { font-family: 'Courier New', Courier, monospace; font-size: 11.5px; }

            /* Security & Legal Notice */
            .security-notice { background: #f8fafc; border-left: 4px solid #0b224d; padding: 14px 18px; border-radius: 6px; font-size: 12px; color: #475569; margin-bottom: 22px; line-height: 1.5; }
            .email-signoff { margin-top: 24px; font-size: 13px; color: #334155; line-height: 1.5; }
            .email-footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 28px; text-align: center; font-size: 11.5px; color: #94a3b8; line-height: 1.5; }
        </style>
    </head>
    <body>
        <div class='email-wrapper'>
            <!-- Bank-Style Header -->
            <div class='bank-header'>
                <div class='bank-tag'>Official Electronic Statement (e-BS)</div>
                <div class='bank-title'>Bestlink College of the Philippines</div>
                <div class='bank-subtitle'>Commission on Student Elections • Electronic Voting Services</div>
            </div>

            <!-- Email Body Content -->
            <div class='email-body'>
                <div class='salutation'>Dear {$escapedName},</div>
                <div class='intro-text'>
                    Thank you for exercising your electoral right in the <strong>Supreme Student Council General Elections 2026</strong>. Your official ballot transactions have been authenticated, encrypted, and recorded in the college voting ledger.<br><br>
                    Attached to this email is your certified <strong>Electronic Statement of Ballot (e-BS)</strong> containing the itemized record of your cast selections.
                </div>

                <!-- Attachment Details Box -->
                <div class='attachment-pill'>
                    <div class='pdf-icon'>PDF</div>
                    <div class='att-details'>
                        <div class='att-name'>{$escapedFilename}</div>
                        <div class='att-meta'>Encrypted Official Statement of Ballot • 128-Bit Protected Document</div>
                    </div>
                </div>

                <!-- Banking-Style Password Alert Box -->
                <div class='bank-password-box'>
                    <div class='bank-pw-title'>
                        <span>&#128274;</span> Important: Your Attached Statement is Password-Protected
                    </div>
                    <p style='margin: 0; font-size: 13px; color: #78350f;'>
                        To safeguard your voter privacy and comply with electoral data confidentiality, your attached PDF statement is encrypted.
                    </p>
                    
                    <div class='pw-display-card'>
                        <div class='pw-label'>Document Unlock Password (Your Student ID):</div>
                        <div class='pw-value'>{$escapedStudentId}</div>
                    </div>

                    <div style='font-size: 12.5px; font-weight: 800; color: #92400e; margin-bottom: 4px;'>Instructions to Open Your Statement:</div>
                    <ol class='steps-list'>
                        <li>Download and open the attached file (<strong>{$escapedFilename}</strong>).</li>
                        <li>When prompted for a password by your PDF viewer (Adobe Acrobat, Google Chrome, or mobile reader), enter your Student ID: <strong>{$escapedStudentId}</strong>.</li>
                        <li>Tap <em>Open</em> or <em>OK</em> to view your certified ballot record." . (!empty($dobDisplay) ? "<br><em>(Alternate unlock key: Your Date of Birth in YYYYMMDD format [<strong>{$dobDisplay}</strong>] is also accepted).</em>" : "") . "</li>
                    </ol>
                </div>

                <!-- Statement Overview Table -->
                <table class='statement-ledger'>
                    <thead>
                        <tr>
                            <th colspan='2'>Electronic Statement Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class='ledger-label'>Account Holder</td>
                            <td class='ledger-val'>{$escapedName}</td>
                        </tr>
                        <tr>
                            <td class='ledger-label'>Student Account No.</td>
                            <td class='ledger-val'>{$escapedStudentId}</td>
                        </tr>
                        <tr>
                            <td class='ledger-label'>Academic Program</td>
                            <td class='ledger-val'>{$escapedSection}</td>
                        </tr>
                        <tr>
                            <td class='ledger-label'>Statement Reference</td>
                            <td class='ledger-val mono-text'>{$statementNo}</td>
                        </tr>
                        <tr>
                            <td class='ledger-label'>Transaction Timestamp</td>
                            <td class='ledger-val'>{$escapedTimestamp} PHT</td>
                        </tr>
                        <tr>
                            <td class='ledger-label'>Ballot Audit Hash</td>
                            <td class='ledger-val mono-text'>{$escapedHash}</td>
                        </tr>
                        <tr>
                            <td class='ledger-label'>Record Status</td>
                            <td class='ledger-val' style='color: #166534; font-weight: 800;'>&#10004; POSTED &amp; CRYPTOGRAPHICALLY SEALED</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Bank-Grade Security Disclaimer -->
                <div class='security-notice'>
                    <strong>Security Advisory:</strong> Bestlink College of the Philippines and the Commission on Elections will NEVER request your student credentials, passwords, or biometrics via email, SMS, or telephone. Please retain this electronic statement as permanent proof of your vote.
                </div>

                <div class='email-signoff'>
                    Sincerely,<br>
                    <strong>COMMISSION ON STUDENT ELECTIONS (COMELEC)</strong><br>
                    Bestlink College of the Philippines
                </div>
            </div>

            <!-- Footer -->
            <div class='email-footer'>
                &copy; 2026 Bestlink College of the Philippines • Supreme Student Council Elections.<br>
                Quirino Highway, Novaliches, Quezon City, Metro Manila • PACUCOA Accredited.<br>
                This is an automated system-generated statement. Replies to this inbox are not monitored.
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

            $mail->setFrom(SMTP_FROM_EMAIL, 'Bestlink College of the Philippines — Electronic Statements');
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->addReplyTo(SMTP_FROM_EMAIL, 'Bestlink College of the Philippines — Electronic Statements');

            // Attach password-protected PDF directly from memory with explicit attachment disposition
            $mail->addStringAttachment($pdfBinaryContent, $pdfFilename, 'base64', 'application/pdf', 'attachment');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = "Dear {$recipientName},\n\nYour official Electronic Statement of Ballot for the Bestlink College of the Philippines Supreme Student Council General Elections 2026 has been generated.\n\nYour password-protected statement is attached as: {$pdfFilename}\n\nHOW TO OPEN:\n1. Open the attached PDF file.\n2. When prompted for a password, enter your Student ID: {$studentId}\n\nThank you for exercising your right to vote!\n\nBestlink College of the Philippines — Commission on Student Elections";

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
 * @param array $user
 * @return bool
 * @throws Exception
 */
function sendVoterBallotReceipt($recipientEmail, $recipientName, $studentId, $voteTimestamp, $verificationHash, $pdfBinaryContent, $pdfFilename = null, $user = []) {
    $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentId);
    if (empty($pdfFilename)) {
        $pdfFilename = "eStatement_Ballot_{$cleanId}.pdf";
    }

    $subject = "[Bestlink COMELEC] Electronic Statement of Ballot (e-BS) — {$studentId} — Elections 2026";
    $htmlBody = buildBallotReceiptEmailHtml($recipientName, $studentId, $voteTimestamp, $verificationHash, $pdfFilename, $user);

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


