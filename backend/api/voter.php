<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/encryption.php';

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
        echo json_encode(['success' => true, 'timestamp' => $voteTimestamp]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Submission Error']);
    }
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

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
