<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('../pages/login.php');
}

// Get case ID from URL
$caseId = $_GET['case'] ?? null;
$startNew = isset($_GET['start']);
$isReview = isset($_GET['review']);
$selectedEvidence = $_GET['evidence'] ?? null;

if (!$caseId) {
    redirectTo('cases.php');
}

// Get case data
$case = getCase($caseId);
if (!$case) {
    redirectTo('cases.php');
}

// Handle starting new case or ensure case is initialized
if ($startNew || !isset($_SESSION['current_case']) || $_SESSION['current_case']['case_id'] !== $caseId) {
    initializeCaseProgress($caseId);
}

// Handle evidence collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_evidence'])) {
    $evidenceId = $_POST['evidence_id'];
    $collected = collectEvidence($evidenceId);
    
    // Auto-save progress after collecting evidence
    saveCaseProgressToFile();
    
    // Redirect to prevent form resubmission
    $redirectUrl = "investigate.php?case=" . urlencode($caseId);
    if ($selectedEvidence) {
        $redirectUrl .= "&evidence=" . urlencode($selectedEvidence);
    }
    if ($isReview) {
        $redirectUrl .= "&review=1";
    }
    header("Location: " . $redirectUrl);
    exit();
}

// Handle cross-reference actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $needsRedirect = false;
    
    if (isset($_POST['add_to_crossref'])) {
        $evidenceId = $_POST['evidence_id'];
        addToCrossReference($evidenceId);
        saveCaseProgressToFile(); // Auto-save
        $needsRedirect = true;
    } elseif (isset($_POST['remove_from_crossref'])) {
        $evidenceId = $_POST['evidence_id'];
        removeFromCrossReference($evidenceId);
        saveCaseProgressToFile(); // Auto-save
        $needsRedirect = true;
    } elseif (isset($_POST['clear_crossref'])) {
        clearCrossReference();
        saveCaseProgressToFile(); // Auto-save
        $needsRedirect = true;
    } elseif (isset($_POST['connect_evidence'])) {
        $evidence1 = $_POST['evidence1'];
        $evidence2 = $_POST['evidence2'];
        $connectionType = $_POST['connection_type'] ?? 'related';
        addConnection($evidence1, $evidence2, $connectionType);
        saveCaseProgressToFile(); // Auto-save
        $needsRedirect = true;
    } elseif (isset($_POST['save_notes'])) {
        $notes = $_POST['investigation_notes'] ?? '';
        updateInvestigationNotes($notes);
        saveCaseProgressToFile(); // Auto-save
        $needsRedirect = true;
    } elseif (isset($_POST['get_hint'])) {
        if (!isset($_SESSION['current_case']['hints_used'])) {
            $_SESSION['current_case']['hints_used'] = 0;
        }
        if ($_SESSION['current_case']['hints_used'] < MAX_HINTS) {
            $_SESSION['current_case']['hints_used']++;
            updateCaseProgress();
        }
        saveCaseProgressToFile(); // Auto-save
        $needsRedirect = true;
    } elseif (isset($_POST['reset_case'])) {
        // Reset case - wipe all progress and start completely fresh
        unset($_SESSION['current_case']);
        
        // Delete any saved progress from file system
        if (isset($_SESSION['username'])) {
            deleteCaseProgress($_SESSION['username'], $caseId);
        }
        
        // Initialize completely fresh case (force new start)
        $_SESSION['current_case'] = [
            'case_id' => $caseId,
            'start_time' => time(),
            'evidence_collected' => [],
            'hints_used' => 0,
            'investigation_notes' => '',
            'cross_reference' => [
                'selected_evidence' => [],
                'connections' => []
            ]
        ];
        
        $needsRedirect = true;
    }
    
    // Redirect after any POST action to prevent form resubmission
    if ($needsRedirect) {
        $redirectUrl = "investigate.php?case=" . urlencode($caseId);
        if ($selectedEvidence) {
            $redirectUrl .= "&evidence=" . urlencode($selectedEvidence);
        }
        if ($isReview) {
            $redirectUrl .= "&review=1";
        }
        header("Location: " . $redirectUrl);
        exit();
    }
}

// Check if case is completed
$isCompleted = false;
if (isset($_SESSION['cases_completed'])) {
    foreach ($_SESSION['cases_completed'] as $completed) {
        if ($completed['case_id'] === $case['id']) {
            $isCompleted = true;
            break;
        }
    }
}

// Calculate progress
$totalEvidence = count($case['evidence']);
$collectedEvidence = isset($_SESSION['current_case']['evidence_collected']) ? 
    count($_SESSION['current_case']['evidence_collected']) : 0;
$progressPercent = $totalEvidence > 0 ? ($collectedEvidence / $totalEvidence) * 100 : 0;

// Calculate time elapsed
$timeElapsed = 0;
if (isset($_SESSION['current_case']['start_time'])) {
    $timeElapsed = time() - $_SESSION['current_case']['start_time'];
} else {
    // If no start time, initialize the case
    if (!isset($_SESSION['current_case'])) {
        initializeCaseProgress($caseId);
        $timeElapsed = 0;
    }
}

// Get current score preview
$scorePreview = null;
if (!$isCompleted && !$isReview) {
    $scorePreview = getCurrentScorePreview($caseId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($case['title']); ?> - Investigation</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_investigate.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <!-- Investigation Header -->
            
        <div class="main-investigation-layout">
            <!-- Left Side - 65% -->
            <div class="left-section">
                <!-- Case Header -->
                <div class="case-header">
                    <div class="case-title-section">
                        <h1><?php echo htmlspecialchars($case['title']); ?></h1>
                        <div class="case-actions">
                            <?php if ($isCompleted): ?>
                                <a href="cases.php" class="btn btn-primary">Browse More Cases</a>
                            <?php elseif ($isReview): ?>
                                <a href="cases.php" class="btn btn-secondary">Back to Cases</a>
                            <?php else: ?>
                                <a href="cases.php" class="btn btn-secondary">Abandon Case</a>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="reset_case" class="btn btn-warning">
                                        Reset Case
                                    </button>
                                </form>
                                <?php if ($collectedEvidence >= ceil($totalEvidence * 0.5)): ?>
                                    <a href="submit_solution.php?case=<?php echo $case['id']; ?>" class="btn btn-primary">Submit Solution</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Submit Solution</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h2><?php echo htmlspecialchars($case['description']); ?></h2>
                    <div class="case-scenario">
                        <p><?php echo htmlspecialchars($case['scenario']); ?></p>
                    </div>
                </div>
                
                <!-- High-Resolution Evidence Display -->
                <div class="evidence-display">
                    <h3>Evidence Display</h3>
                    <div class="evidence-viewer">
                        <?php if ($selectedEvidence && isset($case['evidence'][$selectedEvidence])): ?>
                            <?php $evidence = $case['evidence'][$selectedEvidence]; ?>
                            <!-- DEBUG: Selected Evidence: <?php echo $selectedEvidence; ?> -->
                            <!-- DEBUG: Evidence Name: <?php echo $evidence['name']; ?> -->
                            <div class="evidence-details">
                                <div class="evidence-header-section">
                                    <h4><?php echo htmlspecialchars($evidence['name']); ?></h4>
                                    <?php if (!hasEvidence($selectedEvidence) && !$isCompleted && !$isReview): ?>
                                        <form method="POST" class="collect-form-inline">
                                            <input type="hidden" name="evidence_id" value="<?php echo $selectedEvidence; ?>">
                                            <button type="submit" name="collect_evidence" class="btn btn-primary collect-btn-inline">
                                                Collect Evidence
                                            </button>
                                        </form>
                                    <?php elseif (hasEvidence($selectedEvidence) || $isCompleted || $isReview): ?>
                                        <div class="evidence-collected-inline">
                                            <span class="collected-badge">✓ Collected</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="evidence-content">
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($evidence['description']); ?></p>
                                    <p><strong>Discovery:</strong> <?php echo htmlspecialchars($evidence['discovery_text']); ?></p>
                                    <p><strong>Content:</strong> <?php echo htmlspecialchars($evidence['content']); ?></p>
                                    <p><strong>Relevance:</strong> 
                                        <span class="relevance-<?php echo $evidence['relevance']; ?>">
                                            <?php echo ucfirst($evidence['relevance']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="evidence-placeholder">
                                <div class="evidence-icon">📄</div>
                                <p>Click on evidence from the list to view details</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Investigation Notes -->
                <div class="notes-section">
                    <h3>Investigation Notes</h3>
                    <form method="POST" class="notes-form">
                        <textarea name="investigation_notes" class="notes-textarea" placeholder="Add your observations and notes here..."><?php echo htmlspecialchars($_SESSION['current_case']['investigation_notes'] ?? ''); ?></textarea>
                        <button type="submit" name="save_notes" class="btn btn-secondary save-notes-btn">Save Notes</button>
                    </form>
                </div>
                
                <!-- Evidence Cross-Reference System -->
                <div class="cross-reference-section">
                    <h3>Evidence Cross-Reference</h3>
                    <div class="cross-ref-workspace">
                        <div class="cross-ref-instructions">
                            <p>Use + buttons on collected evidence to add to analysis workspace</p>
                        </div>
                        <div class="cross-ref-canvas">
                            <?php 
                            $selectedEvidence = $_SESSION['current_case']['cross_reference']['selected_evidence'] ?? [];
                            $connections = $_SESSION['current_case']['cross_reference']['connections'] ?? [];
                            ?>
                            <?php if (empty($selectedEvidence)): ?>
                                <div class="canvas-instructions">
                                    <p>Evidence nodes will appear here when added to cross-reference</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($selectedEvidence as $index => $evidenceId): ?>
                                    <?php if (isset($case['evidence'][$evidenceId])): ?>
                                        <div class="evidence-node" style="left: <?php echo 20 + ($index * 150); ?>px; top: <?php echo 20 + (($index % 2) * 90); ?>px;">
                                            <span class="node-label"><?php echo htmlspecialchars($case['evidence'][$evidenceId]['name']); ?></span>
                                            <div class="node-relevance relevance-<?php echo $case['evidence'][$evidenceId]['relevance']; ?>"></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <!-- Render connections -->
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
                                        <div class="evidence-connection connection-<?php echo $connection['type']; ?>" 
                                             style="left: <?php echo $edge1X; ?>px; top: <?php echo $edge1Y; ?>px; width: <?php echo $lineLength; ?>px; transform: rotate(<?php echo $lineAngle; ?>deg);"></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cross-ref-selected">
                            <h4>Selected Evidence (<?php echo count($selectedEvidence); ?>)</h4>
                            <div class="selected-evidence-list">
                                <?php if (empty($selectedEvidence)): ?>
                                    <div class="empty-selection">
                                        <p>No evidence selected for cross-reference</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($selectedEvidence as $evidenceId): ?>
                                        <?php if (isset($case['evidence'][$evidenceId])): ?>
                                            <div class="crossref-evidence-item">
                                                <span class="evidence-name"><?php echo htmlspecialchars($case['evidence'][$evidenceId]['name']); ?></span>
                                                <span class="relevance-indicator relevance-<?php echo $case['evidence'][$evidenceId]['relevance']; ?>">
                                                    <?php echo strtoupper($case['evidence'][$evidenceId]['relevance']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (count($selectedEvidence) >= 2 && !$isReview && !$isCompleted): ?>
                            <div class="connection-builder">
                                <h4>Create Connection</h4>
                                <form method="POST" class="connection-form">
                                    <select name="evidence1" class="form-select">
                                        <?php foreach ($selectedEvidence as $evidenceId): ?>
                                            <option value="<?php echo $evidenceId; ?>"><?php echo htmlspecialchars($case['evidence'][$evidenceId]['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="evidence2" class="form-select">
                                        <?php foreach ($selectedEvidence as $evidenceId): ?>
                                            <option value="<?php echo $evidenceId; ?>"><?php echo htmlspecialchars($case['evidence'][$evidenceId]['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="connection_type" class="form-select">
                                        <option value="related">Related</option>
                                        <option value="contradicts">Contradicts</option>
                                        <option value="supports">Supports</option>
                                        <option value="timeline">Timeline</option>
                                    </select>
                                    <button type="submit" name="connect_evidence" class="btn btn-primary">Connect</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="cross-ref-actions">
                            <?php if (!empty($selectedEvidence) && !$isReview && !$isCompleted): ?>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="clear_crossref" class="btn btn-secondary">Clear All</button>
                                </form>
                            <?php endif; ?>
                            <span class="analysis-info">Connections: <?php echo count($connections); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - 35% -->
            <div class="right-section">
                <!-- Evidence List -->
                <div class="evidence-list-section">
                    <h3>Evidence List (<?php echo $collectedEvidence; ?>/<?php echo $totalEvidence; ?>)</h3>
                    
                    <?php if ($isCompleted || $isReview): ?>
                        <div class="alert-info">
                            <p><strong>Case Review Mode:</strong> All evidence is displayed for review purposes.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="evidence-list">
                        <?php foreach ($case['evidence'] as $evidenceId => $evidence): ?>
                            <?php 
                            $isCollected = hasEvidence($evidenceId) || $isCompleted || $isReview;
                            $isSelected = ($selectedEvidence === $evidenceId);
                            $inCrossRef = isset($_SESSION['current_case']['cross_reference']['selected_evidence']) && 
                                         in_array($evidenceId, $_SESSION['current_case']['cross_reference']['selected_evidence']);
                            ?>
                            <div class="evidence-list-item <?php echo $isCollected ? 'collected' : ''; ?> <?php echo $isSelected ? 'selected' : ''; ?> <?php echo $inCrossRef ? 'in-crossref' : ''; ?>">
                                <a href="?case=<?php echo $caseId; ?>&evidence=<?php echo $evidenceId; ?><?php echo $isReview ? '&review=1' : ''; ?>" 
                                   class="evidence-link">
                                    <h4><?php echo htmlspecialchars($evidence['name']); ?></h4>
                                </a>
                                <?php if ($isCollected && !$isReview && !$isCompleted): ?>
                                    <div class="evidence-actions">
                                        <?php if (!$inCrossRef): ?>
                                            <form method="POST" class="crossref-form">
                                                <input type="hidden" name="evidence_id" value="<?php echo $evidenceId; ?>">
                                                <button type="submit" name="add_to_crossref" class="btn btn-crossref-add" title="Add to Cross-Reference">+</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="crossref-form">
                                                <input type="hidden" name="evidence_id" value="<?php echo $evidenceId; ?>">
                                                <button type="submit" name="remove_from_crossref" class="btn btn-crossref-remove" title="Remove from Cross-Reference">-</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Suspects -->
                <div class="suspects-section">
                    <h3>Suspects (<?php echo count($case['suspects']); ?>)</h3>
                    <div class="suspects-grid">
                        <?php foreach ($case['suspects'] as $suspectId => $suspectName): ?>
                            <div class="suspect-card">
                                <h4><?php echo htmlspecialchars($suspectName); ?></h4>
                                <p>Person of Interest</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Hints Section -->
                <?php if (!$isCompleted && !$isReview): ?>
                <div class="hints-section">
                    <h3>Investigation Hints</h3>
                    <div class="hints-container">
                        <?php 
                        $hintsUsed = $_SESSION['current_case']['hints_used'] ?? 0;
                        $availableHints = isset($case['hints']) ? $case['hints'] : [];
                        ?>
                        
                        <?php if ($hintsUsed > 0 && !empty($availableHints)): ?>
                            <div class="revealed-hints">
                                <?php for ($i = 0; $i < min($hintsUsed, count($availableHints)); $i++): ?>
                                    <div class="hint-item revealed">
                                        <span class="hint-number">Hint <?php echo $i + 1; ?>:</span>
                                        <span class="hint-text"><?php echo htmlspecialchars($availableHints[$i]); ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="hint-controls">
                            <?php if ($hintsUsed < MAX_HINTS && $hintsUsed < count($availableHints)): ?>
                                <form method="POST" class="hint-form">
                                    <div class="hint-info">
                                        <p>Hints used: <?php echo $hintsUsed; ?>/<?php echo MAX_HINTS; ?></p>
                                        <p class="hint-warning">Each hint reduces your final score</p>
                                    </div>
                                    <button type="submit" name="get_hint" class="btn btn-warning">
                                        Get Hint (<?php echo $hintsUsed + 1; ?>/<?php echo MAX_HINTS; ?>)
                                    </button>
                                </form>
                            <?php elseif ($hintsUsed >= MAX_HINTS): ?>
                                <div class="hints-exhausted">
                                    <p>All hints have been used (<?php echo MAX_HINTS; ?>/<?php echo MAX_HINTS; ?>)</p>
                                </div>
                            <?php elseif ($hintsUsed >= count($availableHints)): ?>
                                <div class="hints-exhausted">
                                    <p>No more hints available for this case</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Case Metadata Accordion -->
                <div class="case-metadata-section">
                    <input type="checkbox" id="metadata-toggle" class="metadata-toggle">
                    <label for="metadata-toggle" class="metadata-header">
                        <h3>Case Metadata</h3>
                        <span class="expand-icon">▼</span>
                    </label>
                    <div class="metadata-content">
                        <div class="metadata-grid">
                            <?php if ($scorePreview && !$isCompleted && !$isReview): ?>
                                <div class="meta-item score-preview">
                                    <span class="meta-label">Current Score</span>
                                    <span class="meta-value score-value"><?php echo $scorePreview['total_score']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Max Points</span>
                                    <span class="meta-value"><?php echo $case['points']; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <span class="meta-label">Evidence Found</span>
                                <span class="meta-value"><?php echo $collectedEvidence; ?>/<?php echo $totalEvidence; ?></span>
                            </div>
                            
                            <?php if (!$isReview && !$isCompleted): ?>
                            <div class="meta-item">
                                <span class="meta-label">Time Elapsed</span>
                                <span class="meta-value"><?php echo formatTime($timeElapsed); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <span class="meta-label">Hints Used</span>
                                <span class="meta-value"><?php echo $_SESSION['current_case']['hints_used'] ?? 0; ?>/<?php echo MAX_HINTS; ?></span>
                            </div>
                            
                            <?php if ($scorePreview && !$isCompleted && !$isReview): ?>
                                <div class="meta-item">
                                    <span class="meta-label"></span>
                                    <a href="score_breakdown.php?case=<?php echo $caseId; ?>" class="btn btn-secondary btn-sm">Full Details</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    
</body>
</html>