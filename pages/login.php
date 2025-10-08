<?php
require_once '../includes/config.php';
require_once '../includes/cases.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    
    if (empty($username)) {
        $error = 'Username is required.';
    } elseif (empty($password)) {
        $error = 'Password is required.';
    } else {
        // Check if username exists using JSON helper function
        $user = getUserByUsername($username);
        
        if ($user) {
            // Check password (plain text comparison)
            if (isset($user['password']) && $user['password'] === $password) {
                // User found and password correct, set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['registered_at'] = $user['registration_time'];
                $_SESSION['total_score'] = $user['total_score'];
                $_SESSION['cases_completed'] = $user['cases_completed'];
                $_SESSION['achievements'] = $user['achievements'] ?? [];
                
                // Debug: Log what we loaded
                error_log("DEBUG: Loaded " . count($user['cases_completed']) . " cases for user $username, total score: " . $user['total_score']);
                
                redirectTo('../index.php');
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'Username not found. Please register first.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cryptic Quest</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/page_specific_css/page_auth.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Cryptic Quest</h1>
            <p>Detective Login</p>
        </header>
        
        <main>
            <div class="form-container">
                <h2>Welcome Back, Detective</h2>
                
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Detective Name:</label>
                        <input type="text" id="username" name="username" class="auth-input"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" class="auth-input" required>
                    </div>
                    
                    <div class="form-group auth-form-group-center">
                        <button type="submit" class="btn btn-primary auth-btn">Continue Investigation</button>
                    </div>
                </form>
                
                <div class="nav-links">
                    <a href="register.php" class="auth-nav-link">New detective? Register here</a>
                    <a href="../index.php" class="auth-nav-link">Back to Home</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>