<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/encryption.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/ballot_receipt.php';

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();

/**
 * Progressive lockout duration in seconds:
 * 1st lock: 60s (1 min)
 * 2nd lock: 120s (2 mins)
 * 3rd lock: 300s (5 mins)
 * 4th+ lock: 600s (10 mins)
 */
function getVoterLockoutDurationSeconds($lockCount) {
    if ($lockCount <= 1) return 60;
    if ($lockCount === 2) return 120;
    if ($lockCount === 3) return 300;
    return 600;
}

/**
 * Checks if a voter is currently in lockout.
 * Returns array: [is_locked => bool, remaining_seconds => int, lock_count => int]
 */
function checkVoterPasswordLockout($user) {
    $now = time();
    $isLocked = false;
    $remainingSeconds = 0;
    $lockCount = (int)($user['pwd_lock_count'] ?? 0);

    if (!empty($user['pwd_locked_until'])) {
        $lockedTime = strtotime($user['pwd_locked_until']);
        if ($lockedTime > $now) {
            $isLocked = true;
            $remainingSeconds = $lockedTime - $now;
        }
    }

    return [
        'is_locked' => $isLocked,
        'remaining_seconds' => $remainingSeconds,
        'lock_count' => $lockCount
    ];
}

/**
 * Handles password verification with 3 attempts limit, progressive lockout, and security email alert.
 * Returns array with result details.
 */
function verifyVoterPasswordWithLockout($pdo, $user, $password) {
    $lockStatus = checkVoterPasswordLockout($user);
    if ($lockStatus['is_locked']) {
        $lockSec = $lockStatus['remaining_seconds'];
        $durText = ($lockSec >= 60) ? ceil($lockSec / 60) . ' minute(s)' : "{$lockSec} second(s)";
        return [
            'success' => false,
            'http_code' => 423,
            'is_locked' => true,
            'remaining_attempts' => 0,
            'remaining_seconds' => $lockSec,
            'lock_duration' => getVoterLockoutDurationSeconds($lockStatus['lock_count']),
            'lock_count' => $lockStatus['lock_count'],
            'error' => "Account verification is temporarily locked. Please wait {$durText} before trying again."
        ];
    }

    $isPasswordValid = password_verify($password, $user['password_hash']) || (!empty($user['student_id']) && $password === $user['student_id']);

    if ($isPasswordValid) {
        // Successful verification — reset failed attempts, lock time, and lock count
        $resetStmt = $pdo->prepare("UPDATE users SET pwd_failed_attempts = 0, pwd_locked_until = NULL, pwd_lock_count = 0 WHERE id = ?");
        $resetStmt->execute([$user['id']]);
        return [
            'success' => true,
            'http_code' => 200,
            'is_locked' => false,
            'remaining_attempts' => 3,
            'remaining_seconds' => 0
        ];
    }

    // Invalid password: count failure
    $currentAttempts = (int)($user['pwd_failed_attempts'] ?? 0);
    $newAttempts = $currentAttempts + 1;

    if ($newAttempts >= 3) {
        // Lock triggered or increased!
        $currentLockCount = (int)($user['pwd_lock_count'] ?? 0);
        $newLockCount = $currentLockCount + 1;
        $lockDuration = getVoterLockoutDurationSeconds($newLockCount);
        $lockedUntil = date('Y-m-d H:i:s', time() + $lockDuration);

        $lockStmt = $pdo->prepare("UPDATE users SET pwd_failed_attempts = 3, pwd_locked_until = ?, pwd_lock_count = ? WHERE id = ?");
        $lockStmt->execute([$lockedUntil, $newLockCount, $user['id']]);

        // Dispatch security alert email to voter
        $emailSent = false;
        if (!empty($user['email'])) {
            try {
                $lockMinutes = round($lockDuration / 60, 1);
                $incidentTime = date('F j, Y, g:i A') . ' (PST)';
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Recorded Client';
                sendPasswordLockoutAlert($user['email'], $user['full_name'], $lockMinutes, $incidentTime, $ip);
                $emailSent = true;
            } catch (Exception $mailEx) {
                error_log("Failed to send lockout alert to voter ID {$user['id']}: " . $mailEx->getMessage());
            }
        }

        $durText = ($lockDuration >= 60) ? round($lockDuration / 60) . ' minute(s)' : "{$lockDuration} second(s)";
        return [
            'success' => false,
            'http_code' => 423,
            'is_locked' => true,
            'remaining_attempts' => 0,
            'remaining_seconds' => $lockDuration,
            'lock_duration' => $lockDuration,
            'lock_count' => $newLockCount,
            'email_sent' => $emailSent,
            'error' => "Maximum attempts exceeded. Account verification locked for {$durText}. A security alert has been dispatched to your registered email."
        ];
    }

    // Still has attempts remaining (1 or 2)
    $remainingAttempts = 3 - $newAttempts;
    $updStmt = $pdo->prepare("UPDATE users SET pwd_failed_attempts = ? WHERE id = ?");
    $updStmt->execute([$newAttempts, $user['id']]);

    return [
        'success' => false,
        'http_code' => 400,
        'is_locked' => false,
        'remaining_attempts' => $remainingAttempts,
        'remaining_seconds' => 0,
        'error' => "Incorrect password. {$remainingAttempts} attempt(s) remaining before temporary account lock."
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'dashboard') {
    $user = requireApiVoter();
    $votingStatus = getElectionSetting('voting_status', 'CLOSED');
    echo json_encode([
        'success' => true,
        'has_voted' => (bool)$user['has_voted'],
        'voting_status' => $votingStatus,
        'user' => [
            'full_name' => $user['full_name']
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'candidates') {
    requireApiVoter();
    $stmt = $pdo->query("
        SELECT c.id, c.position_id, c.candidate_name, c.year_level, c.platform, c.profile_image, p.position_name, p.display_order
        FROM candidates c
        JOIN positions p ON c.position_id = p.id
        ORDER BY p.display_order ASC, c.candidate_name ASC
    ");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $grouped = [];
    foreach ($candidates as $c) {
        $posId = $c['position_id'];
        if (!isset($grouped[$posId])) {
            $grouped[$posId] = [
                'position_id' => $posId,
                'position_name' => $c['position_name'],
                'display_order' => $c['display_order'],
                'candidates' => []
            ];
        }
        $grouped[$posId]['candidates'][] = $c;
    }
    
    echo json_encode([
        'success' => true,
        'positions' => array_values($grouped)
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cast_vote') {
    $user = requireApiVoter();
    $votingStatus = getElectionSetting('voting_status', 'CLOSED');

    if ($votingStatus !== 'OPEN') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Voting is CLOSED.']);
        exit;
    }

    if ($user['has_voted']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You have already voted.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $finalVotes = $data['votes'] ?? [];

    if (empty($finalVotes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No votes submitted.']);
        exit;
    }

    // If account is currently locked, prevent casting
    $lockStatus = checkVoterPasswordLockout($user);
    if ($lockStatus['is_locked']) {
        http_response_code(423);
        $remSec = $lockStatus['remaining_seconds'];
        $durText = ($remSec >= 60) ? ceil($remSec / 60) . ' minute(s)' : "{$remSec} second(s)";
        echo json_encode([
            'success' => false,
            'is_locked' => true,
            'remaining_seconds' => $remSec,
            'error' => "Account verification is temporarily locked. Please wait {$durText} before trying again."
        ]);
        exit;
    }

    // Verify password if provided as biometric fallback
    $password = $data['password'] ?? '';
    if (!empty($password)) {
        $result = verifyVoterPasswordWithLockout($pdo, $user, $password);
        if (!$result['success']) {
            http_response_code($result['http_code'] ?? 400);
            unset($result['http_code']);
            echo json_encode($result);
            exit;
        }
    }

    $voteTimestamp = date('Y-m-d H:i:s');
    $success = false;

    try {
        $pdo->beginTransaction();

        foreach ($finalVotes as $positionId => $candidateId) {
            $positionId = (int)$positionId;
            $candidateId = (int)$candidateId;

            $payloadData = [
                'voter_id' => $user['id'],
                'position_id' => $positionId,
                'candidate_id' => $candidateId,
                'timestamp' => $voteTimestamp
            ];
            $encryptedPayload = encryptBallot($payloadData);

            $stmt = $pdo->prepare("INSERT INTO votes (voter_id, position_id, candidate_id, encrypted_payload) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], $positionId, $candidateId, $encryptedPayload]);
        }

        $updateStmt = $pdo->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        $pdo->commit();
        $success = true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $success = false;
    }
    
    if ($success) {
        $studentId = !empty($user['student_id']) ? trim($user['student_id']) : trim($user['username']);
        $verificationHash = hash('sha256', $user['id'] . '|' . $studentId . '|' . $voteTimestamp . '|' . json_encode($finalVotes));
        
        $emailSent = false;
        $emailError = null;

        try {
            $selectionsDetails = getBallotSelectionsDetails($pdo, $finalVotes);
            $cleanDob = !empty($user['date_of_birth']) ? preg_replace('/[^0-9]/', '', $user['date_of_birth']) : null;
            // Generate password-protected bank-statement PDF (unlocked with voter's Student ID or birthdate)
            $pdfContent = generateProtectedBallotReceiptPdf($user, $selectionsDetails, $voteTimestamp, $verificationHash, $studentId, $cleanDob);

            if (!empty($user['email'])) {
                $emailSent = sendVoterBallotReceipt($user['email'], $user['full_name'], $studentId, $voteTimestamp, $verificationHash, $pdfContent, null, $user);
            }
        } catch (Throwable $mailEx) {
            $emailError = $mailEx->getMessage();
            error_log("Ballot receipt dispatch failed for voter ID {$user['id']}: " . $emailError);
        }

        echo json_encode([
            'success' => true,
            'timestamp' => $voteTimestamp,
            'email_sent' => $emailSent,
            'email' => $user['email'] ?? '',
            'student_id' => $studentId,
            'verification_hash' => $verificationHash,
            'notice' => $emailSent 
                ? "An official password-protected Electronic Statement of Ballot has been sent to {$user['email']} (unlocked using your Student ID Number, e.g. 240114837)."
                : "Your official ballot has been cryptographically recorded."
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Submission Error']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download_receipt') {
    $user = requireApiVoter();
    if (!$user['has_voted']) {
        http_response_code(400);
        echo json_encode(['error' => 'You have not voted yet.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT position_id, candidate_id, created_at FROM votes WHERE voter_id = ?");
    $stmt->execute([$user['id']]);
    $voteRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $finalVotes = [];
    $voteTimestamp = date('Y-m-d H:i:s');
    foreach ($voteRows as $vr) {
        $finalVotes[$vr['position_id']] = $vr['candidate_id'];
        $voteTimestamp = $vr['created_at'];
    }

    $studentId = !empty($user['student_id']) ? trim($user['student_id']) : trim($user['username']);
    $verificationHash = hash('sha256', $user['id'] . '|' . $studentId . '|' . $voteTimestamp . '|' . json_encode($finalVotes));

    $cleanDob = !empty($user['date_of_birth']) ? preg_replace('/[^0-9]/', '', $user['date_of_birth']) : null;
    $selectionsDetails = getBallotSelectionsDetails($pdo, $finalVotes);
    $pdfContent = generateProtectedBallotReceiptPdf($user, $selectionsDetails, $voteTimestamp, $verificationHash, $studentId, $cleanDob);

    $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentId);
    $filename = "eStatement_Ballot_{$cleanId}.pdf";
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $pdfContent;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_profile') {
    $user = requireApiVoter();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $newName = trim($data['full_name'] ?? '');

    if (empty($newName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name cannot be empty.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $stmt->execute([$newName, $user['id']]);

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'profile') {
    $user = requireApiVoter();
    echo json_encode([
        'success' => true,
        'profile' => [
            'full_name' => $user['full_name'],
            'student_id' => $user['student_id'] ?? $user['username'],
            'email' => $user['email']
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'password_status') {
    $user = requireApiVoter();
    $lockStatus = checkVoterPasswordLockout($user);
    $failedAttempts = (int)($user['pwd_failed_attempts'] ?? 0);
    $remainingAttempts = max(0, 3 - $failedAttempts);

    echo json_encode([
        'success' => true,
        'is_locked' => $lockStatus['is_locked'],
        'remaining_seconds' => $lockStatus['remaining_seconds'],
        'remaining_attempts' => $remainingAttempts,
        'failed_attempts' => $failedAttempts,
        'lock_count' => $lockStatus['lock_count']
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verify_password') {
    $user = requireApiVoter();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $password = $data['password'] ?? '';

    if (empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Password is required for verification.']);
        exit;
    }

    $result = verifyVoterPasswordWithLockout($pdo, $user, $password);
    http_response_code($result['http_code'] ?? ($result['success'] ? 200 : 400));
    unset($result['http_code']);
    echo json_encode($result);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
