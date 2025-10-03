<?php
/*
    Admin login page
    Secure authentication for platform administrators
*/

require_once('_init.php');

$error = '';
$message = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        // Simple admin authentication (in production, use proper authentication)
        $validAdmins = [
            'admin' => 'admin123',
            'superadmin' => 'superadmin123',
            'moderator' => 'moderator123'
        ];
        
        if (isset($validAdmins[$username]) && $validAdmins[$username] === $password) {
            // Set admin session
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_role'] = $username === 'superadmin' ? 'superadmin' : ($username === 'admin' ? 'admin' : 'moderator');
            
            // Log admin login
            $securityManager->logAction('admin_login', null, [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            // Redirect to dashboard
            header('Location: admin-dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
            
            // Log failed login attempt
            $securityManager->logAction('admin_login_failed', null, [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
    } else {
        $error = 'Please enter both username and password';
    }
}

include('head.php');
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="admin-login-card">
                <div class="login-header">
                    <h2>🔧 Admin Login</h2>
                    <p>Platform Administration Access</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="admin-login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required 
                               placeholder="Enter your username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               placeholder="Enter your password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Access Admin Panel</button>
                </form>
                
                <div class="demo-credentials">
                    <h4>Demo Credentials</h4>
                    <p>For testing purposes, use these credentials:</p>
                    <ul>
                        <li><strong>Username:</strong> admin <strong>Password:</strong> admin123</li>
                        <li><strong>Username:</strong> superadmin <strong>Password:</strong> superadmin123</li>
                        <li><strong>Username:</strong> moderator <strong>Password:</strong> moderator123</li>
                    </ul>
                </div>
                
                <div class="login-footer">
                    <p>Need admin access? <a href="contact.php">Contact the system administrator</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-login-card {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-top: 50px;
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.login-header p {
    color: #7f8c8d;
    margin: 0;
}

.admin-login-form {
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-block {
    display: block;
    width: 100%;
}

.demo-credentials {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.demo-credentials h4 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.demo-credentials p {
    color: #7f8c8d;
    margin-bottom: 10px;
}

.demo-credentials ul {
    margin: 0;
    padding-left: 20px;
}

.demo-credentials li {
    color: #2c3e50;
    margin-bottom: 5px;
}

.login-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #ecf0f1;
}

.login-footer p {
    color: #7f8c8d;
    margin: 0;
}

.login-footer a {
    color: #3498db;
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .admin-login-card {
        margin: 20px;
        padding: 30px 20px;
    }
}
</style>

<?php include("f.php"); ?>
