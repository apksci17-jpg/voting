<?php
// config/database.php — MySQL Database Connection Configuration & Global Timezone Settings

// Globally configure Asia/Manila (Philippine Standard Time - UTC+8) for all voter and admin operations
date_default_timezone_set('Asia/Manila');

// Database Credentials (Auto-detects Railway, Docker, or local XAMPP environment)
$dbHost = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$dbPass = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
$dbName = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'voting';
$dbPort = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);
define('DB_PORT', $dbPort);

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Try connecting directly to configured database
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Synchronize MySQL session timezone with Asia/Manila (+08:00) safely
        try {
            $pdo->exec("SET time_zone = '+08:00';");
        } catch (Exception $tzEx) {
            // Some shared hosts restrict SET time_zone; fallback to PHP date_default_timezone_set
        }
    } catch (PDOException $e) {
        // If on localhost and database doesn't exist, try creating it automatically
        if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
            try {
                $rootPdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                
                // Re-connect to voting_db
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Run initial schema import
                $schemaFile = __DIR__ . '/../database/schema_infinityfree.sql';
                if (!file_exists($schemaFile)) {
                    $schemaFile = __DIR__ . '/../database/schema.sql';
                }
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
                return $pdo;
            } catch (Exception $ex) {
                // Ignore and proceed to user-friendly error output below
            }
        }
        
        die("<div style='font-family:sans-serif; padding:30px; max-width:640px; margin:40px auto; background:#fee2e2; border:2px solid #ef4444; border-radius:12px; color:#991b1b; box-shadow:0 4px 15px rgba(0,0,0,0.1);'>"
            . "<h2 style='margin-top:0; color:#dc2626;'>⚠️ Database Connection Error</h2>"
            . "<p style='font-size:14px;'>Could not connect to the MySQL database with the credentials in <code>config/database.php</code>.</p>"
            . "<div style='background:#ffffff; padding:12px 16px; border-radius:8px; border:1px solid #fca5a5; font-family:monospace; font-size:12px; word-break:break-all;'>"
            . htmlspecialchars($e->getMessage())
            . "</div>"
            . "<hr style='border:0; border-top:1px solid #fca5a5; margin:18px 0;'>"
            . "<p style='font-size:13px; line-height:1.5; color:#7f1d1d;'><strong>If using Web Hosting (InfinityFree / cPanel / Free.nf):</strong><br>"
            . "1. Open <code>config/database.php</code>.<br>"
            . "2. Replace <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, and <code>DB_NAME</code> with your hosting MySQL details found in your hosting control panel.</p>"
            . "</div>");
    }

    return $pdo;
}
