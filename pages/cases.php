<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    redirectTo('../pages/login.php');
}

// Get filter parameters
$difficultyFilter = $_GET['difficulty'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';

// Get all cases
$allCases = getCases();

// Apply filters
$filteredCases = $allCases;
if ($difficultyFilter !== 'all') {
    $filteredCases = array_filter($filteredCases, function($case) use ($difficultyFilter) {
        return $case['difficulty'] === $difficultyFilter;
    });
}
if ($categoryFilter !== 'all') {
    $filteredCases = array_filter($filteredCases, function($case) use ($categoryFilter) {
        return $case['category'] === $categoryFilter;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Cases - Cryptic Quest</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_cases.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Page Content -->
    <div class="page-content">
        <div class="container">
            <header>
                <h1>Case Selection</h1>
                <p>Choose Your Mystery</p>
            </header>
            
            <main>
            
            <!-- Filters -->
            <div class="filters">
                <h3>Filter Cases</h3>
                <form method="GET" class="filter-group">
                    <div>
                        <label for="difficulty">Difficulty:</label>
                        <select name="difficulty" id="difficulty" class="filter-select">
                            <option value="all" <?php echo $difficultyFilter === 'all' ? 'selected' : ''; ?>>All Levels</option>
                            <option value="beginner" <?php echo $difficultyFilter === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo $difficultyFilter === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo $difficultyFilter === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            <option value="expert" <?php echo $difficultyFilter === 'expert' ? 'selected' : ''; ?>>Expert</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="category">Category:</label>
                        <select name="category" id="category" class="filter-select">
                            <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <option value="theft" <?php echo $categoryFilter === 'theft' ? 'selected' : ''; ?>>Theft</option>
                            <option value="murder" <?php echo $categoryFilter === 'murder' ? 'selected' : ''; ?>>Murder</option>
                            <option value="fraud" <?php echo $categoryFilter === 'fraud' ? 'selected' : ''; ?>>Fraud</option>
                            <option value="conspiracy" <?php echo $categoryFilter === 'conspiracy' ? 'selected' : ''; ?>>Conspiracy</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </form>
            </div>
            
            <!-- Cases Grid -->
            <div class="cases-section">
                <h2>Available Cases (<?php echo count($filteredCases); ?>)</h2>
                
                <?php if (empty($filteredCases)): ?>
                    <div class="no-cases">
                        <p>No cases match your filter criteria. Try adjusting your filters.</p>
                    </div>
                <?php else: ?>
                    <div class="cases-grid">
                        <?php foreach ($filteredCases as $case): ?>
                            <div class="case-card">
                                <!-- Difficulty Badge -->
                                <div class="difficulty-badge difficulty-<?php echo $case['difficulty']; ?>">
                                    <?php echo strtoupper($case['difficulty']); ?>
                                </div>
                                
                                <!-- Case Preview Image Area -->
                                <div class="case-preview-area">
                                    <div class="case-icon"><?php echo substr($case['title'], 0, 1); ?></div>
                                </div>
                                
                                <!-- Case Title -->
                                <h3><?php echo htmlspecialchars($case['title']); ?></h3>
                                
                                <!-- Case Description -->
                                <p class="case-description"><?php echo htmlspecialchars($case['description']); ?></p>
                                
                                <!-- Case Meta Information -->
                                <div class="case-meta">
                                    <span><?php echo $case['estimated_time']; ?></span>
                                    <span><?php echo $case['points']; ?> pts</span>
                                    <span><?php echo ucfirst($case['category']); ?></span>
                                </div>
                                
                                <!-- Case Status -->
                                <?php
                                $isCompleted = false;
                                $isCurrent = false;
                                
                                // Check if case is completed
                                if (isset($_SESSION['cases_completed'])) {
                                    foreach ($_SESSION['cases_completed'] as $completed) {
                                        if ($completed['case_id'] === $case['id']) {
                                            $isCompleted = true;
                                            break;
                                        }
                                    }
                                }
                                
                                // Check if case is currently in progress
                                if (isset($_SESSION['current_case']) && $_SESSION['current_case']['case_id'] === $case['id']) {
                                    $isCurrent = true;
                                }
                                ?>
                                
                                <div class="case-actions">
                                    <?php if ($isCompleted): ?>
                                        <div class="case-status-badge completed">SOLVED - Complete in archive</div>
                                        <a href="investigate.php?case=<?php echo $case['id']; ?>&review=1" class="case-action-btn review-btn">Review Case</a>
                                    <?php elseif ($isCurrent): ?>
                                        <div class="case-status-badge current">IN PROGRESS</div>
                                        <a href="investigate.php?case=<?php echo $case['id']; ?>" class="case-action-btn continue-btn">Continue Investigation</a>
                                    <?php else: ?>
                                        <a href="investigate.php?case=<?php echo $case['id']; ?>&start=1" class="case-action-btn start-btn">Begin Investigation</a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Case Preview -->
                                <div class="case-preview">
                                    <h4>Case Preview:</h4>
                                    <p class="preview-text"><?php echo htmlspecialchars(substr($case['scenario'], 0, 150)) . '...'; ?></p>
                                    <p><strong>Suspects:</strong> <?php echo count($case['suspects']); ?> individuals</p>
                                    <p><strong>Evidence:</strong> <?php echo count($case['evidence']); ?> pieces to analyze</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Current Case Warning -->
            <?php if (isset($_SESSION['current_case'])): ?>
                <div class="current-case-warning">
                    <h3>Case in Progress</h3>
                    <p>You currently have an active case: <strong><?php echo htmlspecialchars(getCase($_SESSION['current_case']['case_id'])['title']); ?></strong></p>
                    <p>Starting a new case will abandon your current progress. Are you sure you want to continue?</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    
</body>
</html>