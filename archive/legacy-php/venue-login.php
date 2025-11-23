<?php
/*
    Venue login page for B2B dashboard access
    Simple authentication system for venue partners
*/

require_once('_init.php');

$error = '';
$message = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venueId = intval($_POST['venue_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    
    if ($venueId > 0 && !empty($password)) {
        // Simple password check (in production, use proper authentication)
        $validCredentials = [
            1 => 'demo123',  // Austin Music Hall
            2 => 'demo123',  // South by Southwest
            3 => 'demo123',  // Rainey Street District
            4 => 'demo123',  // Zilker Park
            5 => 'demo123'   // 6th Street
        ];
        
        if (isset($validCredentials[$venueId]) && $validCredentials[$venueId] === $password) {
            // Set venue session
            $_SESSION['venue_id'] = $venueId;
            $_SESSION['venue_authenticated'] = true;
            
            // Redirect to dashboard
            header('Location: venue-dashboard.php?venue_id=' . $venueId . '&token=demo_token');
            exit();
        } else {
            $error = 'Invalid venue ID or password';
        }
    } else {
        $error = 'Please enter both venue ID and password';
    }
}

include('head.php');
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="venue-login-card">
                <div class="login-header">
                    <h2>🏢 Venue Dashboard Login</h2>
                    <p>Access your venue analytics and management tools</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="venue-login-form">
                    <div class="form-group">
                        <label for="venue_id">Venue ID</label>
                        <input type="number" id="venue_id" name="venue_id" class="form-control" required 
                               placeholder="Enter your venue ID" value="<?php echo htmlspecialchars($_POST['venue_id'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               placeholder="Enter your password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Access Dashboard</button>
                </form>
                
                <div class="demo-credentials">
                    <h4>Demo Credentials</h4>
                    <p>For testing purposes, use these credentials:</p>
                    <ul>
                        <li><strong>Venue ID 1:</strong> Austin Music Hall (Password: demo123)</li>
                        <li><strong>Venue ID 2:</strong> South by Southwest (Password: demo123)</li>
                        <li><strong>Venue ID 3:</strong> Rainey Street District (Password: demo123)</li>
                        <li><strong>Venue ID 4:</strong> Zilker Park (Password: demo123)</li>
                        <li><strong>Venue ID 5:</strong> 6th Street (Password: demo123)</li>
                    </ul>
                </div>
                
                <div class="login-footer">
                    <p>Don't have a venue account? <a href="contact.php">Contact us</a> to get started.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.venue-login-card {
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

.venue-login-form {
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
    .venue-login-card {
        margin: 20px;
        padding: 30px 20px;
    }
}
</style>

<?php include("f.php"); ?>
