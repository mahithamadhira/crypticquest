<?php
require_once '../includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    
    if (empty($username)) {
        $error = 'Username is required.';
    } elseif (empty($password)) {
        $error = 'Password is required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (strlen($username) > 20) {
        $error = 'Username must be less than 20 characters.';
    } elseif (strlen($password) < 3) {
        $error = 'Password must be at least 3 characters long.';
    } else {
        // Check if username already exists using JSON helper
        $existingUser = getUserByUsername($username);
        
        if ($existingUser) {
            $error = 'Username already exists. Please choose another.';
        } else {
            // Create new user using JSON helper
            $userId = generateUserId();
            $timestamp = getCurrentTimestamp();
            
            $users = readJsonFile(USERS_FILE);
            $newUser = [
                'user_id' => $userId,
                'username' => $username,
                'password' => $password, // Store plain text password
                'registration_time' => $timestamp,
                'total_score' => 0,
                'cases_completed' => [],
                'achievements' => []
            ];
            $users[] = $newUser;
            $result = writeJsonFile(USERS_FILE, $users);
            
            if ($result) {
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['registered_at'] = $timestamp;
                
                // Initialize user progress
                $_SESSION['cases_completed'] = [];
                $_SESSION['total_score'] = 0;
                $_SESSION['achievements'] = [];
                
                redirectTo('../index.php');
            } else {
                $error = 'Registration failed. Please try again. (Error: Could not save user data)';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Cryptic Quest</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_auth.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Cryptic Quest</h1>
            <p>Register as Detective</p>
        </header>
        
        <main>
            <div class="form-container">
                <h2>Create Your Detective Profile</h2>
                
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Detective Name:</label>
                        <input type="text" id="username" name="username" class="auth-input"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required maxlength="20" minlength="3" 
                               placeholder="Enter your detective name (3-20 characters)">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" class="auth-input" 
                               required minlength="3" 
                               placeholder="Enter your password (3+ characters)">
                    </div>
                    
                    <div class="form-group auth-form-group-center">
                        <button type="submit" class="btn btn-primary auth-btn">Start Investigating</button>
                    </div>
                </form>
                
                <div class="nav-links">
                    <a href="login.php" class="auth-nav-link">Already have an account? Login here</a>
                    <a href="../index.php" class="auth-nav-link">Back to Home</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>