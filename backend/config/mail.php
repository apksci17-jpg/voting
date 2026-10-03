<?php
// config/mail.php — PHPMailer SMTP Configuration for OTP Delivery

// SMTP Credentials for Tomorrow Vote automated email delivery
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
// Default to Port 465 (SMTPS) which is fully open and supported across cloud providers (Railway, AWS, etc.)
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_USER', getenv('SMTP_USER') ?: 'cassandramherranola@gmail.com');
// Spaces are removed from the Google App Password for clean authentication
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'yjjicbalkrprszcr');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'cassandramherranola@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Tomorrow Vote Verification');
