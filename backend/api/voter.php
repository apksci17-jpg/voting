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

    // Verify password if provided as biometric fallback
    $password = $data['password'] ?? '';
    if (!empty($password) && !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Incorrect voter password. Verification failed.']);
        exit;
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
                ? "An official password-protected Electronic Statement of Ballot has been sent to {$user['email']} (unlocked using your Student ID)."
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verify_password') {
    $user = requireApiVoter();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $password = $data['password'] ?? '';

    if (empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Password is required for verification.']);
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Incorrect password. Verification failed.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
