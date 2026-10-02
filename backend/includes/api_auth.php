<?php
// backend/includes/api_auth.php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    // Fallback for Apache / FastCGI / LiteSpeed environment variables
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }
    // Fallback for direct browser downloads or media streaming
    if (!empty($_GET['token']) && is_string($_GET['token'])) {
        return trim($_GET['token']);
    }
    if (!empty($_POST['token']) && is_string($_POST['token'])) {
        return trim($_POST['token']);
    }
    return null;
}

function getApiUser() {
    $token = getBearerToken();
    if (!$token) return null;
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE session_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function requireApiVoter() {
    $user = getApiUser();
    if (!$user || $user['role'] !== 'voter') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized or not a voter.']);
        exit;
    }
    return $user;
}

function requireApiAdmin() {
    $user = getApiUser();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized or not an admin.']);
        exit;
    }
    return $user;
}
?>
