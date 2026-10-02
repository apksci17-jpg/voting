<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_auth.php';

$action = $_GET['action'] ?? '';
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR student_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$username, $username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
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
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
    }
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
