<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('../pages/login.php');
}

// Get case ID
$caseId = $_GET['case'] ?? null;
if (!$caseId || !isset($_SESSION['current_case']) || $_SESSION['current_case']['case_id'] !== $caseId) {
    redirectTo('cases.php');
}

$case = getCase($caseId);
if (!$case) {
    redirectTo('cases.php');
}

// Check if enough evidence collected
$collectedEvidence = $_SESSION['current_case']['evidence_collected'] ?? [];
$totalEvidence = count($case['evidence']);
$minEvidence = ceil($totalEvidence * 0.5);

if (count($collectedEvidence) < $minEvidence) {
    redirectTo("investigate.php?case=$caseId");
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $primarySuspect = $_POST['primary_suspect'] ?? '';
    $motive = sanitizeInput($_POST['motive'] ?? '');
    // Use investigation notes as reasoning, or empty string if no notes
    $reasoning = $_SESSION['current_case']['investigation_notes'] ?? '';
    $confidence = 75; // Default confidence level
    $isPreview = isset($_POST['preview']);
    $isFinalSubmit = isset($_POST['submit_final']);
    
    // For preview, just validate and show current selections (no error needed)
    if ($isPreview) {
        // Just show the preview, validation errors will be shown but form won't process
        if (empty($primarySuspect)) {
            $error = 'Please select a primary suspect.';
        } elseif (empty($motive)) {
            $error = 'Please provide a motive for the crime.';
        } else {
            $success = 'Preview updated! Review your selections above and click "Submit Final Solution" when ready.';
        }
    } elseif ($isFinalSubmit) {
        // Only process final submission
        if (empty($primarySuspect)) {
            $error = 'Please select a primary suspect.';
        } elseif (empty($motive)) {
            $error = 'Please provide a motive for the crime.';
        } else {
        // Calculate score
        $timeElapsed = time() - $_SESSION['current_case']['start_time'];
        $hintsUsed = $_SESSION['current_case']['hints_used'] ?? 0;
        $isCorrect = ($primarySuspect === $case['solution']['primary_suspect']);
        
        $finalScore = calculateScore($case, $timeElapsed, $hintsUsed, $isCorrect);
        
        // Save solution to session
        $_SESSION['current_case']['solution'] = [
            'primary_suspect' => $primarySuspect,
            'motive' => $motive,
            'reasoning' => $reasoning,
            'confidence' => $confidence,
            'submitted_at' => time(),
            'is_correct' => $isCorrect,
            'score' => $finalScore
        ];
        
        // Mark case as completed
        if (!isset($_SESSION['cases_completed'])) {
            $_SESSION['cases_completed'] = [];
        }
        
        $_SESSION['cases_completed'][] = [
            'case_id' => $caseId,
            'title' => $case['title'],
            'completed_at' => time(),
            'score' => $finalScore,
            'time_taken' => $timeElapsed,
            'hints_used' => $hintsUsed,
            'correct' => $isCorrect
        ];
        
        // Update total score
        $_SESSION['total_score'] = ($_SESSION['total_score'] ?? 0) + $finalScore;
        
        // Save score using JSON helper
        $status = $isCorrect ? 'solved' : 'failed';
        addScoreEntry($_SESSION['user_id'], $_SESSION['username'], $caseId, $finalScore, $timeElapsed, $status);
        
        // Update user data with completed case
        $caseData = [
            'case_id' => $caseId,
            'score' => $finalScore,
            'completed_at' => getCurrentTimestamp(),
            'time_taken' => $timeElapsed,
            'status' => $status
        ];
        updateUserData($_SESSION['user_id'], $_SESSION['username'], $caseData);
        
        // Update leaderboard
        updateLeaderboard();
        
        // Clear current case
        unset($_SESSION['current_case']);
        
        // Redirect to results
        redirectTo("results.php?case=$caseId&score=$finalScore");
        }
    }
}

$timeElapsed = time() - $_SESSION['current_case']['start_time'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Solution - <?php echo htmlspecialchars($case['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_submit_solution.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <header>
                <h1>Submit Your Solution</h1>
                <p><?php echo htmlspecialchars($case['title']); ?></p>
            </header>
            
            <main>
                <!-- Submit Form -->
                <form method="POST" id="solutionForm" class="submit-form">
                <div class="submit-solution-layout">
                    <!-- Left Panel - Solution Form -->
                    <div class="left-panel">
                        <!-- Primary Suspect Selection -->
                        <div class="solution-section">
                            <h3>Primary Suspect</h3>
                            <div class="suspect-selection">
                                <?php foreach ($case['suspects'] as $suspectId => $suspectName): ?>
                                    <label class="suspect-option">
                                        <input type="radio" name="primary_suspect" value="<?php echo $suspectId; ?>" 
                                               <?php echo (isset($_POST['primary_suspect']) && $_POST['primary_suspect'] === $suspectId) ? 'checked' : ''; ?>>
                                        <div class="suspect-card">
                                            <h4><?php echo htmlspecialchars($suspectName); ?></h4>
                                            <p>Select as primary suspect</p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Primary Motive Selection -->
                        <div class="solution-section">
                            <h3>Primary Motive</h3>
                            <div class="motive-selection">
                                <select name="motive" id="motive" class="motive-dropdown" required>
                                    <option value="">Select primary motive...</option>
                                    <option value="financial_desperation" <?php echo (isset($_POST['motive']) && $_POST['motive'] === 'financial_desperation') ? 'selected' : ''; ?>>Financial Desperation</option>
                                    <option value="revenge" <?php echo (isset($_POST['motive']) && $_POST['motive'] === 'revenge') ? 'selected' : ''; ?>>Revenge</option>
                                    <option value="jealousy" <?php echo (isset($_POST['motive']) && $_POST['motive'] === 'jealousy') ? 'selected' : ''; ?>>Jealousy</option>
                                    <option value="opportunity_theft" <?php echo (isset($_POST['motive']) && $_POST['motive'] === 'opportunity_theft') ? 'selected' : ''; ?>>Opportunity/Theft</option>
                                    <option value="blackmail" <?php echo (isset($_POST['motive']) && $_POST['motive'] === 'blackmail') ? 'selected' : ''; ?>>Blackmail</option>
                                    <option value="covering_tracks" <?php echo (isset($_POST['motive']) && $_POST['motive'] === 'covering_tracks') ? 'selected' : ''; ?>>Covering Tracks</option>
                                    <option value="coercion" <?php echo (isset($_POST['motive']) && $_POST['motive'] === 'coercion') ? 'selected' : ''; ?>>Coercion</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Cross Reference Panel -->
                        <div class="solution-section">
                            <h3>Evidence Cross-Reference Analysis</h3>
                            <div class="cross-ref-display">
                                <?php 
                                $selectedEvidence = $_SESSION['current_case']['cross_reference']['selected_evidence'] ?? [];
                                $connections = $_SESSION['current_case']['cross_reference']['connections'] ?? [];
                                ?>
                                
                                <?php if (!empty($selectedEvidence)): ?>
                                    <div class="crossref-mini-canvas">
                                        <?php foreach ($selectedEvidence as $index => $evidenceId): ?>
                                            <?php if (isset($case['evidence'][$evidenceId])): ?>
                                                <div class="mini-evidence-node" style="left: <?php echo 20 + ($index * 150); ?>px; top: <?php echo 20 + (($index % 2) * 90); ?>px;">
                                                    <span class="mini-node-label"><?php echo htmlspecialchars($case['evidence'][$evidenceId]['name']); ?></span>
                                                    <div class="mini-relevance relevance-<?php echo $case['evidence'][$evidenceId]['relevance']; ?>"></div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        
                                        <!-- Edge-to-edge connections -->
                                        <?php foreach ($connections as $connection): ?>
                                            <?php 
                                            $evidence1Index = array_search($connection['evidence1'], $selectedEvidence);
                                            $evidence2Index = array_search($connection['evidence2'], $selectedEvidence);
                                            if ($evidence1Index !== false && $evidence2Index !== false):
                                                // Calculate box positions
                                                $box1X = 20 + ($evidence1Index * 150);
                                                $box1Y = 20 + (($evidence1Index % 2) * 90);
                                                $box2X = 20 + ($evidence2Index * 150);
                                                $box2Y = 20 + (($evidence2Index % 2) * 90);
                                                
                                                // Box dimensions
                                                $boxWidth = 120;
                                                $boxHeight = 60;
                                                
                                                // Calculate centers
                                                $center1X = $box1X + ($boxWidth / 2);
                                                $center1Y = $box1Y + ($boxHeight / 2);
                                                $center2X = $box2X + ($boxWidth / 2);
                                                $center2Y = $box2Y + ($boxHeight / 2);
                                                
                                                // Calculate direction vector
                                                $dx = $center2X - $center1X;
                                                $dy = $center2Y - $center1Y;
                                                $distance = sqrt($dx * $dx + $dy * $dy);
                                                
                                                if ($distance > 0) {
                                                    // Normalize direction
                                                    $unitX = $dx / $distance;
                                                    $unitY = $dy / $distance;
                                                    
                                                    // Calculate edge points
                                                    $edge1X = $center1X + ($unitX * ($boxWidth / 2));
                                                    $edge1Y = $center1Y + ($unitY * ($boxHeight / 2));
                                                    $edge2X = $center2X - ($unitX * ($boxWidth / 2));
                                                    $edge2Y = $center2Y - ($unitY * ($boxHeight / 2));
                                                    
                                                    // Calculate connection line
                                                    $lineLength = sqrt(pow($edge2X - $edge1X, 2) + pow($edge2Y - $edge1Y, 2));
                                                    $lineAngle = atan2($edge2Y - $edge1Y, $edge2X - $edge1X) * 180 / pi();
                                                }
                                            ?>
                                                <div class="mini-connection connection-<?php echo $connection['type']; ?>" 
                                                     style="left: <?php echo $edge1X; ?>px; top: <?php echo $edge1Y; ?>px; width: <?php echo $lineLength; ?>px; transform: rotate(<?php echo $lineAngle; ?>deg);"></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="crossref-summary">
                                        <p><strong>Evidence in Analysis:</strong> <?php echo count($selectedEvidence); ?> pieces</p>
                                        <p><strong>Connections Made:</strong> <?php echo count($connections); ?> connections</p>
                                    </div>
                                <?php else: ?>
                                    <div class="no-crossref">
                                        <p>No cross-reference analysis available.</p>
                                        <a href="investigate.php?case=<?php echo $caseId; ?>" class="btn btn-secondary btn-sm">Return to Investigation</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Panel - Evidence & Notes -->
                    <div class="right-panel">
                        <!-- Key Evidence Collected -->
                        <div class="evidence-section">
                            <h3>Key Evidence Collected</h3>
                            <div class="evidence-list">
                                <?php foreach ($collectedEvidence as $evidenceId): ?>
                                    <?php $evidence = $case['evidence'][$evidenceId]; ?>
                                    <div class="evidence-item">
                                        <div class="evidence-header">
                                            <h4><?php echo htmlspecialchars($evidence['name']); ?></h4>
                                            <span class="relevance-badge relevance-<?php echo $evidence['relevance']; ?>">
                                                <?php echo strtoupper($evidence['relevance']); ?>
                                            </span>
                                        </div>
                                        <p class="evidence-content"><?php echo htmlspecialchars($evidence['content']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Detective Notes -->
                        <div class="notes-section">
                            <h3>Detective Notes</h3>
                            <div class="notes-content">
                                <?php 
                                $investigationNotes = $_SESSION['current_case']['investigation_notes'] ?? '';
                                ?>
                                <?php if (!empty($investigationNotes)): ?>
                                    <div class="saved-notes">
                                        <h4>Your Investigation Notes:</h4>
                                        <div class="notes-text"><?php echo nl2br(htmlspecialchars($investigationNotes)); ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="no-notes">
                                        <p>No investigation notes saved.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="case-summary">
                                    <h4>Case Summary:</h4>
                                    <div class="summary-stats">
                                        <div class="stat-item">
                                            <span class="stat-label">Evidence Collected:</span>
                                            <span class="stat-value"><?php echo count($collectedEvidence); ?>/<?php echo $totalEvidence; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Time Elapsed:</span>
                                            <span class="stat-value"><?php echo formatTime($timeElapsed); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Hints Used:</span>
                                            <span class="stat-value"><?php echo $_SESSION['current_case']['hints_used'] ?? 0; ?>/<?php echo MAX_HINTS; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Max Points:</span>
                                            <span class="stat-value"><?php echo $case['points']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions and Alerts -->
                <div class="form-footer">
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <a href="investigate.php?case=<?php echo $caseId; ?>" class="btn btn-secondary">Return to Investigation</a>
                        <button type="submit" name="submit_final" value="1" class="btn btn-primary">Submit Solution</button>
                    </div>
                </div>
                </form>
        </main>
        </div>
    </div>
    
</body>
</html>