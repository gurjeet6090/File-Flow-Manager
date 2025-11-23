<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileFlow Manager - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --dark: #0f172a;
            --darker: #020617;
            --light: #f8fafc;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --card-bg: #1e293b;
            --sidebar-bg: #0f172a;
            --hover-bg: #334155;
            --border-color: #334155;
            --glass-bg: rgba(30, 41, 59, 0.8);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--darker), var(--dark));
            color: var(--light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary), var(--info));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            font-size: 2.5rem;
        }

        .logo p {
            color: var(--secondary);
            margin-top: 0.5rem;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            color: var(--light);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--primary);
            color: var(--light);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .form-control::placeholder {
            color: var(--secondary);
        }

        .input-group-text {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            color: var(--secondary);
            border-right: none;
        }

        .form-control:focus + .input-group-text {
            border-color: var(--primary);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .demo-credentials {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .demo-credentials h6 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .demo-credentials p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--secondary);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--secondary);
        }

        .alert {
            border-radius: 12px;
            border: 1px solid;
            background: rgba(15, 23, 42, 0.8);
        }

        .alert-danger {
            border-color: var(--danger);
            color: var(--danger);
        }

        .alert-success {
            border-color: var(--success);
            color: var(--success);
        }

        .password-toggle {
            background: transparent;
            border: none;
            color: var(--secondary);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
        }

        .btn-loading {
            position: relative;
            color: transparent;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container fade-in-up">
        <div class="logo">
            <h1><i class="fas fa-folder-tree me-2"></i>FileFlow Manager</h1>
            <p>Secure File Management System</p>
        </div>

        <div class="glass-card p-4">

            <!-- Message Alert -->
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php 
                    $errors = [
                        'invalid' => 'Invalid username or password.',
                        'empty' => 'Please fill in all fields.',
                        'session' => 'Session expired. Please login again.',
                        'unauthorized' => 'Please login to access the file manager.'
                    ];
                    echo $errors[$_GET['error']] ?? 'An error occurred.';
                ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    $successMessages = [
                        'logout' => 'You have been successfully logged out.'
                    ];
                    echo $successMessages[$_GET['success']] ?? 'Success!';
                ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form action="auth.php" method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary-custom mb-3" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">Secure Access</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-file"></i>
                    </div>
                    <div class="feature-text">File Management</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <div class="feature-text">Cloud Storage</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="feature-text">Fast Performance</div>
                </div>
            </div>
        </div>

        <div class="login-footer">
            <p class="small">Secure file management system</p>
            <p class="small">© 2026 FileFlow. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const originalText = loginBtn.innerHTML;
            
            // Show loading state
            loginBtn.innerHTML = '<i class="fas fa-spinner"></i> Signing In...';
            loginBtn.classList.add('btn-loading');
            loginBtn.disabled = true;
        });

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Feature grid styling
        const style = document.createElement('style');
        style.textContent = `
            .feature-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin-top: 2rem;
            }
            .feature-item {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                background: rgba(15, 23, 42, 0.4);
                border-radius: 8px;
                transition: all 0.3s ease;
            }
            .feature-item:hover {
                background: rgba(15, 23, 42, 0.6);
                transform: translateY(-2px);
            }
            .feature-icon {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(99, 102, 241, 0.2);
                color: var(--primary);
                font-size: 1.2rem;
            }
            .feature-text {
                font-size: 0.875rem;
                color: var(--light);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>