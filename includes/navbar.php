<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'];

// Check if user is logged in
$isLoggedIn = isset($_SESSION['username']);
?>

<nav class="main-navbar">
    <div class="navbar-container">
        <!-- Logo/Brand -->
        <div class="navbar-brand">
            <a href="<?php echo $isLoggedIn ? '../index.php' : 'index.php'; ?>" class="brand-link">
                <span class="brand-text">Cryptic Quest</span>
            </a>
        </div>

        <!-- User Info Section -->
        <?php if ($isLoggedIn): ?>
            <div class="navbar-user">
                <span class="user-welcome">Welcome Detective: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Navigation Links -->
        <div class="navbar-nav">
            <?php if ($isLoggedIn): ?>
                <!-- Main Navigation -->
                <a href="<?php echo strpos($currentPath, '/pages/') !== false ? '../index.php' : 'index.php'; ?>" 
                   class="nav-link <?php echo ($currentPage == 'index') ? 'active' : ''; ?>">
                    Home
                </a>
                
                <a href="<?php echo strpos($currentPath, '/pages/') !== false ? 'cases.php' : 'pages/cases.php'; ?>" 
                   class="nav-link <?php echo ($currentPage == 'cases') ? 'active' : ''; ?>">
                    Cases
                </a>
                
                <a href="<?php echo strpos($currentPath, '/pages/') !== false ? 'profile.php' : 'pages/profile.php'; ?>" 
                   class="nav-link <?php echo ($currentPage == 'profile') ? 'active' : ''; ?>">
                    Profile
                </a>
                
                <a href="<?php echo strpos($currentPath, '/pages/') !== false ? 'leaderboard.php' : 'pages/leaderboard.php'; ?>" 
                   class="nav-link <?php echo ($currentPage == 'leaderboard') ? 'active' : ''; ?>">
                    Leaderboard
                </a>
                
                <a href="<?php echo strpos($currentPath, '/pages/') !== false ? '../includes/logout.php' : 'includes/logout.php'; ?>" 
                   class="nav-link logout-link">
                    Logout
                </a>
            <?php else: ?>
                <!-- Guest Navigation -->
                <a href="pages/login.php" class="nav-link">
                    Login
                </a>
                <a href="pages/register.php" class="nav-link">
                    Register
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav" id="mobileNav">
        <?php if ($isLoggedIn): ?>
            <div class="mobile-nav-header">
                <span class="mobile-user-info">Detective: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            
            <a href="<?php echo strpos($currentPath, '/pages/') !== false ? '../index.php' : 'index.php'; ?>" 
               class="mobile-nav-link <?php echo ($currentPage == 'index') ? 'active' : ''; ?>">
                Home
            </a>
            
            <a href="<?php echo strpos($currentPath, '/pages/') !== false ? 'cases.php' : 'pages/cases.php'; ?>" 
               class="mobile-nav-link <?php echo ($currentPage == 'cases') ? 'active' : ''; ?>">
                Cases
            </a>
            
            <a href="<?php echo strpos($currentPath, '/pages/') !== false ? 'profile.php' : 'pages/profile.php'; ?>" 
               class="mobile-nav-link <?php echo ($currentPage == 'profile') ? 'active' : ''; ?>">
                Profile
            </a>
            
            <a href="<?php echo strpos($currentPath, '/pages/') !== false ? 'leaderboard.php' : 'pages/leaderboard.php'; ?>" 
               class="mobile-nav-link <?php echo ($currentPage == 'leaderboard') ? 'active' : ''; ?>">
                Leaderboard
            </a>
            
            <a href="<?php echo strpos($currentPath, '/pages/') !== false ? '../includes/logout.php' : 'includes/logout.php'; ?>" 
               class="mobile-nav-link logout-link">
                Logout
            </a>
        <?php else: ?>
            <a href="pages/login.php" class="mobile-nav-link">
                Login
            </a>
            <a href="pages/register.php" class="mobile-nav-link">
                Register
            </a>
        <?php endif; ?>
    </div>
</nav>
