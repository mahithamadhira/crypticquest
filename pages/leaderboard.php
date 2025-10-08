<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('login.php');
}

// Read leaderboard data from scores JSON file using helper function
function getLeaderboardData() {
    $scoresData = readJsonFile(SCORES_FILE);
    
    $scores = [];
    foreach ($scoresData as $score) {
        $scores[] = [
            'user_id' => $score['user_id'],
            'username' => $score['username'],
            'case_id' => $score['case_id'],
            'score' => (int)$score['score'],
            'completed_at' => $score['completed_at'],
            'time_taken' => (int)$score['time_taken'],
            'correct' => $score['status'] === 'solved' ? 1 : 0
        ];
    }
    
    return $scores;
}

// Calculate user statistics for leaderboard
function calculateUserStats($scores) {
    $userStats = [];
    
    foreach ($scores as $score) {
        $username = $score['username'];
        
        if (!isset($userStats[$username])) {
            $userStats[$username] = [
                'username' => $username,
                'total_score' => 0,
                'cases_completed' => 0,
                'cases_correct' => 0,
                'total_time' => 0,
                'best_score' => 0,
                'last_activity' => $score['completed_at']
            ];
        }
        
        $userStats[$username]['total_score'] += $score['score'];
        $userStats[$username]['cases_completed']++;
        $userStats[$username]['cases_correct'] += $score['correct'];
        $userStats[$username]['total_time'] += $score['time_taken'];
        $userStats[$username]['best_score'] = max($userStats[$username]['best_score'], $score['score']);
        
        // Update last activity if this score is more recent
        if ($score['completed_at'] > $userStats[$username]['last_activity']) {
            $userStats[$username]['last_activity'] = $score['completed_at'];
        }
    }
    
    // Calculate derived statistics
    foreach ($userStats as &$stats) {
        $stats['accuracy_rate'] = $stats['cases_completed'] > 0 ? 
            round(($stats['cases_correct'] / $stats['cases_completed']) * 100) : 0;
        $stats['average_score'] = $stats['cases_completed'] > 0 ? 
            round($stats['total_score'] / $stats['cases_completed']) : 0;
        $stats['average_time'] = $stats['cases_completed'] > 0 ? 
            round($stats['total_time'] / $stats['cases_completed']) : 0;
    }
    
    return $userStats;
}

// Get filter parameters
$filterBy = $_GET['filter'] ?? 'total_score';
$timeFrame = $_GET['timeframe'] ?? 'all_time';

// Get all scores
$allScores = getLeaderboardData();

// Filter by timeframe
$filteredScores = $allScores;
if ($timeFrame !== 'all_time') {
    $cutoffTime = '';
    switch ($timeFrame) {
        case 'today':
            $cutoffTime = date('Y-m-d') . ' 00:00:00';
            break;
        case 'week':
            $cutoffTime = date('Y-m-d H:i:s', strtotime('-1 week'));
            break;
        case 'month':
            $cutoffTime = date('Y-m-d H:i:s', strtotime('-1 month'));
            break;
    }
    
    if ($cutoffTime) {
        $filteredScores = array_filter($allScores, function($score) use ($cutoffTime) {
            return $score['completed_at'] >= $cutoffTime;
        });
    }
}

// Calculate user statistics
$userStats = calculateUserStats($filteredScores);

// Sort by selected filter
switch ($filterBy) {
    case 'accuracy':
        uasort($userStats, function($a, $b) {
            if ($a['accuracy_rate'] === $b['accuracy_rate']) {
                return $b['total_score'] - $a['total_score'];
            }
            return $b['accuracy_rate'] - $a['accuracy_rate'];
        });
        break;
    case 'cases_completed':
        uasort($userStats, function($a, $b) {
            if ($a['cases_completed'] === $b['cases_completed']) {
                return $b['total_score'] - $a['total_score'];
            }
            return $b['cases_completed'] - $a['cases_completed'];
        });
        break;
    case 'best_score':
        uasort($userStats, function($a, $b) {
            return $b['best_score'] - $a['best_score'];
        });
        break;
    case 'average_score':
        uasort($userStats, function($a, $b) {
            if ($a['average_score'] === $b['average_score']) {
                return $b['total_score'] - $a['total_score'];
            }
            return $b['average_score'] - $a['average_score'];
        });
        break;
    case 'total_score':
    default:
        uasort($userStats, function($a, $b) {
            return $b['total_score'] - $a['total_score'];
        });
        break;
}

// Get current user's rank
$currentUser = $_SESSION['username'];
$userRank = 0;
$userPosition = 0;
foreach ($userStats as $position => $stats) {
    $userPosition++;
    if ($stats['username'] === $currentUser) {
        $userRank = $userPosition;
        break;
    }
}

// Get top performers for highlights
$topByScore = $userStats;
uasort($topByScore, function($a, $b) { return $b['total_score'] - $a['total_score']; });
$topByAccuracy = $userStats;
uasort($topByAccuracy, function($a, $b) { return $b['accuracy_rate'] - $a['accuracy_rate']; });
$topByCases = $userStats;
uasort($topByCases, function($a, $b) { return $b['cases_completed'] - $a['cases_completed']; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detective Leaderboard - Cryptic Quest</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_leaderboard.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <header>
                <h1>Detective Leaderboard</h1>
                <p>Top Investigators & Crime Solvers</p>
            </header>
            
            <main>
                <!-- Current User Position -->
                <?php if (isset($userStats[$currentUser])): ?>
                <div class="user-position">
                    <h3>Your Ranking</h3>
                    <div class="position-card">
                        <div class="position-info">
                            <span class="rank-number">#<?php echo $userRank; ?></span>
                            <div class="user-details">
                                <h4>Detective <?php echo htmlspecialchars($currentUser); ?></h4>
                                <p><?php echo $userStats[$currentUser]['total_score']; ?> points • <?php echo $userStats[$currentUser]['cases_completed']; ?> cases • <?php echo $userStats[$currentUser]['accuracy_rate']; ?>% accuracy</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Top Performers Highlights -->
                <div class="top-performers">
                    <h3>Hall of Fame</h3>
                    <div class="performers-grid">
                        <?php if (!empty($topByScore)): ?>
                        <div class="performer-card">
                            <div class="rank-number">1</div>
                            <h4>Highest Score</h4>
                            <?php $topScorer = reset($topByScore); ?>
                            <div class="performer-info">
                                <div>
                                    <p class="performer-name"><?php echo htmlspecialchars($topScorer['username']); ?></p>
                                    <p class="performer-stat"><?php echo $topScorer['total_score']; ?> points</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($topByAccuracy)): ?>
                        <div class="performer-card">
                            <div class="rank-number">2</div>
                            <h4>Best Accuracy</h4>
                            <?php $topAccuracy = reset($topByAccuracy); ?>
                            <div class="performer-info">
                                <div>
                                    <p class="performer-name"><?php echo htmlspecialchars($topAccuracy['username']); ?></p>
                                    <p class="performer-stat"><?php echo $topAccuracy['accuracy_rate']; ?>% accuracy</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($topByCases)): ?>
                        <div class="performer-card">
                            <div class="rank-number">3</div>
                            <h4>Most Cases</h4>
                            <?php $topCases = reset($topByCases); ?>
                            <div class="performer-info">
                                <div>
                                    <p class="performer-name"><?php echo htmlspecialchars($topCases['username']); ?></p>
                                    <p class="performer-stat"><?php echo $topCases['cases_completed']; ?> cases solved</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Leaderboard Filters -->
                <div class="leaderboard-filters">
                    <div class="filter-section">
                        <h4>Sort By:</h4>
                        <div class="filter-buttons">
                            <a href="?filter=total_score&timeframe=<?php echo $timeFrame; ?>" 
                               class="filter-btn <?php echo $filterBy === 'total_score' ? 'active' : ''; ?>">
                                Total Score
                            </a>
                            <a href="?filter=accuracy&timeframe=<?php echo $timeFrame; ?>" 
                               class="filter-btn <?php echo $filterBy === 'accuracy' ? 'active' : ''; ?>">
                                Accuracy Rate
                            </a>
                            <a href="?filter=cases_completed&timeframe=<?php echo $timeFrame; ?>" 
                               class="filter-btn <?php echo $filterBy === 'cases_completed' ? 'active' : ''; ?>">
                                Cases Solved
                            </a>
                            <a href="?filter=best_score&timeframe=<?php echo $timeFrame; ?>" 
                               class="filter-btn <?php echo $filterBy === 'best_score' ? 'active' : ''; ?>">
                                Best Single Score
                            </a>
                            <a href="?filter=average_score&timeframe=<?php echo $timeFrame; ?>" 
                               class="filter-btn <?php echo $filterBy === 'average_score' ? 'active' : ''; ?>">
                                Average Score
                            </a>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4>Time Frame:</h4>
                        <div class="filter-buttons">
                            <a href="?filter=<?php echo $filterBy; ?>&timeframe=all_time" 
                               class="filter-btn <?php echo $timeFrame === 'all_time' ? 'active' : ''; ?>">
                                All Time
                            </a>
                            <a href="?filter=<?php echo $filterBy; ?>&timeframe=month" 
                               class="filter-btn <?php echo $timeFrame === 'month' ? 'active' : ''; ?>">
                                This Month
                            </a>
                            <a href="?filter=<?php echo $filterBy; ?>&timeframe=week" 
                               class="filter-btn <?php echo $timeFrame === 'week' ? 'active' : ''; ?>">
                                This Week
                            </a>
                            <a href="?filter=<?php echo $filterBy; ?>&timeframe=today" 
                               class="filter-btn <?php echo $timeFrame === 'today' ? 'active' : ''; ?>">
                                Today
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Leaderboard Table -->
                <div class="leaderboard-table">
                    <h3>Rankings</h3>
                    
                    <?php if (empty($userStats)): ?>
                        <div class="no-data">
                            <p>No detective data available yet. Be the first to solve a case!</p>
                            <a href="cases.php" class="btn btn-primary">Start Investigating</a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="leaderboard">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Detective</th>
                                        <th>Total Score</th>
                                        <th>Cases Solved</th>
                                        <th>Accuracy Rate</th>
                                        <th>Best Score</th>
                                        <th>Average Score</th>
                                        <th>Last Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $position = 1;
                                    foreach ($userStats as $stats): 
                                        $isCurrentUser = ($stats['username'] === $currentUser);
                                    ?>
                                    <tr class="<?php echo $isCurrentUser ? 'current-user' : ''; ?> <?php echo $position <= 3 ? 'top-' . $position : ''; ?>">
                                        <td>
                                            <span class="rank-position">
                                                <?php if ($position === 1): ?>
                                                    1
                                                <?php elseif ($position === 2): ?>
                                                    2
                                                <?php elseif ($position === 3): ?>
                                                    3
                                                <?php else: ?>
                                                    #<?php echo $position; ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="detective-info">
                                                <span class="detective-name">
                                                    Detective <?php echo htmlspecialchars($stats['username']); ?>
                                                    <?php if ($isCurrentUser): ?>
                                                        <span class="you-indicator">(You)</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="score-cell">
                                            <span class="score-value"><?php echo number_format($stats['total_score']); ?></span>
                                        </td>
                                        <td>
                                            <span class="cases-count"><?php echo $stats['cases_completed']; ?></span>
                                            <small>(<?php echo $stats['cases_correct']; ?> correct)</small>
                                        </td>
                                        <td>
                                            <div class="accuracy-display">
                                                <span class="accuracy-percentage"><?php echo $stats['accuracy_rate']; ?>%</span>
                                                <div class="accuracy-bar">
                                                    <div class="accuracy-fill" style="width: <?php echo $stats['accuracy_rate']; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="score-cell">
                                            <span class="score-value"><?php echo number_format($stats['best_score']); ?></span>
                                        </td>
                                        <td class="score-cell">
                                            <span class="score-value"><?php echo number_format($stats['average_score']); ?></span>
                                        </td>
                                        <td>
                                            <span class="last-activity">
                                                <?php echo date('M j, Y', strtotime($stats['last_activity'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php 
                                    $position++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Leaderboard Statistics -->
                <div class="leaderboard-stats">
                    <h3>Community Statistics</h3>
                    <div class="community-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($userStats); ?></span>
                            <span class="stat-label">Active Detectives</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo array_sum(array_column($userStats, 'cases_completed')); ?></span>
                            <span class="stat-label">Total Cases Solved</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo array_sum(array_column($userStats, 'total_score')); ?></span>
                            <span class="stat-label">Total Points Earned</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?php 
                                $totalCases = array_sum(array_column($userStats, 'cases_completed'));
                                $totalCorrect = array_sum(array_column($userStats, 'cases_correct'));
                                echo $totalCases > 0 ? round(($totalCorrect / $totalCases) * 100) : 0;
                                ?>%
                            </span>
                            <span class="stat-label">Community Accuracy</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="leaderboard-actions">
                    <a href="cases.php" class="btn btn-primary">Solve More Cases</a>
                    <a href="profile.php" class="btn btn-secondary">View Your Profile</a>
                    <a href="../index.php" class="btn btn-secondary">Return to Dashboard</a>
                </div>
            </main>
        </div>
    </div>
    
</body>
</html>