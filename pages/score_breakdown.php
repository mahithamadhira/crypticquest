<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('../pages/login.php');
}

// Get case ID from URL
$caseId = $_GET['case'] ?? null;
if (!$caseId) {
    redirectTo('cases.php');
}

// Get case data
$case = getCase($caseId);
if (!$case) {
    redirectTo('cases.php');
}

// Get score breakdown
$scoreData = getCurrentScorePreview($caseId);
if (!$scoreData) {
    redirectTo("investigate.php?case=$caseId");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score Breakdown - <?php echo htmlspecialchars($case['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_investigate.css">
    <style>
        .score-breakdown-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(26, 26, 26, 0.9);
            border: 1px solid var(--terminal-border);
            border-radius: 8px;
        }
        
        .score-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--terminal-border);
        }
        
        .total-score {
            font-size: 3rem;
            color: var(--terminal-success);
            font-weight: bold;
            text-shadow: 0 0 10px rgba(0, 255, 170, 0.5);
            margin-bottom: 0.5rem;
        }
        
        .score-components {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .component-card {
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid var(--terminal-border);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .component-card:hover {
            border-color: var(--terminal-highlight);
            transform: translateY(-2px);
        }
        
        .component-title {
            color: var(--neon-cyan);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .component-score {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .component-score.positive {
            color: var(--terminal-success);
        }
        
        .component-score.negative {
            color: var(--terminal-error);
        }
        
        .component-score.neutral {
            color: var(--terminal-warning);
        }
        
        .component-details {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--terminal-text-dim);
            line-height: 1.4;
        }
        
        .bonus-section {
            background: linear-gradient(135deg, rgba(0, 255, 170, 0.1) 0%, rgba(26, 26, 26, 0.8) 100%);
            border-color: var(--terminal-success);
        }
        
        .penalty-section {
            background: linear-gradient(135deg, rgba(255, 102, 0, 0.1) 0%, rgba(26, 26, 26, 0.8) 100%);
            border-color: var(--terminal-error);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: rgba(10, 10, 10, 0.6);
            border: 1px solid var(--terminal-border);
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--terminal-highlight);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--terminal-text-dim);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .back-button {
            margin-top: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <div class="page-content">
        <div class="score-breakdown-container">
            <div class="score-header">
                <h1><?php echo htmlspecialchars($case['title']); ?></h1>
                <div class="total-score"><?php echo $scoreData['total_score']; ?></div>
                <p>Current Score Breakdown</p>
            </div>
            
            <div class="score-components">
                <!-- Base Score -->
                <div class="component-card">
                    <div class="component-title">
                        Base Score
                        <span class="component-score positive"><?php echo $scoreData['breakdown']['base_score']; ?></span>
                    </div>
                    <div class="component-details">
                        Foundation score (40% of total points). This represents the core value of completing the case.
                    </div>
                </div>
                
                <!-- Time Efficiency -->
                <div class="component-card">
                    <div class="component-title">
                        Time Efficiency
                        <span class="component-score <?php echo $scoreData['breakdown']['time_efficiency'] > 0 ? 'positive' : 'neutral'; ?>">
                            <?php echo $scoreData['breakdown']['time_efficiency']; ?>
                        </span>
                    </div>
                    <div class="component-details">
                        Based on completion time vs. optimal time (<?php echo $scoreData['stats']['optimal_minutes']; ?> min). 
                        Current: <?php echo $scoreData['stats']['time_taken_minutes']; ?> minutes.
                        <?php if ($scoreData['stats']['time_taken_minutes'] <= $scoreData['stats']['optimal_minutes']): ?>
                            <br><strong>Excellent timing!</strong>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Evidence Collection -->
                <div class="component-card">
                    <div class="component-title">
                        Evidence Quality
                        <span class="component-score positive"><?php echo $scoreData['breakdown']['evidence_collection']; ?></span>
                    </div>
                    <div class="component-details">
                        Quality of evidence collected (<?php echo $scoreData['stats']['evidence_percentage']; ?>% collected).
                        High-relevance evidence provides bonus points.
                    </div>
                </div>
                
                <!-- Investigation Quality -->
                <div class="component-card">
                    <div class="component-title">
                        Analysis Depth
                        <span class="component-score <?php echo $scoreData['breakdown']['investigation_quality'] > 0 ? 'positive' : 'neutral'; ?>">
                            <?php echo $scoreData['breakdown']['investigation_quality']; ?>
                        </span>
                    </div>
                    <div class="component-details">
                        Cross-reference usage and connection analysis.
                        Evidence in analysis: <?php echo $scoreData['stats']['cross_ref_evidence']; ?>,
                        Connections made: <?php echo $scoreData['stats']['connections_made']; ?>
                    </div>
                </div>
                
                <!-- Difficulty Multiplier -->
                <div class="component-card">
                    <div class="component-title">
                        Difficulty Bonus
                        <span class="component-score neutral">×<?php echo $scoreData['breakdown']['difficulty_multiplier']; ?></span>
                    </div>
                    <div class="component-details">
                        Multiplier based on case difficulty: <?php echo ucfirst($case['difficulty']); ?>.
                        Higher difficulties provide score multipliers.
                    </div>
                </div>
                
                <?php if ($scoreData['breakdown']['hint_penalty'] > 0): ?>
                <!-- Hint Penalty -->
                <div class="component-card penalty-section">
                    <div class="component-title">
                        Hint Penalty
                        <span class="component-score negative"><?php echo $scoreData['breakdown']['hint_penalty']; ?></span>
                    </div>
                    <div class="component-details">
                        Progressive penalty for using hints (<?php echo $scoreData['stats']['hints_used']; ?> used).
                        Each hint costs progressively more points.
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($scoreData['breakdown']['accuracy_bonus'] > 0): ?>
                <!-- Accuracy Bonus -->
                <div class="component-card bonus-section">
                    <div class="component-title">
                        Accuracy Bonus
                        <span class="component-score positive">+<?php echo $scoreData['breakdown']['accuracy_bonus']; ?></span>
                    </div>
                    <div class="component-details">
                        Perfect evidence collection bonus (10% of base score).
                        Awarded for collecting all available evidence.
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($scoreData['breakdown']['analysis_bonus'] > 0): ?>
                <!-- Analysis Mastery Bonus -->
                <div class="component-card bonus-section">
                    <div class="component-title">
                        Analysis Mastery
                        <span class="component-score positive">+<?php echo $scoreData['breakdown']['analysis_bonus']; ?></span>
                    </div>
                    <div class="component-details">
                        Thorough cross-reference analysis bonus (5% of base score).
                        Awarded for deep investigative work.
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($scoreData['breakdown']['speed_bonus'] > 0): ?>
                <!-- Speed Mastery Bonus -->
                <div class="component-card bonus-section">
                    <div class="component-title">
                        Speed Mastery
                        <span class="component-score positive">+<?php echo $scoreData['breakdown']['speed_bonus']; ?></span>
                    </div>
                    <div class="component-details">
                        Exceptional speed bonus (15% of base score).
                        Awarded for completing in 70% of optimal time.
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Performance Statistics -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $scoreData['stats']['time_taken_minutes']; ?>m</div>
                    <div class="stat-label">Time Taken</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $scoreData['stats']['evidence_percentage']; ?>%</div>
                    <div class="stat-label">Evidence Collected</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $scoreData['stats']['cross_ref_evidence']; ?></div>
                    <div class="stat-label">Cross-Referenced</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $scoreData['stats']['connections_made']; ?></div>
                    <div class="stat-label">Connections Made</div>
                </div>
            </div>
            
            <div class="back-button">
                <a href="investigate.php?case=<?php echo $caseId; ?>" class="btn btn-primary">Back to Investigation</a>
            </div>
        </div>
    </div>
</body>
</html>