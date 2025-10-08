<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('../pages/login.php');
}

// Check if there's an active case
if (!isset($_SESSION['current_case'])) {
    redirectTo('cases.php');
}

$case = getCase($_SESSION['current_case']['case_id']);
if (!$case) {
    redirectTo('cases.php');
}

// Get collected evidence
$collectedEvidence = $_SESSION['current_case']['evidence_collected'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidence Analysis - <?php echo htmlspecialchars($case['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_analyze_evidence.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <header>
                <h1>Evidence Analysis Lab</h1>
                <p>Cross-Reference and Connect Evidence</p>
            </header>
            
            <main>
            
            <!-- Evidence Cross-Reference Tool -->
            <div class="analysis-workspace">
                <div class="evidence-board">
                    <h3>Evidence Cross-Reference Board</h3>
                    <p>Drag and drop evidence to create connections and build your case theory.</p>
                    
                    <div class="evidence-connections">
                        <?php if (empty($collectedEvidence)): ?>
                            <div class="no-evidence">
                                <p>No evidence collected yet. Return to the investigation to gather clues.</p>
                                <a href="investigate.php?case=<?php echo $case['id']; ?>" class="btn btn-primary">Continue Investigation</a>
                            </div>
                        <?php else: ?>
                            <div class="connection-canvas">
                                <div class="evidence-nodes">
                                    <?php foreach ($collectedEvidence as $index => $evidenceId): ?>
                                        <?php $evidence = $case['evidence'][$evidenceId]; ?>
                                        <div class="evidence-node" 
                                             data-evidence-id="<?php echo $evidenceId; ?>"
                                             data-relevance="<?php echo $evidence['relevance']; ?>"
                                             style="left: <?php echo 100 + ($index * 200); ?>px; top: <?php echo 100 + ($index % 3) * 150; ?>px;">
                                            <div class="node-header">
                                                <h4><?php echo htmlspecialchars($evidence['name']); ?></h4>
                                                <span class="relevance-badge relevance-<?php echo $evidence['relevance']; ?>">
                                                    <?php echo strtoupper($evidence['relevance']); ?>
                                                </span>
                                            </div>
                                            <p><?php echo htmlspecialchars(substr($evidence['content'], 0, 100)) . '...'; ?></p>
                                            <div class="node-actions">
                                                <button class="btn-small" disabled>View Details</button>
                                                <button class="btn-small" disabled>Connect</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Connection Lines (SVG) -->
                                <svg class="connection-lines" width="100%" height="100%">
                                </svg>
                            </div>
                            
                            <!-- Connection Tools -->
                            <div class="connection-tools">
                                <h4>Evidence Connections</h4>
                                <div class="connection-list" id="connectionList">
                                    <p>No connections made yet. Click "Connect" on evidence pieces to link them.</p>
                                </div>
                                
                                <div class="connection-analysis">
                                    <h4>Connection Analysis</h4>
                                    <div id="analysisResults">
                                        <p>Create evidence connections to see analysis results.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Suspects Analysis -->
                <div class="suspects-analysis">
                    <h3>Suspect Analysis</h3>
                    <div class="suspects-evidence-grid">
                        <?php foreach ($case['suspects'] as $suspectId => $suspectName): ?>
                            <div class="suspect-analysis-card" data-suspect="<?php echo $suspectId; ?>">
                                <h4><?php echo htmlspecialchars($suspectName); ?></h4>
                                <div class="suspect-evidence-links">
                                    <h5>Connected Evidence:</h5>
                                    <div class="evidence-links" id="suspect-<?php echo $suspectId; ?>-evidence">
                                        <p>No evidence linked yet.</p>
                                    </div>
                                </div>
                                <div class="suspect-score">
                                    <h5>Suspicion Level:</h5>
                                    <div class="suspicion-meter">
                                        <div class="suspicion-fill" data-suspect="<?php echo $suspectId; ?>" style="width: 0%"></div>
                                    </div>
                                    <span class="suspicion-text">0% Likely</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Theory Builder -->
                <div class="theory-builder">
                    <h3>Case Theory Builder</h3>
                    <form id="theoryForm">
                        <div class="theory-section">
                            <label for="primarySuspect">Primary Suspect:</label>
                            <select id="primarySuspect" name="primary_suspect" class="form-control">
                                <option value="">Select a suspect...</option>
                                <?php foreach ($case['suspects'] as $suspectId => $suspectName): ?>
                                    <option value="<?php echo $suspectId; ?>"><?php echo htmlspecialchars($suspectName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="theory-section">
                            <label for="motive">Suspected Motive:</label>
                            <textarea id="motive" name="motive" class="form-control" rows="3" 
                                      placeholder="Based on the evidence, what do you think motivated this crime?"></textarea>
                        </div>
                        
                        <div class="theory-section">
                            <label for="keyEvidence">Key Evidence Supporting Your Theory:</label>
                            <div class="key-evidence-selector">
                                <?php foreach ($collectedEvidence as $evidenceId): ?>
                                    <?php $evidence = $case['evidence'][$evidenceId]; ?>
                                    <label class="evidence-checkbox">
                                        <input type="checkbox" name="key_evidence[]" value="<?php echo $evidenceId; ?>">
                                        <?php echo htmlspecialchars($evidence['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="theory-section">
                            <label for="timeline">Sequence of Events:</label>
                            <textarea id="timeline" name="timeline" class="form-control" rows="4" 
                                      placeholder="Describe how you think the crime unfolded..."></textarea>
                        </div>
                        
                        <div class="theory-actions">
                            <form action="submit_solution.php" method="get" style="display: inline;">
                                <input type="hidden" name="case" value="<?php echo $case['id']; ?>">
                                <button type="submit" class="btn btn-primary">Proceed to Solution</button>
                            </form>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        </div>
    </div>
    
</body>
</html>