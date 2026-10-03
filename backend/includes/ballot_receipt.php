<?php
// includes/ballot_receipt.php — Protected Ballot Receipt PDF Generator for Tomorrow Vote

require_once __DIR__ . '/fpdf_protection.php';

if (!function_exists('sanitizeReceiptText')) {
    function sanitizeReceiptText($str) {
        if ($str === null) return '';
        $str = htmlspecialchars_decode((string)$str, ENT_QUOTES);
        $replacements = [
            "\xE2\x80\x98" => "'",
            "\xE2\x80\x99" => "'",
            "\xE2\x80\x9C" => '"',
            "\xE2\x80\x9D" => '"',
            "\xE2\x80\x93" => "-",
            "\xE2\x80\x94" => "-",
            "\xE2\x80\xA2" => "*",
            "\xE2\x80\xA6" => "...",
        ];
        $str = strtr($str, $replacements);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $str);
            if ($converted !== false) return $converted;
        }
        return utf8_decode($str);
    }
}

class ProtectedBallotReceiptPDF extends FPDF_Protection {
    public $voterName = '';
    public $studentId = '';
    public $timestamp = '';
    public $verificationHash = '';

    function Header() {
        // Deep Navy Top Header Bar
        $this->SetFillColor(11, 34, 77); // #0b224d
        $this->Rect(0, 0, $this->w, 30, 'F');

        // Gold/Cyan accent line underneath
        $this->SetFillColor(16, 89, 214); // #1059d6
        $this->Rect(0, 30, $this->w, 2, 'F');

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetXY(10, 5);
        $this->Cell(0, 6, sanitizeReceiptText('BESTLINK COLLEGE OF THE PHILIPPINES'), 0, 1, 'C');

        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(191, 219, 254); // Light blue
        $this->Cell(0, 5, sanitizeReceiptText('SUPREME STUDENT COUNCIL ELECTIONS 2026'), 0, 1, 'C');

        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(254, 240, 138); // Soft Gold
        $this->Cell(0, 5, sanitizeReceiptText('OFFICIAL CERTIFIED VOTER BALLOT RECEIPT & AUDIT RECORD'), 0, 1, 'C');

        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetDrawColor(203, 213, 225);
        $this->Line(12, $this->GetY(), $this->w - 12, $this->GetY());
        $this->Ln(2);

        $this->SetFont('Helvetica', 'I', 7.5);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 4, sanitizeReceiptText('Bestlink College of the Philippines — Commission on Student Elections (COMELEC)'), 0, 1, 'C');
        $this->Cell(0, 4, sanitizeReceiptText('Confidential Encrypted Document — Unlock Password: Voter Student ID | Page ' . $this->PageNo()), 0, 0, 'C');
    }
}

/**
 * Fetches detailed position and candidate names for ballot selections.
 *
 * @param PDO $pdo
 * @param array $finalVotes Associative array: [position_id => candidate_id]
 * @return array List of positions and candidate details
 */
function getBallotSelectionsDetails($pdo, $finalVotes) {
    if (empty($finalVotes)) {
        return [];
    }

    $positions = [];
    foreach ($finalVotes as $posId => $candId) {
        $posId = (int)$posId;
        $candId = (int)$candId;

        // Fetch position
        $pStmt = $pdo->prepare("SELECT id, position_name, display_order FROM positions WHERE id = ?");
        $pStmt->execute([$posId]);
        $pos = $pStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch candidate
        $cStmt = $pdo->prepare("SELECT id, candidate_name, year_level, platform FROM candidates WHERE id = ?");
        $cStmt->execute([$candId]);
        $cand = $cStmt->fetch(PDO::FETCH_ASSOC);

        $positions[] = [
            'position_id' => $posId,
            'position_name' => $pos ? $pos['position_name'] : ("Position #" . $posId),
            'display_order' => $pos ? (int)$pos['display_order'] : 999,
            'candidate_id' => $candId,
            'candidate_name' => $cand ? $cand['candidate_name'] : ("Candidate #" . $candId),
            'year_level' => $cand ? $cand['year_level'] : '',
            'platform' => $cand ? $cand['platform'] : ''
        ];
    }

    usort($positions, function($a, $b) {
        return $a['display_order'] - $b['display_order'];
    });

    return $positions;
}

/**
 * Generates a password-protected PDF binary string of the voter's official ballot.
 *
 * @param array $user Voter user record from DB
 * @param array $selectionsDetails Output of getBallotSelectionsDetails()
 * @param string $voteTimestamp Format: Y-m-d H:i:s
 * @param string $verificationHash SHA-256 integrity hash
 * @param string $pdfPassword Password to protect the document (voter's Student ID)
 * @return string Binary PDF content
 */
function generateProtectedBallotReceiptPdf($user, $selectionsDetails, $voteTimestamp, $verificationHash, $pdfPassword) {
    $pdf = new ProtectedBallotReceiptPDF('P', 'mm', 'A4');
    $pdf->voterName = $user['full_name'] ?? 'Student Voter';
    $pdf->studentId = $user['student_id'] ?? ($user['username'] ?? 'N/A');
    $pdf->timestamp = $voteTimestamp;
    $pdf->verificationHash = $verificationHash;

    // Apply password protection: allow viewing and printing, lock copying/modifying
    $pdf->SetProtection(['print'], $pdfPassword);

    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 24);
    $pdf->AddPage();

    $pdf->SetY(38);

    // --- Section 1: Voter Identity & Ballot Metadata Card ---
    $pdf->SetFillColor(248, 250, 252); // #f8fafc
    $pdf->SetDrawColor(203, 213, 225); // #cbd5e1
    $pdf->RoundedRect(12, 36, 186, 36, 3, 'DF');

    $pdf->SetXY(16, 39);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(45, 4, sanitizeReceiptText('VOTER FULL NAME'), 0, 0);
    $pdf->Cell(45, 4, sanitizeReceiptText('STUDENT ID / NUMBER'), 0, 0);
    $pdf->Cell(50, 4, sanitizeReceiptText('REGISTERED EMAIL'), 0, 0);
    $pdf->Cell(40, 4, sanitizeReceiptText('SUBMISSION TIME'), 0, 1);

    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(15, 23, 42); // Dark slate
    $pdf->Cell(45, 6, sanitizeReceiptText($pdf->voterName), 0, 0);
    $pdf->SetTextColor(16, 89, 214); // Royal Blue
    $pdf->Cell(45, 6, sanitizeReceiptText($pdf->studentId), 0, 0);
    $pdf->SetFont('Helvetica', '', 9.5);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(50, 6, sanitizeReceiptText($user['email'] ?? 'N/A'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(40, 6, sanitizeReceiptText($voteTimestamp), 0, 1);

    $pdf->Ln(2);
    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(22, 101, 52); // Green
    $pdf->Cell(90, 5, sanitizeReceiptText('STATUS: OFFICIAL BALLOT CAST & VERIFIED'), 0, 0);
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(180, 83, 9); // Amber
    $pdf->Cell(90, 5, sanitizeReceiptText('ENCRYPTED: Password Protected with Student ID'), 0, 1, 'R');

    $pdf->Ln(8);

    // --- Section 2: Ballot Selections Table ---
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(11, 34, 77);
    $pdf->Cell(0, 6, sanitizeReceiptText('OFFICIAL VOTED CHOICES'), 0, 1);

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 4, sanitizeReceiptText('The following list contains your certified candidate votes recorded for each contested position:'), 0, 1);
    $pdf->Ln(2);

    // Table Header
    $pdf->SetFillColor(11, 34, 77); // Deep Blue
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->Cell(12, 7, '#', 0, 0, 'C', true);
    $pdf->Cell(62, 7, sanitizeReceiptText('POSITION'), 0, 0, 'L', true);
    $pdf->Cell(64, 7, sanitizeReceiptText('SELECTED CANDIDATE'), 0, 0, 'L', true);
    $pdf->Cell(48, 7, sanitizeReceiptText('ACADEMIC YEAR / DETAILS'), 0, 1, 'L', true);

    // Table Rows
    $pdf->SetFont('Helvetica', '', 9);
    $fill = false;
    $idx = 1;

    foreach ($selectionsDetails as $row) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetDrawColor(226, 232, 240);

        // Row Index
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(12, 8, $idx++, 'B', 0, 'C', true);

        // Position Name
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(62, 8, sanitizeReceiptText($row['position_name']), 'B', 0, 'L', true);

        // Candidate Name
        $pdf->SetTextColor(16, 89, 214);
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell(64, 8, sanitizeReceiptText($row['candidate_name']), 'B', 0, 'L', true);

        // Year Level / Platform
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('Helvetica', '', 8.5);
        $detail = !empty($row['year_level']) ? $row['year_level'] : 'Bestlink Candidate';
        $pdf->Cell(48, 8, sanitizeReceiptText($detail), 'B', 1, 'L', true);

        $fill = !$fill;
    }

    $pdf->Ln(6);

    // --- Section 3: Cryptographic Integrity Box ---
    $pdf->SetFillColor(241, 245, 249); // #f1f5f9
    $pdf->SetDrawColor(203, 213, 225);
    $boxY = $pdf->GetY();
    $pdf->RoundedRect(12, $boxY, 186, 28, 3, 'DF');

    $pdf->SetXY(16, $boxY + 3);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(11, 34, 77);
    $pdf->Cell(0, 4, sanitizeReceiptText('CRYPTOGRAPHIC VERIFICATION SEAL & TAMPER-EVIDENT AUDIT HASH'), 0, 1);

    $pdf->SetX(16);
    $pdf->SetFont('Courier', 'B', 8);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(0, 5, $verificationHash, 0, 1);

    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->MultiCell(178, 3.5, sanitizeReceiptText("This document is an official encrypted record of your vote for the 2026 Student Council Elections. Individual ballot payloads are encrypted via AES-256-CBC and committed to the immutable database. This receipt is recognized by COMELEC as valid proof of voting."), 0, 'L');

    $pdf->Ln(6);

    // Return binary string
    return $pdf->Output('S', 'ballot_receipt.pdf');
}
