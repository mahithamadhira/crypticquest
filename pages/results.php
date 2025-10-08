<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('../pages/login.php');
}

$caseId = $_GET['case'] ?? null;
$score = (int)($_GET['score'] ?? 0);

if (!$caseId) {
    redirectTo('cases.php');
}

$case = getCase($caseId);
if (!$case) {
    redirectTo('cases.php');
}

// Find the completed case in session
$completedCase = null;
if (isset($_SESSION['cases_completed'])) {
    foreach ($_SESSION['cases_completed'] as $completed) {
        if ($completed['case_id'] === $caseId) {
            $completedCase = $completed;
            break;
        }
    }
}

if (!$completedCase) {
    redirectTo('cases.php');
}

$isCorrect = $completedCase['correct'];
$timeTaken = $completedCase['time_taken'];
$hintsUsed = $completedCase['hints_used'];
$finalScore = $completedCase['score'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Results - <?php echo htmlspecialchars($case['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_results.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <header>
                <h1><?php echo $isCorrect ? 'Case Solved!' : 'Case Unsolved'; ?></h1>
                <p><?php echo htmlspecialchars($case['title']); ?></p>
            </header>
            
            <main>
            <!-- Results Header -->
            <div class="results-header <?php echo $isCorrect ? 'success' : 'failure'; ?>">
                <div class="result-status">
                    <?php if ($isCorrect): ?>
                        <h2>Excellent Detective Work!</h2>
                        <p>You correctly identified the culprit and solved the case.</p>
                    <?php else: ?>
                        <h2>Case Remains Open</h2>
                        <p>Your investigation led to an incorrect conclusion. Review the solution to improve your detective skills.</p>
                    <?php endif; ?>
                </div>
                
                <div class="final-score">
                    <h3>Final Score</h3>
                    <div class="score-display"><?php echo $finalScore; ?></div>
                    <p>out of <?php echo $case['points'] + ACCURACY_BONUS; ?> possible</p>
                </div>
            </div>
            
            <!-- Performance Breakdown -->
            <div class="performance-breakdown">
                <h3>Performance Analysis</h3>
                
                <div class="performance-grid">
                    <div class="performance-card">
                        <h4>Time Performance</h4>
                        <div class="performance-stat">
                            <span class="stat-value"><?php echo formatTime($timeTaken); ?></span>
                            <span class="stat-label">Time Taken</span>
                        </div>
                        <div class="performance-stat">
                            <span class="stat-value"><?php echo $case['estimated_time']; ?></span>
                            <span class="stat-label">Estimated Time</span>
                        </div>
                        <?php
                        $optimalMinutes = 30;
                        $actualMinutes = ceil($timeTaken / 60);
                        $timeRating = $actualMinutes <= $optimalMinutes ? 'Excellent' : 
                                     ($actualMinutes <= $optimalMinutes * 1.5 ? 'Good' : 'Needs Improvement');
                        ?>
                        <p class="performance-rating rating-<?php echo strtolower(str_replace(' ', '-', $timeRating)); ?>">
                            <?php echo $timeRating; ?> Time
                        </p>
                    </div>
                    
                    <div class="performance-card">
                        <h4>Investigation Efficiency</h4>
                        <div class="performance-stat">
                            <span class="stat-value"><?php echo $hintsUsed; ?></span>
                            <span class="stat-label">Hints Used</span>
                        </div>
                        <div class="performance-stat">
                            <span class="stat-value"><?php echo MAX_HINTS; ?></span>
                            <span class="stat-label">Max Hints</span>
                        </div>
                        <?php
                        $hintRating = $hintsUsed == 0 ? 'Perfect' : 
                                     ($hintsUsed <= 1 ? 'Excellent' : 
                                     ($hintsUsed <= 2 ? 'Good' : 'Needs Improvement'));
                        ?>
                        <p class="performance-rating rating-<?php echo strtolower(str_replace(' ', '-', $hintRating)); ?>">
                            <?php echo $hintRating; ?> Independence
                        </p>
                    </div>
                    
                    <div class="performance-card">
                        <h4>Accuracy</h4>
                        <div class="performance-stat">
                            <span class="stat-value"><?php echo $isCorrect ? '100%' : '0%'; ?></span>
                            <span class="stat-label">Solution Accuracy</span>
                        </div>
                        <div class="performance-stat">
                            <span class="stat-value"><?php echo $isCorrect ? '+' . ACCURACY_BONUS : '0'; ?></span>
                            <span class="stat-label">Bonus Points</span>
                        </div>
                        <p class="performance-rating rating-<?php echo $isCorrect ? 'perfect' : 'needs-improvement'; ?>">
                            <?php echo $isCorrect ? 'Perfect Analysis' : 'Incorrect Conclusion'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Score Breakdown -->
            <div class="score-breakdown">
                <h3>Score Calculation</h3>
                <div class="score-items">
                    <div class="score-item">
                        <span>Base Points:</span>
                        <span>+<?php echo $case['points']; ?></span>
                    </div>
                    <?php
                    $timePenalty = max(0, (ceil($timeTaken / 60) - 30) * TIME_PENALTY);
                    $hintPenalty = $hintsUsed * 0.1;
                    ?>
                    <div class="score-item">
                        <span>Time Penalty (<?php echo round($timePenalty * 100); ?>%):</span>
                        <span>-<?php echo round($case['points'] * $timePenalty); ?></span>
                    </div>
                    <div class="score-item">
                        <span>Hint Penalty (<?php echo round($hintPenalty * 100); ?>%):</span>
                        <span>-<?php echo round($case['points'] * $hintPenalty); ?></span>
                    </div>
                    <div class="score-item">
                        <span>Accuracy Bonus:</span>
                        <span><?php echo $isCorrect ? '+' . ACCURACY_BONUS : '0'; ?></span>
                    </div>
                    <div class="score-total">
                        <span>Final Score:</span>
                        <span><?php echo $finalScore; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Solution Comparison -->
            <div class="solution-comparison">
                <h3>Solution Analysis</h3>
                
                <div class="comparison-grid">
                    <!-- Your Solution -->
                    <div class="solution-card your-solution">
                        <h4>Your Solution</h4>
                        <div class="solution-details">
                            <p><strong>Primary Suspect:</strong> 
                                <?php echo htmlspecialchars($completedCase['suspect'] ?? 'Unknown'); ?>
                            </p>
                            <p><strong>Motive:</strong> <?php echo htmlspecialchars($completedCase['motive'] ?? 'Not specified'); ?></p>
                            <div class="solution-status <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                                <?php echo $isCorrect ? 'CORRECT' : 'INCORRECT'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Correct Solution -->
                    <div class="solution-card correct-solution">
                        <h4>Correct Solution</h4>
                        <div class="solution-details">
                            <p><strong>Primary Suspect:</strong> 
                                <?php echo htmlspecialchars($case['suspects'][$case['solution']['primary_suspect']]); ?>
                            </p>
                            <p><strong>Motive:</strong> <?php echo htmlspecialchars($case['solution']['motive']); ?></p>
                            <p><strong>Key Evidence:</strong> <?php echo htmlspecialchars($case['solution']['evidence_summary']); ?></p>
                            <div class="solution-explanation">
                                <h5>Explanation:</h5>
                                <p><?php echo htmlspecialchars($case['solution']['explanation']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detective Level Progress -->
            <div class="detective-progress">
                <h3>Detective Progress</h3>
                <div class="progress-info">
                    <div class="level-info">
                        <h4>Current Level: Detective</h4>
                        <p>Total Cases Solved: <?php echo count($_SESSION['cases_completed']); ?></p>
                        <p>Total Score: <?php echo $_SESSION['total_score']; ?></p>
                    </div>
                    
                    <div class="achievements">
                        <h4>Achievements Unlocked:</h4>
                        <div class="achievement-list">
                            <?php if (count($_SESSION['cases_completed']) >= 1): ?>
                                <div class="achievement">First Case Solved</div>
                            <?php endif; ?>
                            <?php if ($isCorrect && $hintsUsed == 0): ?>
                                <div class="achievement">Independent Investigator</div>
                            <?php endif; ?>
                            <?php if ($isCorrect && $timeTaken <= 1800): ?>
                                <div class="achievement">Speed Detective</div>
                            <?php endif; ?>
                            <?php if (count($_SESSION['cases_completed']) >= 5): ?>
                                <div class="achievement">Veteran Detective</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="results-actions">
                <a href="cases.php" class="btn btn-primary">Browse More Cases</a>
                <a href="investigate.php?case=<?php echo $case['id']; ?>&review=1" class="btn btn-secondary">Review This Case</a>
                <a href="../index.php" class="btn btn-secondary">Return to Dashboard</a>
                
                <?php if (!$isCorrect): ?>
                    <button class="btn btn-secondary" disabled>View Hints & Tips</button>
                <?php endif; ?>
            </div>
            
            <!-- Share Results -->
            <div class="share-results">
                <h3>Share Your Achievement</h3>
                <div class="share-buttons">
                    <button class="btn btn-secondary" disabled>Share Score</button>
                    <button class="btn btn-secondary" disabled>Copy Results</button>
                </div>
            </div>
        </main>
        </div>
    </div>
    
</body>
</html>