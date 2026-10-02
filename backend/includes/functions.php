<?php
// includes/functions.php — System Helper Functions
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

/**
 * Get election setting value by key
 */
function getElectionSetting($key, $default = '') {
    if ($key === 'voting_status') {
        static $syncing = false;
        if (!$syncing) {
            $syncing = true;
            syncAutomaticVotingStatus();
            $syncing = false;
        }
    }
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM election_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

/**
 * Update election setting key
 */
function setElectionSetting($key, $value) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO election_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([$key, $value]);
}

/**
 * Is election currently OPEN
 */
function isVotingOpen() {
    return getElectionSetting('voting_status', 'CLOSED') === 'OPEN';
}

/**
 * Get election summary statistics for Admin Dashboard
 */
function getAdminDashboardStats() {
    $pdo = getDBConnection();
    syncAutomaticVotingStatus();
    
    // Total distinct voters who submitted ballots
    $stmt = $pdo->query("SELECT COUNT(DISTINCT voter_id) as total_votes FROM votes");
    $votesCast = (int)$stmt->fetchColumn();

    // Total active candidates
    $stmt = $pdo->query("SELECT COUNT(*) as total_candidates FROM candidates");
    $candidatesCount = (int)$stmt->fetchColumn();

    // Total registered voters
    $stmt = $pdo->query("SELECT COUNT(*) as total_voters FROM users WHERE role = 'voter'");
    $totalVoters = (int)$stmt->fetchColumn();

    // Turnout percentage
    $turnoutPct = $totalVoters > 0 ? round(($votesCast / $totalVoters) * 100, 1) : 0;

    // Days remaining until end_date
    $endDateStr = getElectionSetting('end_date', '2026-08-15');
    $endDate = new DateTime($endDateStr);
    $now = new DateTime();
    $daysRemaining = $now < $endDate ? $now->diff($endDate)->days : 0;

    return [
        'votes_cast' => $votesCast,
        'candidates_count' => $candidatesCount,
        'turnout_pct' => $turnoutPct,
        'days_remaining' => $daysRemaining,
        'total_voters' => $totalVoters,
        'voting_status' => getElectionSetting('voting_status', 'CLOSED'),
        'election_title' => getElectionSetting('election_title', 'Student Council Election 2026'),
        'start_date' => getElectionSetting('start_date', '2026-08-08'),
        'end_date' => $endDateStr,
        'start_time' => getElectionSetting('start_time', '08:00 AM'),
        'end_time' => getElectionSetting('end_time', '06:00 PM'),
        'schedules' => getElectionSchedules(),
        'timeline' => getVotesTimelineData(),
        'by_position' => getVotesByPositionData()
    ];
}

/**
 * Get real-time timeline data for Votes Overview chart
 */
function getVotesTimelineData() {
    $pdo = getDBConnection();
    
    $totalVoters = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter'")->fetchColumn();
    if ($totalVoters <= 0) $totalVoters = 31;

    // Fetch distinct voter ballots sorted by created_at
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(MIN(created_at), '%h:%i %p') as time_label,
               MIN(created_at) as created_at
        FROM votes
        GROUP BY voter_id
        ORDER BY created_at ASC
    ");
    $ballotTimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalVotesCast = count($ballotTimes);

    if ($totalVotesCast > 0) {
        $labels = ['Start'];
        $data = [0];
        $cumulative = 0;

        foreach ($ballotTimes as $b) {
            $cumulative++;
            $labels[] = $b['time_label'];
            $data[] = $cumulative;
        }

        // Add Live point
        $labels[] = 'Live (' . date('h:i A') . ')';
        $data[] = $totalVotesCast;

        return [
            'labels' => $labels,
            'data' => $data,
            'max_voters' => $totalVoters,
            'total_votes' => $totalVotesCast
        ];
    } else {
        return [
            'labels' => ['08:00 AM', '10:00 AM', '12:00 PM', '02:00 PM', '04:00 PM', '06:00 PM', 'Live'],
            'data' => [0, 0, 0, 0, 0, 0, 0],
            'max_voters' => $totalVoters,
            'total_votes' => 0
        ];
    }
}

/**
 * Get votes breakdown by Position for secondary overview
 */
function getVotesByPositionData() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT p.position_name, COUNT(v.id) as vote_count 
        FROM positions p
        LEFT JOIN votes v ON p.id = v.position_id
        GROUP BY p.id, p.position_name, p.display_order
        ORDER BY p.display_order ASC
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $data = [];
    foreach ($results as $r) {
        $labels[] = $r['position_name'];
        $data[] = (int)$r['vote_count'];
    }

    return [
        'labels' => $labels,
        'data' => $data
    ];
}

/**
 * Get all added election schedules
 */
function getElectionSchedules() {
    $setting = getElectionSetting('election_schedules_list', null);
    if ($setting === null) {
        $today = date('Y-m-d');
        return [
            [
                'id' => '1',
                'title' => 'Student Council Election 2026',
                'start_date' => $today,
                'end_date' => $today,
                'date' => $today,
                'start_time' => '08:00 AM',
                'end_time' => '06:00 PM',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    $schedules = json_decode($setting, true);
    return is_array($schedules) ? $schedules : [];
}

/**
 * Add a new election schedule with start date and end date range
 */
function addElectionSchedule($startDate, $endDate, $startTime, $endTime, $title = 'Student Council Election 2026') {
    $schedules = getElectionSchedules();
    $newSchedule = [
        'id' => (string)(time() . rand(100, 999)),
        'title' => $title,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'date' => $startDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'created_at' => date('Y-m-d H:i:s')
    ];
    array_unshift($schedules, $newSchedule);
    setElectionSetting('election_schedules_list', json_encode(array_values($schedules)));

    setElectionSetting('start_date', $startDate);
    setElectionSetting('end_date', $endDate);
    setElectionSetting('start_time', $startTime);
    setElectionSetting('end_time', $endTime);

    return $newSchedule;
}

/**
 * Delete an election schedule by ID
 */
function deleteElectionSchedule($id) {
    $schedules = getElectionSchedules();
    $filtered = array_values(array_filter($schedules, function($s) use ($id) {
        return (string)$s['id'] !== (string)$id;
    }));
    setElectionSetting('election_schedules_list', json_encode($filtered));
}

/**
 * Updates the status override of a schedule item (OPEN or CLOSED)
 */
function updateScheduleStatusOverride($id, $status) {
    $schedules = getElectionSchedules();
    foreach ($schedules as &$s) {
        if ((string)$s['id'] === (string)$id) {
            $s['status_override'] = $status;
        }
    }
    setElectionSetting('election_schedules_list', json_encode(array_values($schedules)));
}

/**
 * Automatically evaluates the schedule and opens/closes the voting status based on start & end date/time.
 */
function syncAutomaticVotingStatus() {
    $manualOverride = getElectionSetting('manual_override', 'AUTO');
    if ($manualOverride === 'MANUAL_OPEN') {
        setElectionSetting('voting_status', 'OPEN');
        return 'OPEN';
    }
    if ($manualOverride === 'MANUAL_CLOSED') {
        setElectionSetting('voting_status', 'CLOSED');
        return 'CLOSED';
    }

    $now = time();
    $today = date('Y-m-d');

    // 1. Check all configured schedule items in election_schedules_list
    $schedules = getElectionSchedules();
    $hasActiveSchedule = false;

    if (!empty($schedules)) {
        foreach ($schedules as $sch) {
            if (!empty($sch['status_override'])) {
                if ($sch['status_override'] === 'OPEN') {
                    $hasActiveSchedule = true;
                    break;
                }
                continue;
            }

            $sDate = $sch['start_date'] ?? $sch['date'] ?? $today;
            $eDate = $sch['end_date'] ?? $sDate;
            $sTime = !empty($sch['start_time']) ? $sch['start_time'] : '00:00:00';
            $eTime = !empty($sch['end_time']) ? $sch['end_time'] : '23:59:59';

            $startTs = strtotime("$sDate $sTime");
            $endTs = strtotime("$eDate $eTime");

            if ($startTs === false || $endTs === false) {
                $startTs = strtotime("$sDate 00:00:00");
                $endTs = strtotime("$eDate 23:59:59");
            }

            if ($now >= $startTs && $now <= $endTs) {
                $hasActiveSchedule = true;
                break;
            }
        }
    }

    // 2. Also check global settings start_date and end_date
    if (!$hasActiveSchedule) {
        $startDate = getElectionSetting('start_date', '');
        $endDate = getElectionSetting('end_date', '');
        
        if (!empty($startDate) && !empty($endDate)) {
            $startTime = getElectionSetting('start_time', '00:00:00');
            $endTime = getElectionSetting('end_time', '23:59:59');

            $startTs = strtotime("$startDate $startTime");
            $endTs = strtotime("$endDate $endTime");

            if ($startTs !== false && $endTs !== false && $now >= $startTs && $now <= $endTs) {
                $hasActiveSchedule = true;
            }
        }
    }

    if ($hasActiveSchedule) {
        setElectionSetting('voting_status', 'OPEN');
        return 'OPEN';
    } else {
        setElectionSetting('voting_status', 'CLOSED');
        return 'CLOSED';
    }
}

/**
 * Evaluates the dynamic status of a schedule item based on current date & time or manual override.
 * Returns: 'ACTIVE', 'SCHEDULED', or 'FINISHED'
 */
function evaluateScheduleStatus($sch) {
    if (!empty($sch['status_override'])) {
        return $sch['status_override'] === 'OPEN' ? 'ACTIVE' : 'FINISHED';
    }
    $sDate = $sch['start_date'] ?? $sch['date'] ?? date('Y-m-d');
    $eDate = $sch['end_date'] ?? $sDate;
    $sTime = $sch['start_time'] ?? '08:00 AM';
    $eTime = $sch['end_time'] ?? '06:00 PM';

    $startTs = strtotime("$sDate $sTime");
    $endTs = strtotime("$eDate $eTime");
    $now = time();

    if ($now < $startTs) {
        return 'SCHEDULED';
    } elseif ($now >= $startTs && $now <= $endTs) {
        return 'ACTIVE';
    } else {
        return 'FINISHED';
    }
}

/**
 * Get official election results (candidates grouped by position with vote counts)
 */
function getOfficialElectionResults() {
    $pdo = getDBConnection();
    $positions = $pdo->query("SELECT * FROM positions ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    foreach ($positions as $pos) {
        $stmt = $pdo->prepare("
            SELECT id, candidate_name, year_level, profile_image
            FROM candidates
            WHERE position_id = ?
        ");
        $stmt->execute([$pos['id']]);
        $cands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cands as &$c) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ?");
            $st->execute([$c['id']]);
            $c['vote_count'] = (int)$st->fetchColumn();
        }
        unset($c);
        
        // Sort by vote count descending
        usort($cands, function($a, $b) {
            return $b['vote_count'] <=> $a['vote_count'];
        });
        
        $pos['candidates'] = $cands;
        $results[] = $pos;
    }
    
    return $results;
}

