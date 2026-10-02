<?php
// database/setup.php — Database Initialization Script
require_once __DIR__ . '/../config/database.php';

echo "<h2>Online Voting System — MySQL Database Setup</h2>";

try {
    $pdo = getDBConnection();
    $schemaFile = __DIR__ . '/schema.sql';
    
    if (!file_exists($schemaFile)) {
        die("Error: schema.sql file not found.");
    }
    
    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);
    
    // Explicitly update default user hashes to guarantee login success
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $voterHash = password_hash('voter123', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin' OR email = 'admin@tomorrowvote.com'")->execute([$adminHash]);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE role = 'voter'")->execute([$voterHash]);

    echo "<p style='color: green; font-weight: bold;'>✔ Database 'voting_db' and tables initialized successfully!</p>";
    echo "<h3>Default Accounts:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> Email: <code>admin@tomorrowvote.com</code> | Password: <code>admin123</code></li>";
    echo "<li><strong>Voter:</strong> Email: <code>voter1@tomorrowvote.com</code> | Password: <code>voter123</code></li>";
    echo "</ul>";
    echo "<p><a href='../login.php'>Go to Login Screen</a></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✖ Setup failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
