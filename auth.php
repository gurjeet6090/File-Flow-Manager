<?php
session_start();

// Static credentials - Change these to your desired username and password
$valid_username = 'admin';
$valid_password = 'password123';

// Handle login
if ($_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate credentials
    if (empty($username) || empty($password)) {
        header('Location: login.php?error=empty');
        exit();
    }
    
    if ($username === $valid_username && $password === $valid_password) {
        // Login successful
        $_SESSION['user'] = [
            'username' => $username,
            'login_time' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];
        
        // Set session timeout (1 hour)
        $_SESSION['last_activity'] = time();
        
        header('Location: index.php');
        exit();
    } else {
        // Login failed
        header('Location: login.php?error=invalid');
        exit();
    }
}

// Handle logout
if ($_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php?success=logout');
    exit();
}
?>