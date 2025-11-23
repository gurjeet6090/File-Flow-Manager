<?php
session_start();

// Session configuration
$session_timeout = 3600; // 1 hour in seconds

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Check session timeout
function checkSessionTimeout() {
    global $session_timeout;
    
    if (isset($_SESSION['last_activity'])) {
        $session_life = time() - $_SESSION['last_activity'];
        if ($session_life > $session_timeout) {
            session_destroy();
            header('Location: login.php?error=session');
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Get current user info
function getCurrentUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

// Require authentication
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php?error=unauthorized');
        exit();
    }
    checkSessionTimeout();
}

// Logout function
function logout() {
    session_destroy();
    header('Location: login.php?success=logout');
    exit();
}
?>