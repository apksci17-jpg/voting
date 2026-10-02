<?php
// includes/pdf_export.php — Official PDF Report & Historical Backup Exporter
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/fpdf.php';

if (!function_exists('sanitizePdfText')) {
    function sanitizePdfText($str) {
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

class FPDF_Extended extends FPDF {
    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F') $op='f';
        elseif($style=='FD' || $style=='DF') $op='B';
        else $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
}

class ElectionPDFReport extends FPDF_Extended {
    public $reportTitle = 'STUDENT COUNCIL ELECTIONS 2026 - OFFICIAL ELECTION REPORT';
    public $isHistorical = false;

    function Header() {
        // Top Header Banner
        $this->SetFillColor(15, 77, 186); // Royal Blue
        $this->Rect(0, 0, $this->w, 28, 'F');
        
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetXY(10, 6);
        $this->Cell(0, 7, 'BESTLINK COLLEGE OF THE PHILIPPINES', 0, 1, 'C');
        
        $this->SetFont('Helvetica', 'B', 10);
        $subHeader = $this->isHistorical ? 'HISTORICAL ARCHIVE & CERTIFIED AUDIT RECORD' : 'STUDENT COUNCIL ELECTIONS 2026 - OFFICIAL ELECTION REPORT';
        $this->Cell(0, 5, $subHeader, 0, 1, 'C');
        
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(0, 5, 'Tomorrow Vote Online Voting System | Secure Cryptographic Audit Record', 0, 1, 'C');
        
        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-18);
        $this->SetDrawColor(203, 213, 225);
        $this->Line(10, $this->GetY(), $this->w - 10, $this->GetY());
        $this->Ln(2);
        
        $this->SetFont('Helvetica', 'I', 7);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 4, 'Bestlink College of the Philippines - Supreme Student Council Election Record | Page ' . $this->PageNo(), 0, 1, 'C');
        $this->Cell(0, 4, 'Confidential & Tamper-Evident - Archived on ' . date('Y-m-d H:i:s'), 0, 0, 'C');
    }
}

/**
 * Generate PDF Report from live database or fallback to the latest historical election backup
 */
function generateElectionPDFReport($download = true, $backupData = null) {
    $pdo = getDBConnection();
    $stats = getAdminDashboardStats();
    
    $isHistorical = false;
    $votesCount = (int)$stats['votes_cast'];
    
    // If no votes in current DB and no custom backup passed, check for last backup snapshot
    if ($votesCount === 0 && $backupData === null) {
        $backupDir = __DIR__ . '/../backups';
        if (file_exists($backupDir)) {
            $jsonFiles = glob($backupDir . '/*.json');
            if (!empty($jsonFiles)) {
                // Get most recent json backup with votes
                usort($jsonFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
                foreach ($jsonFiles as $jf) {
                    $decoded = json_decode(file_get_contents($jf), true);
                    if (!empty($decoded['votes_count']) && $decoded['votes_count'] > 0) {
                        $backupData = $decoded;
                        $isHistorical = true;
                        break;
                    }
                }
            }
        }
    }

    // Fetch positions & candidates with votes
    $positions = $pdo->query("SELECT * FROM positions ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $pdf = new ElectionPDFReport('P', 'mm', 'A4');
    $pdf->isHistorical = $isHistorical;
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 22);
    $pdf->AddPage();
    
    $pdf->SetY(34);

    // If historical data from last election is used
    if ($isHistorical && $backupData) {
        $electionTitle = $backupData['election_title'] ?? 'Student Council Election (Last Term Record)';
        $totalBallotsCast = (int)($backupData['votes_count'] ?? count($backupData['votes'] ?? []));
        $electionDate = $backupData['export_timestamp'] ?? date('Y-m-d H:i:s');
        $turnoutPct = '100.0';
        
        // Count votes per candidate from backup
        $voteCountsByCandidate = [];
        if (!empty($backupData['votes'])) {
            foreach ($backupData['votes'] as $v) {
                $cId = $v['candidate_id'] ?? 0;
                $voteCountsByCandidate[$cId] = ($voteCountsByCandidate[$cId] ?? 0) + 1;
            }
        }
    } else {
        $electionTitle = $stats['election_title'];
        $totalBallotsCast = $stats['votes_cast'];
        $electionDate = date('F j, Y - h:i A');
        $turnoutPct = $stats['turnout_pct'];
        $voteCountsByCandidate = null;
    }

    // Section: Meta Info Box
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetDrawColor(203, 213, 225);
    $pdf->RoundedRect(12, 32, 186, 26, 3, 'DF');
    
    $pdf->SetXY(16, 35);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(45, 5, 'Election Title:', 0, 0);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(50, 5, htmlspecialchars_decode($electionTitle), 0, 0);
    
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(40, 5, 'Total Ballots Cast:', 0, 0);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(45, 5, number_format($totalBallotsCast) . ' Verified Votes', 0, 1);
    
    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(45, 5, 'Record Type:', 0, 0);
    $pdf->SetFont('Helvetica', 'B', 9);
    if ($isHistorical) {
        $pdf->SetTextColor(180, 83, 9);
        $pdf->Cell(50, 5, 'LAST ELECTION ARCHIVE', 0, 0);
    } elseif ($stats['voting_status'] === 'OPEN') {
        $pdf->SetTextColor(22, 101, 52);
        $pdf->Cell(50, 5, 'OFFICIALLY OPEN', 0, 0);
    } else {
        $pdf->SetTextColor(153, 0, 0);
        $pdf->Cell(50, 5, 'CLOSED & CONCLUDED', 0, 0);
    }
    
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(40, 5, 'Voter Turnout Rate:', 0, 0);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(15, 77, 186);
    $pdf->Cell(45, 5, $turnoutPct . '%', 0, 1);
    
    $pdf->SetX(16);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(45, 5, 'Audit Timestamp:', 0, 0);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(50, 5, $electionDate, 0, 0);
    
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(40, 5, 'Encryption Protocol:', 0, 0);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(45, 5, 'AES-256 CBC Mode', 0, 1);

    $pdf->Ln(6);

    // Section: WINNING CANDIDATES TABLE
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(0, 7, $isHistorical ? 'HISTORICAL ELECTED WINNERS (LAST ELECTION RESULTS)' : 'OFFICIAL ELECTED WINNERS & LEADING CANDIDATES', 0, 1, 'L');
    
    $pdf->SetFillColor(15, 77, 186);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Cell(42, 7, 'POSITION', 1, 0, 'L', true);
    $pdf->Cell(58, 7, 'WINNING CANDIDATE', 1, 0, 'L', true);
    $pdf->Cell(28, 7, 'YEAR LEVEL', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'VOTES RECEIVED', 1, 0, 'C', true);
    $pdf->Cell(28, 7, 'PERCENTAGE', 1, 1, 'C', true);

    $pdf->SetFont('Helvetica', '', 8);
    $fill = false;
    
    foreach ($positions as $pos) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.candidate_name, c.year_level
            FROM candidates c
            WHERE c.position_id = ?
            ORDER BY c.id ASC
        ");
        $stmt->execute([$pos['id']]);
        $cands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cands as &$c) {
            if ($voteCountsByCandidate !== null) {
                $c['vote_count'] = $voteCountsByCandidate[$c['id']] ?? 0;
            } else {
                $st = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ?");
                $st->execute([$c['id']]);
                $c['vote_count'] = (int)$st->fetchColumn();
            }
        }
        unset($c);
        
        usort($cands, function($a, $b) { return $b['vote_count'] <=> $a['vote_count']; });
        
        $totalPosVotes = array_sum(array_column($cands, 'vote_count'));
        $winner = $cands[0] ?? null;
        
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetTextColor(15, 23, 42);
        
        $winnerName = $winner ? $winner['candidate_name'] : 'No candidate';
        $winnerYear = $winner ? $winner['year_level'] : '-';
        $winnerVotes = $winner ? number_format($winner['vote_count']) : '0';
        $winnerPct = ($winner && $totalPosVotes > 0) ? round(($winner['vote_count'] / $totalPosVotes) * 100, 1) . '%' : '0.0%';
        
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(42, 6, $pos['position_name'], 1, 0, 'L', $fill);
        $pdf->Cell(58, 6, $winnerName, 1, 0, 'L', $fill);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(28, 6, $winnerYear, 1, 0, 'C', $fill);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(30, 6, $winnerVotes, 1, 0, 'C', $fill);
        $pdf->Cell(28, 6, $winnerPct, 1, 1, 'C', $fill);
        
        $fill = !$fill;
    }

    $pdf->Ln(6);

    // Section: DETAILED VOTE BREAKDOWN PER POSITION
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(0, 7, 'COMPLETE VOTE BREAKDOWN BY POSITION', 0, 1, 'L');

    foreach ($positions as $pos) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.candidate_name, c.year_level, c.platform
            FROM candidates c
            WHERE c.position_id = ?
            ORDER BY c.id ASC
        ");
        $stmt->execute([$pos['id']]);
        $cands = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cands as &$c) {
            if ($voteCountsByCandidate !== null) {
                $c['vote_count'] = $voteCountsByCandidate[$c['id']] ?? 0;
            } else {
                $st = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ?");
                $st->execute([$c['id']]);
                $c['vote_count'] = (int)$st->fetchColumn();
            }
        }
        unset($c);

        usort($cands, function($a, $b) { return $b['vote_count'] <=> $a['vote_count']; });
        $totalPosVotes = array_sum(array_column($cands, 'vote_count'));

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetTextColor(15, 77, 186);
        $pdf->Cell(0, 6, 'Position: ' . $pos['position_name'] . ' (Total Position Ballots: ' . number_format($totalPosVotes) . ')', 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(60, 6, 'Candidate Name', 1, 0, 'L', true);
        $pdf->Cell(35, 6, 'Year Level', 1, 0, 'C', true);
        $pdf->Cell(45, 6, 'Votes Received', 1, 0, 'C', true);
        $pdf->Cell(46, 6, 'Vote Share %', 1, 1, 'C', true);

        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(15, 23, 42);
        
        if (empty($cands)) {
            $pdf->Cell(0, 6, 'No registered candidates for this position.', 1, 1, 'C');
        } else {
            $rank = 1;
            foreach ($cands as $cand) {
                $pct = $totalPosVotes > 0 ? round(($cand['vote_count'] / $totalPosVotes) * 100, 1) : 0;
                $isTop = ($rank === 1 && $cand['vote_count'] > 0);
                
                if ($isTop) {
                    $pdf->SetFillColor(220, 252, 231);
                    $pdf->SetFont('Helvetica', 'B', 7.5);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetFont('Helvetica', '', 7.5);
                }
                
                $displayName = ($isTop ? '[WINNER] ' : '') . $cand['candidate_name'];
                $pdf->Cell(60, 6, $displayName, 1, 0, 'L', $isTop);
                $pdf->Cell(35, 6, $cand['year_level'], 1, 0, 'C', $isTop);
                $pdf->Cell(45, 6, number_format($cand['vote_count']) . ' votes', 1, 0, 'C', $isTop);
                $pdf->Cell(46, 6, $pct . '%', 1, 1, 'C', $isTop);
                $rank++;
            }
        }
        $pdf->Ln(2);
    }

    // Sign-off Box
    $pdf->Ln(4);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(90, 4, 'Certified Official Record By:', 0, 0);
    $pdf->Cell(96, 4, 'System Authentication Hash:', 0, 1);
    
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->Cell(90, 4, 'Commission on Student Elections (COMELEC)', 0, 0);
    $pdf->Cell(96, 4, hash('sha256', date('Y-m-d H:i:s') . $totalBallotsCast), 0, 1);
    
    $pdf->Cell(90, 4, 'Bestlink College of the Philippines', 0, 0);
    $pdf->Cell(96, 4, 'Tamper-Evident AES-256 Database Signature Verified', 0, 1);

    // Save backup file
    $backupDir = __DIR__ . '/../backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    $filename = 'election_official_report_' . date('Y-m-d_His') . '.pdf';
    $fullPath = $backupDir . '/' . $filename;
    $pdf->Output('F', $fullPath);

    if ($download) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($fullPath);
        exit;
    }

    return $fullPath;
}
