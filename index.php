<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/cases.php';

// Ensure user data is loaded from file if logged in
if (isset($_SESSION['username']) && file_exists(USERS_FILE)) {
    loadUserDataIntoSession($_SESSION['username']);
    // Restore any in-progress case
    restoreUserProgress($_SESSION['username']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cryptic Quest - Interactive Mystery Game</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/page_specific_css/page_home.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <?php if (!isset($_SESSION['username'])): ?>
                <header>
                    <h1>Cryptic Quest</h1>
                    <p>An Interactive Mystery Board Game Platform</p>
                </header>
            <?php endif; ?>
            
            <main>
            <?php if (!isset($_SESSION['username'])): ?>
                <div class="welcome-section">
                    <h2>Welcome Detective!</h2>
                    <p>Ready to solve intricate mysteries using logic, deduction, and collaborative problem-solving?</p>
                    <div class="auth-buttons">
                        <a href="pages/login.php" class="btn btn-primary">Login</a>
                        <a href="pages/register.php" class="btn btn-secondary">Register</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="dashboard">
                    <h2>Welcome back, Detective <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    
                    <!-- Detective Statistics -->
                    <div class="detective-stats">
                        <div class="stat-card">
                            <h3><?php echo count($_SESSION['cases_completed'] ?? []); ?></h3>
                            <p>Cases Solved</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $_SESSION['total_score'] ?? 0; ?></h3>
                            <p>Total Points</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo count($_SESSION['achievements'] ?? []); ?></h3>
                            <p>Achievements</p>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="pages/cases.php" class="btn btn-primary">Browse Cases</a>
                        <a href="pages/profile.php" class="btn btn-secondary">View Profile</a>
                        <a href="pages/leaderboard.php" class="btn btn-secondary">Leaderboard</a>
                        <a href="includes/logout.php" class="btn btn-logout">Logout</a>
                    </div>
                    
                    <!-- Current Case Progress -->
                    <?php 
                    // Check if current case is actually still valid (not completed)
                    $validCurrentCase = false;
                    $currentCaseData = null;
                    
                    // First check session for current case
                    if (isset($_SESSION['current_case'])) {
                        $caseId = $_SESSION['current_case']['case_id'];
                        $caseCompleted = false;
                        
                        // Check if this case is already completed
                        if (isset($_SESSION['cases_completed'])) {
                            foreach ($_SESSION['cases_completed'] as $completed) {
                                if ($completed['case_id'] === $caseId) {
                                    $caseCompleted = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$caseCompleted) {
                            $validCurrentCase = true;
                            $currentCaseData = $_SESSION['current_case'];
                        } else {
                            // Clear current_case from session if it's completed
                            unset($_SESSION['current_case']);
                        }
                    }
                    
                    // If no session case, check saved progress files
                    if (!$validCurrentCase && isset($_SESSION['username'])) {
                        $progressFile = DATA_DIR . 'progress/' . $_SESSION['username'] . '.json';
                        if (file_exists($progressFile)) {
                            $allProgress = readJsonFile($progressFile);
                            
                            // Find the most recent incomplete case
                            $mostRecent = null;
                            $mostRecentTime = 0;
                            
                            foreach ($allProgress as $caseId => $progress) {
                                // DEBUG: Check what data we have
                                echo "<!-- DEBUG: Checking case $caseId with evidence: " . (count($progress['evidence_collected'] ?? [])) . ", start_time: " . ($progress['start_time'] ?? 'MISSING') . " -->";
                                
                                // Better check - any case with a start_time means it's been started
                                $hasProgress = isset($progress['start_time']) && $progress['start_time'] > 0;
                                
                                if ($hasProgress && isset($progress['last_updated']) && $progress['last_updated'] > $mostRecentTime) {
                                    // Check if this case is not completed
                                    $caseCompleted = false;
                                    if (isset($_SESSION['cases_completed'])) {
                                        foreach ($_SESSION['cases_completed'] as $completed) {
                                            if ($completed['case_id'] === $caseId) {
                                                $caseCompleted = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    if (!$caseCompleted) {
                                        $mostRecent = $progress;
                                        $mostRecentTime = $progress['last_updated'];
                                    }
                                }
                            }
                            
                            if ($mostRecent) {
                                $validCurrentCase = true;
                                $currentCaseData = $mostRecent;
                            }
                        }
                    }
                    ?>
                    <?php if ($validCurrentCase): ?>
                        <div class="current-case">
                            <h3>Case in Progress</h3>
                            <?php 
                            $currentCase = getCase($currentCaseData['case_id']);
                            $timeElapsed = time() - $currentCaseData['start_time'];
                            ?>
                            <div class="completed-case-card in-progress mysterious-gradient">
                                <h4><?php echo htmlspecialchars($currentCase['title']); ?></h4>
                                <p class="case-status">IN PROGRESS</p>
                                <p class="case-time"><?php echo formatTime($timeElapsed); ?></p>
                                <p class="case-evidence"><?php echo count($currentCaseData['evidence_collected']); ?>/<?php echo count($currentCase['evidence']); ?> Evidence</p>
                                <a href="pages/investigate.php?case=<?php echo $currentCase['id']; ?>" class="btn btn-primary">Continue Investigation</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="section-divider"></div>
                    
                    <!-- Recent Investigations -->
                    <?php if (!empty($_SESSION['cases_completed'])): ?>
                    <div class="completed-cases">
                        <h3>Recent Investigations</h3>
                        <div class="completed-cases-grid">
                            <?php foreach (array_slice($_SESSION['cases_completed'], -3) as $completedCase): ?>
                                <div class="completed-case-card <?php echo ($completedCase['correct'] ?? false) ? 'solved' : 'unsolved'; ?>">
                                    <h4><?php echo htmlspecialchars($completedCase['title'] ?? 'Unknown Case'); ?></h4>
                                    <p class="case-status"><?php echo ($completedCase['correct'] ?? false) ? 'SOLVED' : 'UNSOLVED'; ?></p>
                                    <p class="case-score"><?php echo ($completedCase['score'] ?? 0); ?> points</p>
                                    <p class="case-time"><?php echo formatTime($completedCase['time_taken'] ?? 0); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>