<?php
// Cryptic Quest - Configuration File

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include JSON helper functions
require_once __DIR__ . '/json_helpers.php';

// Application settings
define('APP_NAME', 'Cryptic Quest');
define('APP_VERSION', '1.0.0');

// Data file paths
define('DATA_DIR', __DIR__ . '/../data/');
define('USERS_FILE', DATA_DIR . 'users.json');
define('SCORES_FILE', DATA_DIR . 'scores.json');
define('LEADERBOARD_FILE', DATA_DIR . 'leaderboard.json');

// Game settings
define('MAX_HINTS', 3);
define('TIME_PENALTY', 0.1); // 10% penalty per minute
define('ACCURACY_BONUS', 50); // Bonus points for correct answers

// Create data directory if it doesn't exist
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Initialize data files if they don't exist using JSON helper
if (!file_exists(USERS_FILE)) {
    writeJsonFile(USERS_FILE, []);
}
if (!file_exists(SCORES_FILE)) {
    writeJsonFile(SCORES_FILE, []);
}
if (!file_exists(LEADERBOARD_FILE)) {
    writeJsonFile(LEADERBOARD_FILE, []);
}

// Helper functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateUserId() {
    return uniqid('user_', true);
}

function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $seconds);
}

function redirectTo($page) {
    header("Location: $page");
    exit();
}

function showError($message) {
    return "<div class='error-message'>$message</div>";
}

function showSuccess($message) {
    return "<div class='success-message'>$message</div>";
}
?>