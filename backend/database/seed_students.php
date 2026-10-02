<?php
// database/seed_students.php — Seed valid students with student numbers and generated passwords
require_once __DIR__ . '/../config/database.php';

$validStudents = [
    ['name' => 'Alvarez, Moises Ker C.', 'student_number' => '240104785', 'first_name' => 'Moises'],
    ['name' => 'Antonio, Jose Miguel P.', 'student_number' => '240114524', 'first_name' => 'Jose'],
    ['name' => 'Avingona, Gabriel Joshua', 'student_number' => '240104619', 'first_name' => 'Gabriel'],
    ['name' => 'Balaguer, Jastin Roi G.', 'student_number' => '240111324', 'first_name' => 'Jastin'],
    ['name' => 'Baria, Presler A.', 'student_number' => '240112382', 'first_name' => 'Presler'],
    ['name' => 'Bomitivo, Joanne C.', 'student_number' => '240114322', 'first_name' => 'Joanne'],
    ['name' => 'Calusin, Justin', 'student_number' => '240110711', 'first_name' => 'Justin'],
    ['name' => 'Castulo, Renz Van S.', 'student_number' => '250100040', 'first_name' => 'Renz'],
    ['name' => 'Coton, Majohn J.', 'student_number' => '240114865', 'first_name' => 'Majohn'],
    ['name' => 'Cruz, Ronald Jay', 'student_number' => '240116136', 'first_name' => 'Ronald'],
    ['name' => 'Elcarte, Hannah Kate M.', 'student_number' => '240108641', 'first_name' => 'Hannah'],
    ['name' => 'Fernandez, Jean Ashley T.', 'student_number' => '240110350', 'first_name' => 'Jean'],
    ['name' => 'Gigante, Angelo Yuan O.', 'student_number' => '240102182', 'first_name' => 'Angelo'],
    ['name' => 'Gonzaga, Diego Rey D.', 'student_number' => '240111136', 'first_name' => 'Diego'],
    ['name' => 'Guzman, Angeline O.', 'student_number' => '240115034', 'first_name' => 'Angeline'],
    ['name' => 'Jalayahay, Daisy B.', 'student_number' => '240104951', 'first_name' => 'Daisy'],
    ['name' => 'Kimpan, Kim Jafet E.', 'student_number' => '240112280', 'first_name' => 'Kim'],
    ['name' => 'Laroa, John Cristian B.', 'student_number' => '240100999', 'first_name' => 'John'],
    ['name' => 'Pedroso, Keith Anton B.', 'student_number' => '240100581', 'first_name' => 'Keith'],
    ['name' => 'Rañola, Cassandramher S.', 'student_number' => '240114837', 'first_name' => 'Cassandramher'],
    ['name' => 'Rigor, Jazel Venice A.', 'student_number' => '240114417', 'first_name' => 'Jazel'],
    ['name' => 'Sampiano, Jenny A.', 'student_number' => '240110713', 'first_name' => 'Jenny'],
    ['name' => 'Soriao, Mark Gerald C.', 'student_number' => '240109916', 'first_name' => 'Mark'],
    ['name' => 'Sumagaysay, Annalee T.', 'student_number' => '240115320', 'first_name' => 'Annalee'],
    ['name' => 'Sy, Franz Clarence E.', 'student_number' => '240100841', 'first_name' => 'Franz'],
    ['name' => 'Tarala, Airadale', 'student_number' => '240116149', 'first_name' => 'Airadale'],
    ['name' => 'Tenorio, Emmanuel Robert C.', 'student_number' => '240109597', 'first_name' => 'Emmanuel'],
    ['name' => 'Tomelden, Jhewelle C.', 'student_number' => '240109409', 'first_name' => 'Jhewelle'],
    ['name' => 'Tribo, Almira A.', 'student_number' => '240111848', 'first_name' => 'Almira'],
    ['name' => 'Visto, Adrian P.', 'student_number' => '240104214', 'first_name' => 'Adrian'],
    ['name' => 'Obra, Joshua R.', 'student_number' => '240105182', 'first_name' => 'Joshua'],
];

try {
    $pdo = getDBConnection();
    
    // 1. Remove all old voters who do not have a valid student number or are synthetic
    $validStudentNumbers = array_column($validStudents, 'student_number');
    $placeholders = implode(',', array_fill(0, count($validStudentNumbers), '?'));
    
    // Delete non-admin users not in this valid student numbers list
    $deleteSql = "DELETE FROM users WHERE role = 'voter' AND student_id NOT IN ($placeholders) AND username NOT IN ($placeholders)";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute(array_merge($validStudentNumbers, $validStudentNumbers));

    // 2. Insert or update each valid student with their automatically generated password
    $processed = 0;
    foreach ($validStudents as $stu) {
        $studentNum = trim($stu['student_number']);
        $fullName = trim($stu['name']);
        $firstName = trim($stu['first_name']);
        
        // Generated Password: First Name + Student Number (e.g. Moises240104785)
        $autoPassword = $firstName . $studentNum;
        $passwordHash = password_hash($autoPassword, PASSWORD_DEFAULT);
        $email = $studentNum . '@student.bcp.edu.ph';
        $username = $studentNum;

        // Check if user exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ? OR username = ? LIMIT 1");
        $checkStmt->execute([$studentNum, $studentNum]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $upStmt = $pdo->prepare("UPDATE users SET full_name = ?, student_id = ?, username = ?, email = ?, password_hash = ? WHERE id = ?");
            $upStmt->execute([$fullName, $studentNum, $username, $email, $passwordHash, $existing['id']]);
        } else {
            $inStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name, student_id, has_voted) VALUES (?, ?, ?, 'voter', ?, ?, 0)");
            $inStmt->execute([$username, $email, $passwordHash, $fullName, $studentNum]);
        }
        $processed++;
    }

    echo "Successfully seeded valid students with auto-generated passwords!\n";
    echo "Total Valid Students Processed: $processed\n";
} catch (Exception $e) {
    echo "Error seeding students: " . $e->getMessage() . "\n";
}
