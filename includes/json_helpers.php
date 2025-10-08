<?php

function readJsonFile($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $content = file_get_contents($filePath);
    if (empty($content)) {
        return [];
    }
    
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in $filePath: " . json_last_error_msg());
        return [];
    }
    
    return $data;
}

function writeJsonFile($filePath, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonData === false) {
        error_log("JSON encode error: " . json_last_error_msg());
        return false;
    }
    
    $result = file_put_contents($filePath, $jsonData);
    if ($result === false) {
        error_log("Failed to write to file: $filePath");
        return false;
    }
    
    return true;
}

function addScoreEntry($userId, $username, $caseId, $score, $timeTaken, $status) {
    $scores = readJsonFile(SCORES_FILE);
    
    $newEntry = [
        'user_id' => $userId,
        'username' => $username,
        'case_id' => $caseId,
        'score' => (int)$score,
        'completed_at' => getCurrentTimestamp(),
        'time_taken' => (int)$timeTaken,
        'status' => $status
    ];
    
    $scores[] = $newEntry;
    return writeJsonFile(SCORES_FILE, $scores);
}

function updateUserData($userId, $username, $caseData) {
    $users = readJsonFile(USERS_FILE);
    
    $userExists = false;
    foreach ($users as &$user) {
        if ($user['user_id'] === $userId) {
            $user['cases_completed'][] = $caseData;
            $user['total_score'] = array_sum(array_column($user['cases_completed'], 'score'));
            $userExists = true;
            break;
        }
    }
    
    if (!$userExists) {
        $newUser = [
            'user_id' => $userId,
            'username' => $username,
            'registration_time' => getCurrentTimestamp(),
            'total_score' => $caseData['score'],
            'cases_completed' => [$caseData],
            'achievements' => []
        ];
        $users[] = $newUser;
    }
    
    return writeJsonFile(USERS_FILE, $users);
}

function updateLeaderboard() {
    $users = readJsonFile(USERS_FILE);
    $leaderboard = [];
    
    foreach ($users as $user) {
        $totalCases = count($user['cases_completed']);
        $solvedCases = count(array_filter($user['cases_completed'], function($case) {
            return $case['status'] === 'solved';
        }));
        
        $leaderboardEntry = [
            'username' => $user['username'],
            'total_score' => $user['total_score'],
            'total_cases' => $totalCases,
            'solved_cases' => $solvedCases,
            'failed_cases' => $totalCases - $solvedCases,
            'accuracy_rate' => $totalCases > 0 ? round(($solvedCases / $totalCases) * 100) : 0,
            'last_activity' => !empty($user['cases_completed']) ? 
                max(array_column($user['cases_completed'], 'completed_at')) : 
                $user['registration_time'],
            'best_score' => !empty($user['cases_completed']) ? 
                max(array_column($user['cases_completed'], 'score')) : 0,
            'average_score' => $totalCases > 0 ? 
                round($user['total_score'] / $totalCases) : 0
        ];
        
        $leaderboard[] = $leaderboardEntry;
    }
    
    usort($leaderboard, function($a, $b) {
        return $b['total_score'] - $a['total_score'];
    });
    
    return writeJsonFile(LEADERBOARD_FILE, $leaderboard);
}

function getUserByUsername($username) {
    $users = readJsonFile(USERS_FILE);
    
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    
    return null;
}

?>