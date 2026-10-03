<?php
// config/mail.php — Mailer & SMTP Configuration for OTP Delivery

// SMTP Credentials for Tomorrow Vote automated email delivery
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
// Default to Port 465 (SMTPS) which uses direct SSL
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_USER', getenv('SMTP_USER') ?: 'cassandramherranola@gmail.com');
// Google App Password for cassandramherranola@gmail.com
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'yjjicbalkrprszcr');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'cassandramherranola@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Tomorrow Vote Verification');

// HTTPS Mail Delivery Options (Operates over unrestricted Port 443 to bypass cloud PaaS SMTP blocking)
// 1. Google Apps Script Web App or custom HTTPS webhook:
define('MAIL_WEBHOOK_URL', getenv('MAIL_WEBHOOK_URL') ?: '');
// 2. Brevo (Sendinblue) HTTPS API Key:
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
