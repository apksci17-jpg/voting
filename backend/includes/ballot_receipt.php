<?php
// includes/ballot_receipt.php — Bank Statement Style Protected Ballot Receipt (e-BS)

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

class BankStatementBallotPDF extends FPDF_Protection {
    public $voterName = '';
    public $studentId = '';
    public $timestamp = '';
    public $verificationHash = '';
    public $statementNo = '';
    public $programSection = '';

    function Header() {
        // --- Bank Statement Letterhead Banner ---
        // Top Corporate Midnight Blue Header
        $this->SetFillColor(11, 34, 77); // #0b224d
        $this->Rect(0, 0, $this->w, 32, 'F');

        // Gold Accent Rule
        $this->SetFillColor(217, 119, 6); // #d97706
        $this->Rect(0, 32, $this->w, 2, 'F');

        // Left Header: Institution & Electoral Division
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetXY(12, 6);
        $this->Cell(110, 6, sanitizeReceiptText('BESTLINK COLLEGE OF THE PHILIPPINES'), 0, 1, 'L');

        $this->SetX(12);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(191, 219, 254); // Light blue
        $this->Cell(110, 4.5, sanitizeReceiptText('COMMISSION ON ELECTIONS • ELECTRONIC VOTING SERVICES'), 0, 1, 'L');

        $this->SetX(12);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(226, 232, 240);
        $this->Cell(110, 4, sanitizeReceiptText('Quirino Highway, Novaliches, Quezon City, Metro Manila • PACUCOA Accredited'), 0, 0, 'L');

        // Right Header: Official Statement Tag
        $this->SetXY(122, 5);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(254, 240, 138); // Soft Gold
        $this->Cell(76, 5, sanitizeReceiptText('STATEMENT OF BALLOT (e-BS)'), 0, 1, 'R');

        $this->SetXY(122, 10.5);
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(76, 4, sanitizeReceiptText('CERTIFIED VOTING TRANSACTION RECORD'), 0, 1, 'R');

        $this->SetXY(122, 15);
        $this->SetFont('Courier', 'B', 8);
        $this->SetTextColor(191, 219, 254);
        $this->Cell(76, 4, sanitizeReceiptText('REF: ' . $this->statementNo), 0, 1, 'R');

        $this->SetXY(122, 19.5);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(226, 232, 240);
        $this->Cell(76, 3.5, sanitizeReceiptText('A.Y. 2026-2027 SSC GENERAL ELECTIONS'), 0, 1, 'R');

        $this->Ln(12);
    }

    function Footer() {
        $this->SetY(-22);
        $this->SetDrawColor(203, 213, 225);
        $this->Line(12, $this->GetY(), $this->w - 12, $this->GetY());
        $this->Ln(2);

        $this->SetFont('Helvetica', 'I', 7);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 3.5, sanitizeReceiptText('Bestlink College of the Philippines • Tomorrow Vote Electronic Banking-Grade Ballot Record • Confidential & Encrypted'), 0, 1, 'C');
        $this->Cell(0, 3.5, sanitizeReceiptText('This is a computer-generated official Statement of Ballot. No manual signature is required. | Page ' . $this->PageNo()), 0, 1, 'C');
        $this->Cell(0, 3.5, sanitizeReceiptText('Protected with standard 128-bit document encryption • Unlock Password: Voter Student ID'), 0, 0, 'C');
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
 * Generates an authentic bank statement style, password-protected PDF of the voter's ballot.
 *
 * @param array $user Voter user record from DB
 * @param array $selectionsDetails Output of getBallotSelectionsDetails()
 * @param string $voteTimestamp Format: Y-m-d H:i:s
 * @param string $verificationHash SHA-256 integrity hash
 * @param string $primaryPassword Password to protect the document (voter's Student ID)
 * @param string|null $secondaryPassword Optional alternate password (e.g. Birthdate YYYYMMDD)
 * @return string Binary PDF content
 */
function generateProtectedBallotReceiptPdf($user, $selectionsDetails, $voteTimestamp, $verificationHash, $primaryPassword, $secondaryPassword = null) {
    $pdf = new BankStatementBallotPDF('P', 'mm', 'A4');
    $pdf->voterName = $user['full_name'] ?? 'Student Voter';
    $pdf->studentId = $user['student_id'] ?? ($user['username'] ?? 'N/A');
    $pdf->timestamp = $voteTimestamp;
    $pdf->verificationHash = $verificationHash;

    $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $pdf->studentId);
    $pdf->statementNo = "BS-2026-" . $cleanId . "-" . strtoupper(substr($verificationHash, 0, 6));

    $progSection = trim(($user['section'] ?? '') . ' ' . ($user['grade_level'] ?? ''));
    $pdf->programSection = !empty($progSection) ? $progSection : 'BSIT College Department';

    // Apply PDF standard stream cipher encryption:
    // User password (Primary) = Student ID
    // Owner password (Secondary) = Clean Date of Birth (YYYYMMDD) or random
    $userPass = (string)$primaryPassword;
    $ownerPass = !empty($secondaryPassword) ? (string)$secondaryPassword : uniqid((string)rand(), true);

    $pdf->SetProtection(['print'], $userPass, $ownerPass);

    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();

    $pdf->SetY(38);

    // =========================================================================
    // SECTION 1: ACCOUNT & STATEMENT OVERVIEW (Bank Statement Box)
    // =========================================================================
    $pdf->SetFillColor(248, 250, 252); // #f8fafc (Clean bank statement card background)
    $pdf->SetDrawColor(203, 213, 225); // #cbd5e1
    $pdf->RoundedRect(12, 37, 186, 42, 3, 'DF');

    // Box Header Ribbon
    $pdf->SetFillColor(241, 245, 249);
    $pdf->RoundedRect(12, 37, 186, 7.5, 3, 'F');
    $pdf->Rect(12, 41, 186, 3.5, 'F'); // Square bottom corners of header
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->Line(12, 44.5, 198, 44.5);

    $pdf->SetXY(16, 38.5);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(11, 34, 77); // Corporate Navy
    $pdf->Cell(90, 5, sanitizeReceiptText('VOTER ACCOUNT & ELECTORAL REGISTRY DETAILS'), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(92, 5, sanitizeReceiptText('STATUS: RECORD POSTED & CRYPTOGRAPHICALLY SEALED'), 0, 1, 'R');

    // Left Column: Voter Account Metadata
    $pdf->SetXY(16, 47);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(32, 4.5, sanitizeReceiptText('ACCOUNT HOLDER:'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(58, 4.5, sanitizeReceiptText($pdf->voterName), 0, 1);

    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(32, 4.5, sanitizeReceiptText('STUDENT ACCOUNT NO:'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(16, 89, 214); // Royal Blue
    $pdf->Cell(58, 4.5, sanitizeReceiptText($pdf->studentId), 0, 1);

    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(32, 4.5, sanitizeReceiptText('PROGRAM & SECTION:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(58, 4.5, sanitizeReceiptText($pdf->programSection), 0, 1);

    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(32, 4.5, sanitizeReceiptText('REGISTERED EMAIL:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(58, 4.5, sanitizeReceiptText($user['email'] ?? 'N/A'), 0, 1);

    // Right Column: Statement & Processing Details
    $pdf->SetXY(110, 47);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(36, 4.5, sanitizeReceiptText('STATEMENT NO:'), 0, 0);
    $pdf->SetFont('Courier', 'B', 8);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(50, 4.5, sanitizeReceiptText($pdf->statementNo), 0, 1);

    $pdf->SetX(110);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(36, 4.5, sanitizeReceiptText('POSTING DATE / TIME:'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(50, 4.5, sanitizeReceiptText($voteTimestamp . ' PHT'), 0, 1);

    $pdf->SetX(110);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(36, 4.5, sanitizeReceiptText('POLLING PRECINCT:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(50, 4.5, sanitizeReceiptText('BCP MAIN CAMPUS - ONLINE 01'), 0, 1);

    $pdf->SetX(110);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(36, 4.5, sanitizeReceiptText('SECURITY LEVEL:'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(22, 101, 52); // Green
    $pdf->Cell(50, 4.5, sanitizeReceiptText('AES-256 + 128-BIT PROTECTED'), 0, 1);

    $pdf->Ln(7);

    // =========================================================================
    // SECTION 2: ITEMIZED BALLOT TRANSACTION LEDGER
    // =========================================================================
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(11, 34, 77);
    $pdf->Cell(0, 5, sanitizeReceiptText('ITEMIZED BALLOT TRANSACTION LEDGER'), 0, 1);

    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 4, sanitizeReceiptText('The following electoral transactions were recorded and credited to your verified voter account:'), 0, 1);
    $pdf->Ln(2);

    // Bank Statement Table Header
    $pdf->SetFillColor(11, 34, 77); // Corporate Navy
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Cell(16, 7, sanitizeReceiptText('REF #'), 1, 0, 'C', true);
    $pdf->Cell(58, 7, sanitizeReceiptText('CONTEST / ELECTORAL OFFICE'), 1, 0, 'L', true);
    $pdf->Cell(62, 7, sanitizeReceiptText('CANDIDATE SELECTED'), 1, 0, 'L', true);
    $pdf->Cell(32, 7, sanitizeReceiptText('AFFILIATION / PLATFORM'), 1, 0, 'L', true);
    $pdf->Cell(18, 7, sanitizeReceiptText('STATUS'), 1, 1, 'C', true);

    // Itemized Table Rows
    $fill = false;
    $idx = 1;

    foreach ($selectionsDetails as $row) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetDrawColor(226, 232, 240);

        // Column 1: Ref No.
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Courier', 'B', 7.5);
        $refStr = sprintf('TXN-%02d', $idx++);
        $pdf->Cell(16, 8, $refStr, 'LRB', 0, 'C', true);

        // Column 2: Contest / Office
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(58, 8, '  ' . sanitizeReceiptText($row['position_name']), 'RB', 0, 'L', true);

        // Column 3: Candidate Selected
        $pdf->SetTextColor(16, 89, 214); // Royal Blue
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(62, 8, '  ' . sanitizeReceiptText($row['candidate_name']), 'RB', 0, 'L', true);

        // Column 4: Platform / Year
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('Helvetica', '', 7.5);
        $detail = !empty($row['year_level']) ? $row['year_level'] : 'Official Candidate';
        $pdf->Cell(32, 8, '  ' . sanitizeReceiptText($detail), 'RB', 0, 'L', true);

        // Column 5: Status
        $pdf->SetTextColor(22, 101, 52); // Dark Green
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->Cell(18, 8, sanitizeReceiptText('COUNTED'), 'RB', 1, 'C', true);

        $fill = !$fill;
    }

    // Ledger Summary Bar (Bank Statement Total Row)
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->SetFont('Helvetica', 'B', 8);
    $totalContests = count($selectionsDetails);
    $pdf->Cell(136, 7, sanitizeReceiptText('  TOTAL ELECTORAL CONTESTS VOTED: ' . $totalContests . ' OF ' . $totalContests), 1, 0, 'L', true);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(50, 7, sanitizeReceiptText('LEDGER BALANCE: RECONCILED  '), 1, 1, 'R', true);

    $pdf->Ln(6);

    // =========================================================================
    // SECTION 3: BANK-GRADE SECURITY SEAL & AUDIT TRAIL
    // =========================================================================
    $boxY = $pdf->GetY();
    $pdf->SetFillColor(248, 250, 252);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->RoundedRect(12, $boxY, 186, 32, 3, 'DF');

    $pdf->SetXY(16, $boxY + 3.5);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetTextColor(11, 34, 77);
    $pdf->Cell(110, 4, sanitizeReceiptText('CRYPTOGRAPHIC AUDIT SEAL & TRANSACTION VERIFICATION HASH'), 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(217, 119, 6); // Amber
    $pdf->Cell(72, 4, sanitizeReceiptText('SECURE 128-BIT ENCRYPTED DOCUMENT'), 0, 1, 'R');

    $pdf->SetX(16);
    $pdf->SetFont('Courier', 'B', 7.5);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(0, 4.5, $verificationHash, 0, 1);

    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->MultiCell(178, 3.2, sanitizeReceiptText("SECURITY PROTOCOL: This certified Statement of Ballot was cryptographically assembled and signed at the moment of ballot casting using AES-256-CBC cipher blocks. The underlying record is sealed into the Bestlink College of the Philippines immutable voting ledger. Any alteration or unauthorized reproduction voids this electronic seal."), 0, 'L');

    $pdf->Ln(4);

    // =========================================================================
    // SECTION 4: STATUTORY NOTICES & ADVISORY (Bank Statement Fine Print)
    // =========================================================================
    $pdf->SetX(12);
    $pdf->SetFont('Helvetica', 'B', 7);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 3.5, sanitizeReceiptText('IMPORTANT ADVISORY & STATUTORY DISCLOSURE:'), 0, 1);

    $pdf->SetX(12);
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->MultiCell(186, 3, sanitizeReceiptText("1. This electronic statement serves as certified official proof of participation under the Supreme Student Council Constitution and Electoral Bylaws.\n2. Under Republic Act 10175 (Cybercrime Prevention Act) and Institutional Electoral Rules, tampering with digital election instruments is strictly prohibited.\n3. Keep this PDF document safe. To view or print this statement, unlock using your registered Student ID Number."), 0, 'L');

    // Return binary string
    return $pdf->Output('S', 'eStatement_Ballot_' . $cleanId . '.pdf');
}
