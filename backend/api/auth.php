<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();

/**
 * Mask an email address for privacy (e.g., c***a@gmail.com)
 */
function maskEmailAddress($email) {
    if (empty($email) || strpos($email, '@') === false) {
        return 'your registered email';
    }
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);
    if ($len <= 2) {
        $masked = substr($name, 0, 1) . '***';
    } else {
        $masked = substr($name, 0, 1) . str_repeat('*', min(4, $len - 2)) . substr($name, -1);
    }
    return $masked . '@' . $domain;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please provide both username and password.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR student_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$username, $username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // ADMIN: Direct password authentication
        if ($user['role'] === 'admin') {
            $token = bin2hex(random_bytes(32));
            $updateToken = $pdo->prepare("UPDATE users SET session_token = ? WHERE id = ?");
            $updateToken->execute([$token, $user['id']]);
            
            echo json_encode([
                'success' => true, 
                'token' => $token, 
                'user' => [
                    'id' => $user['id'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name'],
                    'student_id' => $user['student_id'],
                    'has_voted' => (bool)$user['has_voted']
                ]
            ]);
            exit;
        }

        // VOTER: Require 6-digit email OTP verification
        if ($user['role'] === 'voter') {
            if (empty($user['email'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'No email address registered for this voter account. Please contact election administration.'
                ]);
                exit;
            }

            // Generate secure 6-digit numeric OTP
            $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            // OTP valid for 10 minutes
            $expiresAt = date('Y-m-d H:i:s', time() + 600);

            $updateOtp = $pdo->prepare("UPDATE users SET login_otp = ?, login_otp_expires_at = ? WHERE id = ?");
            $updateOtp->execute([$otp, $expiresAt, $user['id']]);

            try {
                sendVoterLoginOtp($user['email'], $user['full_name'], $otp);
            } catch (Exception $mailEx) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Unable to dispatch verification email. Please check internet connection or contact administration.'
                ]);
                exit;
            }

            $masked = maskEmailAddress($user['email']);
            echo json_encode([
                'success' => true,
                'requires_otp' => true,
                'otp_user_id' => (int)$user['id'],
                'masked_email' => $masked,
                'message' => "A 6-digit verification code has been sent to {$masked}."
            ]);
            exit;
        }
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verify_otp') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $otpUserId = (int)($data['otp_user_id'] ?? 0);
    $otp = trim($data['otp'] ?? '');

    if (!$otpUserId || empty($otp)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please provide the 6-digit verification code.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'voter' LIMIT 1");
    $stmt->execute([$otpUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Voter account not found.']);
        exit;
    }

    if (empty($user['login_otp'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No pending verification code found. Please sign in again.']);
        exit;
    }

    // Check expiration (10 minutes)
    if (empty($user['login_otp_expires_at']) || strtotime($user['login_otp_expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Verification code has expired. Please click Resend Code.']);
        exit;
    }

    // Check match
    if ($user['login_otp'] !== $otp) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Incorrect verification code. Please check your email and try again.']);
        exit;
    }

    // Code verified! Invalidate OTP and issue 64-char session token
    $token = bin2hex(random_bytes(32));
    $updateStmt = $pdo->prepare("UPDATE users SET login_otp = NULL, login_otp_expires_at = NULL, session_token = ? WHERE id = ?");
    $updateStmt->execute([$token, $user['id']]);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
            'student_id' => $user['student_id'],
            'has_voted' => (bool)$user['has_voted']
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'resend_otp') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $otpUserId = (int)($data['otp_user_id'] ?? 0);

    if (!$otpUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid voter request.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'voter' LIMIT 1");
    $stmt->execute([$otpUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Voter account not found.']);
        exit;
    }

    // Generate fresh 6-digit numeric OTP
    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + 600);

    $updateOtp = $pdo->prepare("UPDATE users SET login_otp = ?, login_otp_expires_at = ? WHERE id = ?");
    $updateOtp->execute([$otp, $expiresAt, $user['id']]);

    try {
        sendVoterLoginOtp($user['email'], $user['full_name'], $otp);
    } catch (Exception $mailEx) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Unable to dispatch verification email. Please check internet connection or contact administration.'
        ]);
        exit;
    }

    $masked = maskEmailAddress($user['email']);
    echo json_encode([
        'success' => true,
        'message' => "A new verification code has been sent to {$masked}."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'logout') {
    $user = getApiUser();
    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'me') {
    $user = getApiUser();
    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'role' => $user['role'],
                'full_name' => $user['full_name'],
                'student_id' => $user['student_id'],
                'has_voted' => (bool)$user['has_voted']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
