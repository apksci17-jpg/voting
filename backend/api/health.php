<?php
// backend/api/health.php - Cloud deployment diagnostic & healthcheck
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$response = [
    'version' => '2026-10-03-01',
    'status' => 'healthy',
    'php_version' => PHP_VERSION,
    'timestamp' => date('Y-m-d H:i:s'),
    'env_checks' => [
        'has_MYSQL_URL' => !empty(getenv('MYSQL_URL')),
        'has_MYSQLHOST' => !empty(getenv('MYSQLHOST')),
        'has_MYSQLUSER' => !empty(getenv('MYSQLUSER')),
        'has_MYSQLDATABASE' => !empty(getenv('MYSQLDATABASE')),
        'has_PORT' => !empty(getenv('PORT')),
        'PORT' => getenv('PORT') ?: 'default (8080)'
    ]
];

// Test Database Connection safely without dying
try {
    require_once __DIR__ . '/../config/database.php';
    
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 3
    ]);

    $response['database'] = [
        'connection' => 'SUCCESS',
        'host' => DB_HOST,
        'database_name' => DB_NAME,
        'port' => DB_PORT
    ];

    $tablesStmt = $pdo->query("SHOW TABLES");
    $response['database']['tables'] = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('users', $response['database']['tables'])) {
        $voterCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter'");
        $response['database']['total_voters'] = (int)$voterCountStmt->fetchColumn();
        
        $adminCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $response['database']['total_admins'] = (int)$adminCountStmt->fetchColumn();

        $realEmailCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter' AND email LIKE '%@gmail.com'");
        $response['database']['voters_with_real_email'] = (int)$realEmailCountStmt->fetchColumn();
    }

    if (in_array('election_settings', $response['database']['tables'])) {
        $syncStmt = $pdo->query("SELECT setting_value FROM election_settings WHERE setting_key = 'masterlist_voters_synced_v1'");
        $response['database']['masterlist_synced'] = ($syncStmt && $syncStmt->fetchColumn() === '1');
    }

} catch (Throwable $e) {
    $response['database'] = [
        'connection' => 'FAILED',
        'error_message' => $e->getMessage(),
        'configured_host' => defined('DB_HOST') ? DB_HOST : 'undefined',
        'configured_db' => defined('DB_NAME') ? DB_NAME : 'undefined',
        'tip' => 'Make sure MySQL variables (MYSQL_URL or MYSQLHOST) are referenced in the Railway Variables tab.'
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
