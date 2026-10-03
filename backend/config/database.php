<?php
// config/database.php — MySQL Database Connection Configuration & Global Timezone Settings

// Globally configure Asia/Manila (Philippine Standard Time - UTC+8) for all voter and admin operations
date_default_timezone_set('Asia/Manila');

// Database Credentials (Auto-detects Railway MYSQL_URL, environment variables, or local XAMPP)
if ($mysqlUrl = getenv('MYSQL_URL')) {
    $parts = parse_url($mysqlUrl);
    $dbHost = $parts['host'] ?? 'localhost';
    $dbPort = $parts['port'] ?? 3306;
    $dbUser = $parts['user'] ?? 'root';
    $dbPass = $parts['pass'] ?? '';
    $dbName = ltrim($parts['path'] ?? 'railway', '/');
} else {
    $dbHost = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
    $dbUser = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
    $dbPass = (getenv('MYSQLPASSWORD') !== false && getenv('MYSQLPASSWORD') !== '') ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    $dbName = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'voting';
    $dbPort = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
}

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);
define('DB_PORT', $dbPort);

/**
 * Auto-synchronize masterlist voters into the MySQL database.
 * Updates emails of existing voters to their real Gmail addresses from Excel.
 * Inserts any new voters from the masterlist.
 * Runs automatically on Railway/cloud and local deployments.
 */
function autoSyncMasterlistVoters($pdo) {
    static $alreadyRan = false;
    if ($alreadyRan) return;

    try {
        // Ensure users table exists
        $tblCheck = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($tblCheck->rowCount() === 0) return;

        // Ensure election_settings table exists
        $setCheck = $pdo->query("SHOW TABLES LIKE 'election_settings'");
        if ($setCheck->rowCount() === 0) return;

        // Check if masterlist was already synced to avoid redundant overhead
        $stmtSetting = $pdo->prepare("SELECT setting_value FROM election_settings WHERE setting_key = 'masterlist_voters_synced_v1'");
        $stmtSetting->execute();
        $isSynced = $stmtSetting->fetchColumn();
        if ($isSynced === '1') {
            $alreadyRan = true;
            return;
        }

        $masterlistFile = __DIR__ . '/../includes/voter_masterlist.php';
        if (!file_exists($masterlistFile)) return;
        $masterlist = require $masterlistFile;
        if (!is_array($masterlist) || empty($masterlist)) return;

        $findStmt = $pdo->prepare("SELECT id, username, student_id, email, full_name FROM users WHERE student_id = ? OR email = ? LIMIT 1");
        $updateEmailStmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ? WHERE id = ?");
        $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, student_id, has_voted, phone, date_of_birth, grade_level, section) VALUES (?, ?, ?, 'voter', ?, ?, 0, ?, ?, ?, ?)");

        foreach ($masterlist as $voter) {
            $studentId = trim($voter['student_id']);
            $email = trim($voter['email']);
            $fullName = trim($voter['full_name']);
            $hash = $voter['password_hash'];
            $phone = $voter['phone'] ?? '+63 912 345 6789';
            $dob = $voter['date_of_birth'] ?? '2003-10-12';
            $grade = $voter['grade_level'] ?? '3rd Year';
            $section = $voter['section'] ?? 'BSIT 31008';

            $findStmt->execute([$studentId, $email]);
            $existing = $findStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // If email or full name differs, update it immediately
                if ($existing['email'] !== $email || $existing['full_name'] !== $fullName) {
                    $updateEmailStmt->execute([$email, $fullName, $existing['id']]);
                }
            } else {
                // Insert new voter
                $username = $studentId;
                $insertStmt->execute([$username, $email, $hash, $fullName, $studentId, $phone, $dob, $grade, $section]);
            }
        }

        // Record setting flag so it only runs once per database lifecycle
        $saveSetting = $pdo->prepare("INSERT INTO election_settings (setting_key, setting_value) VALUES ('masterlist_voters_synced_v1', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
        $saveSetting->execute();

        $alreadyRan = true;
    } catch (Exception $e) {
        error_log("autoSyncMasterlistVoters notice: " . $e->getMessage());
    }
}

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
        
        // Auto-initialize tables and seed data if users table does not exist (e.g. fresh Railway deploy)
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($check->rowCount() === 0) {
                $schemaFile = __DIR__ . '/../database/schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
            } else {
                // Ensure login_otp columns exist in users table
                $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'login_otp'");
                if ($colCheck && $colCheck->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN `login_otp` VARCHAR(10) DEFAULT NULL, ADD COLUMN `login_otp_expires_at` DATETIME DEFAULT NULL");
                }
            }

            // Auto-synchronize masterlist voters from Excel to database
            autoSyncMasterlistVoters($pdo);
        } catch (Exception $initEx) {
            // Proceed even if auto-init is skipped or restricted
        }

        // Synchronize MySQL session timezone with Asia/Manila (+08:00) safely
        try {
            $pdo->exec("SET time_zone = '+08:00';");
        } catch (Exception $tzEx) {
            // Some shared hosts restrict SET time_zone; fallback to PHP date_default_timezone_set
        }

        // Disable ONLY_FULL_GROUP_BY in session to support flexible analytical aggregations in MySQL 8.x
        try {
            $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        } catch (Exception $sqlModeEx) {
            // Ignore if restricted
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
        
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
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
