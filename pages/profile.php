<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('login.php');
}

// Ensure user data is loaded from file
if (empty($_SESSION['cases_completed']) && file_exists(SCORES_FILE)) {
    loadUserDataIntoSession($_SESSION['username']);
}

// Calculate user statistics
$casesCompleted = $_SESSION['cases_completed'] ?? [];
$totalScore = $_SESSION['total_score'] ?? 0;
$achievements = $_SESSION['achievements'] ?? [];

// Calculate additional stats
$totalCases = count($casesCompleted);
$correctCases = count(array_filter($casesCompleted, function($case) { return $case['correct']; }));
$averageScore = $totalCases > 0 ? round($totalScore / $totalCases) : 0;
$accuracyRate = $totalCases > 0 ? round(($correctCases / $totalCases) * 100) : 0;

// Calculate total time spent
$totalTimeSpent = 0;
foreach ($casesCompleted as $case) {
    $totalTimeSpent += $case['time_taken'] ?? 0;
}

// Calculate average time per case
$averageTime = $totalCases > 0 ? round($totalTimeSpent / $totalCases) : 0;

// Get difficulty breakdown
$difficultyStats = [
    'beginner' => 0,
    'intermediate' => 0,
    'advanced' => 0,
    'expert' => 0
];

$allCases = getCases();
foreach ($casesCompleted as $completedCase) {
    $caseData = getCase($completedCase['case_id']);
    if ($caseData) {
        $difficultyStats[$caseData['difficulty']]++;
    }
}

// Calculate detective level
$detectives_levels = [
    ['name' => 'Rookie Detective', 'min_score' => 0, 'min_cases' => 0],
    ['name' => 'Junior Detective', 'min_score' => 100, 'min_cases' => 2],
    ['name' => 'Detective', 'min_score' => 300, 'min_cases' => 5],
    ['name' => 'Senior Detective', 'min_score' => 600, 'min_cases' => 10],
    ['name' => 'Detective Inspector', 'min_score' => 1000, 'min_cases' => 15],
    ['name' => 'Chief Detective', 'min_score' => 1500, 'min_cases' => 20]
];

$currentLevel = $detectives_levels[0];
foreach ($detectives_levels as $level) {
    if ($totalScore >= $level['min_score'] && $totalCases >= $level['min_cases']) {
        $currentLevel = $level;
    }
}

// Calculate progress to next level
$nextLevelIndex = array_search($currentLevel, $detectives_levels) + 1;
$nextLevel = $nextLevelIndex < count($detectives_levels) ? $detectives_levels[$nextLevelIndex] : null;

// Check for new achievements
$newAchievements = [];

// First case achievement
if ($totalCases >= 1 && !in_array('first_case', $achievements)) {
    $newAchievements[] = 'first_case';
}

// Perfect accuracy achievement
if ($accuracyRate === 100 && $totalCases >= 3 && !in_array('perfect_accuracy', $achievements)) {
    $newAchievements[] = 'perfect_accuracy';
}

// Speed demon achievement (average time under 20 minutes)
if ($averageTime <= 1200 && $totalCases >= 5 && !in_array('speed_demon', $achievements)) {
    $newAchievements[] = 'speed_demon';
}

// High scorer achievement
if ($totalScore >= 500 && !in_array('high_scorer', $achievements)) {
    $newAchievements[] = 'high_scorer';
}

// Veteran achievement
if ($totalCases >= 10 && !in_array('veteran', $achievements)) {
    $newAchievements[] = 'veteran';
}

// Master detective achievement
if ($totalCases >= 20 && $accuracyRate >= 90 && !in_array('master_detective', $achievements)) {
    $newAchievements[] = 'master_detective';
}

// Add new achievements to session
if (!empty($newAchievements)) {
    $_SESSION['achievements'] = array_merge($achievements, $newAchievements);
    $achievements = $_SESSION['achievements'];
}

// Achievement definitions
$achievementDefs = [
    'first_case' => [
        'name' => 'First Case Solved',
        'description' => 'Successfully solved your first mystery case',
        'icon' => 'icon-case'
    ],
    'perfect_accuracy' => [
        'name' => 'Perfect Detective',
        'description' => '100% accuracy rate with 3+ cases solved',
        'icon' => 'icon-completed'
    ],
    'speed_demon' => [
        'name' => 'Speed Demon',
        'description' => 'Average case completion time under 20 minutes',
        'icon' => 'icon-time'
    ],
    'high_scorer' => [
        'name' => 'High Scorer',
        'description' => 'Accumulated 500+ total points',
        'icon' => 'icon-score'
    ],
    'veteran' => [
        'name' => 'Veteran Investigator',
        'description' => 'Solved 10+ cases',
        'icon' => 'icon-detective'
    ],
    'master_detective' => [
        'name' => 'Master Detective',
        'description' => 'Solved 20+ cases with 90%+ accuracy',
        'icon' => 'icon-analysis'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detective Profile - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_profile.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <header>
                <h1>Detective Profile</h1>
                <p>Your Investigation Journey & Achievements</p>
            </header>
            
            <main>
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="detective-info">
                        <div class="detective-avatar">
                            <span class="avatar-icon">DET</span>
                        </div>
                        <div class="detective-details">
                            <h2>Detective <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                            <p class="detective-rank"><?php echo $currentLevel['name']; ?></p>
                            <p class="join-date">Member since <?php echo date('F Y', $_SESSION['registration_time'] ?? time()); ?></p>
                        </div>
                    </div>
                    
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <span class="stat-number"><?php echo $totalCases; ?></span>
                            <span class="stat-label">Cases Solved</span>
                        </div>
                        <div class="quick-stat">
                            <span class="stat-number"><?php echo $totalScore; ?></span>
                            <span class="stat-label">Total Points</span>
                        </div>
                        <div class="quick-stat">
                            <span class="stat-number"><?php echo $accuracyRate; ?>%</span>
                            <span class="stat-label">Accuracy Rate</span>
                        </div>
                    </div>
                </div>
                
                <!-- Level Progress -->
                <?php if ($nextLevel): ?>
                <div class="level-progress">
                    <h3>Detective Level Progress</h3>
                    <div class="level-info">
                        <div class="current-level">
                            <h4><?php echo $currentLevel['name']; ?></h4>
                            <p>Current Level</p>
                        </div>
                        <div class="progress-bar-container">
                            <?php 
                            $scoreProgress = min(100, (($totalScore - $currentLevel['min_score']) / ($nextLevel['min_score'] - $currentLevel['min_score'])) * 100);
                            $casesProgress = min(100, (($totalCases - $currentLevel['min_cases']) / ($nextLevel['min_cases'] - $currentLevel['min_cases'])) * 100);
                            $overallProgress = min($scoreProgress, $casesProgress);
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $overallProgress; ?>%"></div>
                            </div>
                            <p><?php echo round($overallProgress); ?>% to <?php echo $nextLevel['name']; ?></p>
                        </div>
                        <div class="next-level">
                            <h4><?php echo $nextLevel['name']; ?></h4>
                            <p>Next Level</p>
                            <small>Requires: <?php echo $nextLevel['min_score']; ?> points, <?php echo $nextLevel['min_cases']; ?> cases</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Detailed Statistics -->
                <div class="detailed-stats">
                    <h3>Performance Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h4>Investigation Performance</h4>
                            <div class="stat-items">
                                <div class="stat-item">
                                    <span class="stat-label">Cases Attempted</span>
                                    <span class="stat-value"><?php echo $totalCases; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Cases Solved Correctly</span>
                                    <span class="stat-value"><?php echo $correctCases; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Success Rate</span>
                                    <span class="stat-value"><?php echo $accuracyRate; ?>%</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Average Score per Case</span>
                                    <span class="stat-value"><?php echo $averageScore; ?> pts</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <h4>Time Analysis</h4>
                            <div class="stat-items">
                                <div class="stat-item">
                                    <span class="stat-label">Total Investigation Time</span>
                                    <span class="stat-value"><?php echo formatTime($totalTimeSpent); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Average Case Duration</span>
                                    <span class="stat-value"><?php echo formatTime($averageTime); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Fastest Case</span>
                                    <span class="stat-value">
                                        <?php 
                                        $fastestTime = !empty($casesCompleted) ? min(array_column($casesCompleted, 'time_taken')) : 0;
                                        echo formatTime($fastestTime);
                                        ?>
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Investigation Efficiency</span>
                                    <span class="stat-value">
                                        <?php echo $averageTime <= 1800 ? 'Excellent' : ($averageTime <= 3600 ? 'Good' : 'Improving'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <h4>Case Difficulty Breakdown</h4>
                            <div class="difficulty-chart">
                                <?php foreach ($difficultyStats as $difficulty => $count): ?>
                                <div class="difficulty-bar">
                                    <div class="difficulty-info">
                                        <span><?php echo ucfirst($difficulty); ?></span>
                                        <span><?php echo $count; ?> cases</span>
                                    </div>
                                    <div class="bar-container">
                                        <div class="bar difficulty-<?php echo $difficulty; ?>" 
                                             style="width: <?php echo $totalCases > 0 ? ($count / $totalCases) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Achievements Section -->
                <div class="achievements-section">
                    <h3>Achievements & Badges</h3>
                    
                    <?php if (!empty($newAchievements)): ?>
                    <div class="new-achievements">
                        <h4>New Achievements Unlocked!</h4>
                        <div class="achievement-notifications">
                            <?php foreach ($newAchievements as $achievementId): ?>
                                <?php $achievement = $achievementDefs[$achievementId]; ?>
                                <div class="achievement-notification new">
                                    <div class="achievement-info">
                                        <h5><?php echo $achievement['name']; ?></h5>
                                        <p><?php echo $achievement['description']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="achievements-grid">
                        <?php foreach ($achievementDefs as $achievementId => $achievement): ?>
                        <div class="achievement-card <?php echo in_array($achievementId, $achievements) ? 'unlocked' : 'locked'; ?>">
                            <div class="achievement-details">
                                <h4><?php echo $achievement['name']; ?></h4>
                                <p><?php echo $achievement['description']; ?></p>
                                <?php if (in_array($achievementId, $achievements)): ?>
                                    <span class="achievement-status unlocked">Unlocked</span>
                                <?php else: ?>
                                    <span class="achievement-status locked">Locked</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Recent Cases -->
                <div class="recent-cases">
                    <h3>Recent Case History</h3>
                    <?php if (empty($casesCompleted)): ?>
                        <div class="no-cases">
                            <p>No cases completed yet. Start your detective journey!</p>
                            <a href="cases.php" class="btn btn-primary">Browse Cases</a>
                        </div>
                    <?php else: ?>
                        <div class="case-history">
                            <?php foreach (array_reverse(array_slice($casesCompleted, -5)) as $completedCase): ?>
                                <?php $caseData = getCase($completedCase['case_id']); ?>
                                <?php if ($caseData): ?>
                                <div class="case-history-item">
                                    <div class="case-info">
                                        <h4><?php echo htmlspecialchars($caseData['title']); ?></h4>
                                        <p><?php echo ucfirst($caseData['difficulty']); ?> • <?php echo ucfirst($caseData['category']); ?></p>
                                    </div>
                                    <div class="case-result">
                                        <span class="result-status <?php echo $completedCase['correct'] ? 'solved' : 'unsolved'; ?>">
                                            <?php echo $completedCase['correct'] ? 'Solved' : 'Unsolved'; ?>
                                        </span>
                                        <span class="case-score"><?php echo $completedCase['score']; ?> pts</span>
                                        <span class="case-time"><?php echo formatTime($completedCase['time_taken']); ?></span>
                                    </div>
                                    <div class="case-actions">
                                        <a href="investigate.php?case=<?php echo $completedCase['case_id']; ?>&review=1" class="btn btn-secondary btn-small">Review</a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($casesCompleted) > 5): ?>
                        <div class="view-all-cases">
                            <button class="btn btn-secondary" disabled>View All Cases</button>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Actions -->
                <div class="profile-actions">
                    <a href="cases.php" class="btn btn-primary">Browse New Cases</a>
                    <a href="leaderboard.php" class="btn btn-secondary">View Leaderboard</a>
                    <a href="../index.php" class="btn btn-secondary">Return to Dashboard</a>
                </div>
            </main>
        </div>
    </div>
    
</body>
</html>