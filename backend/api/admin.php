<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pdf_export.php';

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'dashboard') {
    requireApiAdmin();
    $stats = getAdminDashboardStats();
    $status = getElectionSetting('voting_status', 'CLOSED');
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'status' => $status
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set_status') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $newStatus = ($data['status'] === 'OPEN') ? 'OPEN' : 'CLOSED';
    setElectionSetting('voting_status', $newStatus);
    setElectionSetting('manual_override', $newStatus === 'OPEN' ? 'MANUAL_OPEN' : 'MANUAL_CLOSED');
    echo json_encode(['success' => true, 'status' => $newStatus]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset_records') {
    requireApiAdmin();
    $archivedPdfPath = generateElectionPDFReport(false);
    $pdo->exec("DELETE FROM votes");
    $pdo->exec("UPDATE users SET has_voted = 0 WHERE role = 'voter'");
    setElectionSetting('voting_status', 'CLOSED');
    echo json_encode(['success' => true, 'archived' => basename($archivedPdfPath)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'backups') {
    requireApiAdmin();
    $backupDir = __DIR__ . '/../backups';
    $backupFiles = [];
    if (file_exists($backupDir)) {
        $rawFiles = glob($backupDir . '/*.pdf');
        if (!empty($rawFiles)) {
            usort($rawFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
            foreach ($rawFiles as $rf) {
                $backupFiles[] = [
                    'filename' => basename($rf),
                    'size' => round(filesize($rf) / 1024, 1) . ' KB',
                    'time' => date('M j, Y - h:i A', filemtime($rf))
                ];
            }
        }
    }
    echo json_encode(['success' => true, 'backups' => $backupFiles]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download_pdf') {
    requireApiAdmin();
    generateElectionPDFReport(true);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download_backup') {
    requireApiAdmin();
    $filename = basename($_GET['file'] ?? '');
    if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.pdf$/i', $filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file name.']);
        exit;
    }
    
    $backupDir = realpath(__DIR__ . '/../backups');
    $filePath = realpath(__DIR__ . '/../backups/' . $filename);
    
    if (!$filePath || !$backupDir || strpos($filePath, $backupDir) !== 0 || !file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Archived report file not found.']);
        exit;
    }
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    readfile($filePath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'results') {
    requireApiAdmin();
    $results = getOfficialElectionResults();
    $status = getElectionSetting('voting_status', 'CLOSED');
    
    // Check if 100% turnout
    $stmt = $pdo->query("SELECT COUNT(*) as total_voters, SUM(CASE WHEN has_voted=1 THEN 1 ELSE 0 END) as voted FROM users WHERE role='voter'");
    $turnout = $stmt->fetch(PDO::FETCH_ASSOC);
    $canDeclare = ($status === 'CLOSED' || ($turnout['total_voters'] > 0 && $turnout['total_voters'] == $turnout['voted']));

    echo json_encode(['success' => true, 'results' => $results, 'can_declare' => $canDeclare]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'candidates') {
    requireApiAdmin();
    $stmt = $pdo->query("
        SELECT c.*, p.position_name, p.display_order
        FROM candidates c
        JOIN positions p ON c.position_id = p.id
        ORDER BY p.display_order ASC, c.candidate_name ASC
    ");
    echo json_encode(['success' => true, 'candidates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'positions') {
    requireApiAdmin();
    $stmt = $pdo->query("SELECT * FROM positions ORDER BY display_order ASC, id ASC");
    echo json_encode(['success' => true, 'positions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_candidate') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $candidateName = trim($data['candidate_name'] ?? '');
    $positionId = (int)($data['position_id'] ?? 0);
    $yearLevel = trim($data['year_level'] ?? '1st Year');
    $platform = trim($data['platform'] ?? '');
    $profileImage = 'default.svg';

    if (empty($candidateName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Candidate name is required.']);
        exit;
    }
    if ($positionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid position is required.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../../frontend/assets/uploads';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($data['profile_image_base64']) && preg_match('/^data:image\/(\w+);base64,/', $data['profile_image_base64'], $matches)) {
        $ext = strtolower($matches[1]);
        if (in_array($ext, ['jpeg', 'jpg', 'png', 'webp', 'svg+xml', 'svg'])) {
            if ($ext === 'jpeg') $ext = 'jpg';
            if ($ext === 'svg+xml') $ext = 'svg';
            $imgData = substr($data['profile_image_base64'], strpos($data['profile_image_base64'], ',') + 1);
            $decoded = base64_decode($imgData);
            if ($decoded !== false) {
                $newFilename = 'cand_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                file_put_contents($uploadDir . '/' . $newFilename, $decoded);
                $profileImage = $newFilename;
            }
        }
    } elseif (!empty($_FILES['profile_image_file']) && $_FILES['profile_image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            $newFilename = 'cand_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_image_file']['tmp_name'], $uploadDir . '/' . $newFilename)) {
                $profileImage = $newFilename;
            }
        }
    } elseif (!empty($data['profile_image'])) {
        $profileImage = basename($data['profile_image']);
    }

    $stmt = $pdo->prepare("INSERT INTO candidates (position_id, candidate_name, year_level, platform, profile_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$positionId, $candidateName, $yearLevel, $platform, $profileImage]);
    $newId = (int)$pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $newId, 'candidate_name' => $candidateName]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_candidate') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $id = (int)($data['id'] ?? 0);
    $candidateName = trim($data['candidate_name'] ?? '');
    $positionId = (int)($data['position_id'] ?? 0);
    $yearLevel = trim($data['year_level'] ?? '1st Year');
    $platform = trim($data['platform'] ?? '');

    if ($id <= 0 || empty($candidateName) || $positionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Candidate ID, name, and position are required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Candidate not found.']);
        exit;
    }

    $profileImage = $existing['profile_image'];
    $uploadDir = __DIR__ . '/../../frontend/assets/uploads';

    if (!empty($data['profile_image_base64']) && preg_match('/^data:image\/(\w+);base64,/', $data['profile_image_base64'], $matches)) {
        $ext = strtolower($matches[1]);
        if (in_array($ext, ['jpeg', 'jpg', 'png', 'webp', 'svg+xml', 'svg'])) {
            if ($ext === 'jpeg') $ext = 'jpg';
            if ($ext === 'svg+xml') $ext = 'svg';
            $imgData = substr($data['profile_image_base64'], strpos($data['profile_image_base64'], ',') + 1);
            $decoded = base64_decode($imgData);
            if ($decoded !== false) {
                $newFilename = 'cand_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                file_put_contents($uploadDir . '/' . $newFilename, $decoded);
                $profileImage = $newFilename;
            }
        }
    } elseif (!empty($_FILES['profile_image_file']) && $_FILES['profile_image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            $newFilename = 'cand_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_image_file']['tmp_name'], $uploadDir . '/' . $newFilename)) {
                $profileImage = $newFilename;
            }
        }
    } elseif (isset($data['profile_image']) && !empty($data['profile_image'])) {
        $profileImage = basename($data['profile_image']);
    }

    $update = $pdo->prepare("UPDATE candidates SET position_id = ?, candidate_name = ?, year_level = ?, platform = ?, profile_image = ? WHERE id = ?");
    $update->execute([$positionId, $candidateName, $yearLevel, $platform, $profileImage, $id]);

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_candidate') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($data['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'voters') {
    requireApiAdmin();
    $stmt = $pdo->query("
        SELECT id, username, email, full_name, student_id, has_voted, created_at, phone, date_of_birth, grade_level, section 
        FROM users 
        WHERE role = 'voter' 
        ORDER BY has_voted DESC, full_name ASC
    ");
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($voters);
    $voted = 0;
    foreach ($voters as &$v) {
        $v['has_voted'] = (bool)$v['has_voted'];
        if ($v['has_voted']) $voted++;
    }
    unset($v);

    echo json_encode([
        'success' => true,
        'voters' => $voters,
        'metrics' => [
            'total' => $total,
            'voted' => $voted,
            'pending' => $total - $voted,
            'turnout_pct' => $total > 0 ? round(($voted / $total) * 100, 1) : 0
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_voter') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $fullName = trim($data['full_name'] ?? '');
    $studentId = trim($data['student_id'] ?? '');
    $username = trim($data['username'] ?? '');
    if (empty($username)) $username = $studentId;
    $email = trim($data['email'] ?? '');
    if (empty($email)) $email = strtolower($studentId) . '@tomorrowvote.com';
    $password = $data['password'] ?? '';
    if (empty($password)) $password = $studentId;
    $gradeLevel = trim($data['grade_level'] ?? '1st Year');
    $section = trim($data['section'] ?? '');
    $phone = trim($data['phone'] ?? '');

    if (empty($fullName) || empty($studentId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Full Name and Student ID are required.']);
        exit;
    }

    $check = $pdo->prepare("SELECT id, username, student_id, email FROM users WHERE student_id = ? OR username = ? OR email = ? LIMIT 1");
    $check->execute([$studentId, $username, $email]);
    $dup = $check->fetch(PDO::FETCH_ASSOC);
    if ($dup) {
        http_response_code(409);
        $field = ($dup['student_id'] === $studentId) ? 'Student ID' : (($dup['username'] === $username) ? 'Username' : 'Email');
        echo json_encode(['success' => false, 'error' => "A voter with this {$field} is already registered."]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, student_id, has_voted, grade_level, section, phone) VALUES (?, ?, ?, 'voter', ?, ?, 0, ?, ?, ?)");
    $stmt->execute([$username, $email, $hash, $fullName, $studentId, $gradeLevel, $section, $phone]);
    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $newId,
        'student_id' => $studentId,
        'full_name' => $fullName
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_voter') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $id = (int)($data['id'] ?? 0);
    $fullName = trim($data['full_name'] ?? '');
    $studentId = trim($data['student_id'] ?? '');
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $gradeLevel = trim($data['grade_level'] ?? '');
    $section = trim($data['section'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $newPassword = $data['password'] ?? '';

    if ($id <= 0 || empty($fullName) || empty($studentId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Voter ID, Full Name, and Student ID are required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'voter'");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Voter account not found.']);
        exit;
    }

    if (empty($username)) $username = $studentId;
    if (empty($email)) $email = strtolower($studentId) . '@tomorrowvote.com';

    $check = $pdo->prepare("SELECT id FROM users WHERE (student_id = ? OR username = ? OR email = ?) AND id != ? LIMIT 1");
    $check->execute([$studentId, $username, $email, $id]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Another account already uses this Student ID, Username, or Email.']);
        exit;
    }

    if (!empty($newPassword)) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET full_name = ?, student_id = ?, username = ?, email = ?, grade_level = ?, section = ?, phone = ?, password_hash = ? WHERE id = ?");
        $update->execute([$fullName, $studentId, $username, $email, $gradeLevel, $section, $phone, $hash, $id]);
    } else {
        $update = $pdo->prepare("UPDATE users SET full_name = ?, student_id = ?, username = ?, email = ?, grade_level = ?, section = ?, phone = ? WHERE id = ?");
        $update->execute([$fullName, $studentId, $username, $email, $gradeLevel, $section, $phone, $id]);
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_voter') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($data['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id, role, full_name FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userToDelete || $userToDelete['role'] !== 'voter') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Only voter accounts can be deleted.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $delVotes = $pdo->prepare("DELETE FROM votes WHERE voter_id = ?");
        $delVotes->execute([$id]);

        $delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delUser->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error deleting voter.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset_voter_ballot') {
    requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($data['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id, role, has_voted FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $voter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voter || $voter['role'] !== 'voter') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid voter account.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare("DELETE FROM votes WHERE voter_id = ?");
        $del->execute([$id]);

        $up = $pdo->prepare("UPDATE users SET has_voted = 0 WHERE id = ?");
        $up->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error resetting ballot.']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
