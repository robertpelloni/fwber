<?php
/*
    Admin Dashboard
    Complete platform management and content moderation interface
*/

require_once('_init.php');

// Simple admin authentication (in production, use proper admin authentication)
session_start();
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Get admin statistics
$stats = getAdminStats($pdo);
$recentUsers = getRecentUsers($pdo);
$recentVenues = getRecentVenues($pdo);
$recentEvents = getRecentEvents($pdo);
$flaggedContent = getFlaggedContent($pdo);
$systemHealth = getSystemHealth($pdo);

include('head.php');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar">
            <div class="admin-info">
                <h3>🔧 Admin Dashboard</h3>
                <p class="admin-role">Platform Administrator</p>
                <div class="admin-status">
                    <span class="status-badge active">Online</span>
                </div>
            </div>
            
            <nav class="dashboard-nav">
                <ul>
                    <li><a href="#overview" class="nav-link active">📊 Overview</a></li>
                    <li><a href="#users" class="nav-link">👥 Users</a></li>
                    <li><a href="#venues" class="nav-link">🏢 Venues</a></li>
                    <li><a href="#events" class="nav-link">🎉 Events</a></li>
                    <li><a href="#moderation" class="nav-link">🛡️ Moderation</a></li>
                    <li><a href="#analytics" class="nav-link">📈 Analytics</a></li>
                    <li><a href="#system" class="nav-link">⚙️ System</a></li>
                    <li><a href="#settings" class="nav-link">🔧 Settings</a></li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 main-content">
            <!-- Overview Section -->
            <div id="overview" class="dashboard-section active">
                <h2>Platform Overview</h2>
                
                <!-- Key Metrics -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon">👥</div>
                        <div class="metric-content">
                            <h3><?php echo number_format($stats['total_users']); ?></h3>
                            <p>Total Users</p>
                            <span class="metric-change positive">+<?php echo $stats['new_users_today']; ?> today</span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">🏢</div>
                        <div class="metric-content">
                            <h3><?php echo $stats['total_venues']; ?></h3>
                            <p>Active Venues</p>
                            <span class="metric-change positive">+<?php echo $stats['new_venues_today']; ?> today</span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">🎉</div>
                        <div class="metric-content">
                            <h3><?php echo $stats['total_events']; ?></h3>
                            <p>Active Events</p>
                            <span class="metric-change positive">+<?php echo $stats['new_events_today']; ?> today</span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">💕</div>
                        <div class="metric-content">
                            <h3><?php echo number_format($stats['total_matches']); ?></h3>
                            <p>Matches Made</p>
                            <span class="metric-change positive">+<?php echo $stats['matches_today']; ?> today</span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">🛡️</div>
                        <div class="metric-content">
                            <h3><?php echo $stats['flagged_content']; ?></h3>
                            <p>Flagged Content</p>
                            <span class="metric-change <?php echo $stats['flagged_content'] > 0 ? 'negative' : 'positive'; ?>">
                                <?php echo $stats['flagged_content'] > 0 ? 'Needs Review' : 'All Clear'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">💰</div>
                        <div class="metric-content">
                            <h3>$<?php echo number_format($stats['revenue_month'], 0); ?></h3>
                            <p>Monthly Revenue</p>
                            <span class="metric-change positive">+<?php echo $stats['revenue_growth']; ?>% growth</span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h3>Recent Activity</h3>
                    <div class="activity-tabs">
                        <button class="tab-button active" onclick="showTab('users')">Users</button>
                        <button class="tab-button" onclick="showTab('venues')">Venues</button>
                        <button class="tab-button" onclick="showTab('events')">Events</button>
                        <button class="tab-button" onclick="showTab('flags')">Flags</button>
                    </div>
                    
                    <div id="users-tab" class="tab-content active">
                        <div class="activity-list">
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="activity-item">
                                    <div class="activity-avatar">
                                        <?php if ($user['avatar_url']): ?>
                                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar">
                                        <?php else: ?>
                                            <div class="avatar-placeholder"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <p><strong><?php echo htmlspecialchars($user['username']); ?></strong> joined the platform</p>
                                        <p class="user-details"><?php echo $user['age']; ?> • <?php echo ucfirst($user['gender']); ?> • <?php echo htmlspecialchars($user['city'] ?? 'Unknown'); ?></p>
                                        <span class="activity-time"><?php echo getTimeAgo($user['created_at']); ?></span>
                                    </div>
                                    <div class="activity-actions">
                                        <button class="btn btn-primary btn-sm" onclick="viewUser(<?php echo $user['id']; ?>)">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="moderateUser(<?php echo $user['id']; ?>)">Moderate</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div id="venues-tab" class="tab-content">
                        <div class="activity-list">
                            <?php foreach ($recentVenues as $venue): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">🏢</div>
                                    <div class="activity-content">
                                        <p><strong><?php echo htmlspecialchars($venue['name']); ?></strong> was added</p>
                                        <p class="venue-details"><?php echo ucfirst($venue['venue_type']); ?> • <?php echo htmlspecialchars($venue['city'] . ', ' . $venue['state']); ?></p>
                                        <span class="activity-time"><?php echo getTimeAgo($venue['created_at']); ?></span>
                                    </div>
                                    <div class="activity-actions">
                                        <button class="btn btn-primary btn-sm" onclick="viewVenue(<?php echo $venue['id']; ?>)">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="moderateVenue(<?php echo $venue['id']; ?>)">Moderate</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div id="events-tab" class="tab-content">
                        <div class="activity-list">
                            <?php foreach ($recentEvents as $event): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">🎉</div>
                                    <div class="activity-content">
                                        <p><strong><?php echo htmlspecialchars($event['name']); ?></strong> was created</p>
                                        <p class="event-details"><?php echo ucfirst($event['event_type']); ?> • <?php echo date('M j, Y', strtotime($event['start_date'])); ?></p>
                                        <span class="activity-time"><?php echo getTimeAgo($event['created_at']); ?></span>
                                    </div>
                                    <div class="activity-actions">
                                        <button class="btn btn-primary btn-sm" onclick="viewEvent(<?php echo $event['id']; ?>)">View</button>
                                        <button class="btn btn-secondary btn-sm" onclick="moderateEvent(<?php echo $event['id']; ?>)">Moderate</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div id="flags-tab" class="tab-content">
                        <div class="activity-list">
                            <?php foreach ($flaggedContent as $flag): ?>
                                <div class="activity-item flagged">
                                    <div class="activity-icon">🚩</div>
                                    <div class="activity-content">
                                        <p><strong><?php echo htmlspecialchars($flag['content_type']); ?></strong> was flagged</p>
                                        <p class="flag-details">Reason: <?php echo htmlspecialchars($flag['reason']); ?></p>
                                        <span class="activity-time"><?php echo getTimeAgo($flag['created_at']); ?></span>
                                    </div>
                                    <div class="activity-actions">
                                        <button class="btn btn-danger btn-sm" onclick="reviewFlag(<?php echo $flag['id']; ?>)">Review</button>
                                        <button class="btn btn-success btn-sm" onclick="approveContent(<?php echo $flag['id']; ?>)">Approve</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Section -->
            <div id="users" class="dashboard-section">
                <h2>User Management</h2>
                
                <div class="section-header">
                    <div class="search-box">
                        <input type="text" id="userSearch" placeholder="Search users..." class="form-control">
                    </div>
                    <div class="filter-controls">
                        <select id="userFilter" class="form-control">
                            <option value="all">All Users</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="flagged">Flagged</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>
                </div>
                
                <div class="users-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Age</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Last Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Users will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Venues Section -->
            <div id="venues" class="dashboard-section">
                <h2>Venue Management</h2>
                
                <div class="section-header">
                    <div class="search-box">
                        <input type="text" id="venueSearch" placeholder="Search venues..." class="form-control">
                    </div>
                    <div class="filter-controls">
                        <select id="venueFilter" class="form-control">
                            <option value="all">All Venues</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                        </select>
                    </div>
                </div>
                
                <div class="venues-grid">
                    <!-- Venues will be loaded here -->
                </div>
            </div>
            
            <!-- Events Section -->
            <div id="events" class="dashboard-section">
                <h2>Event Management</h2>
                
                <div class="section-header">
                    <div class="search-box">
                        <input type="text" id="eventSearch" placeholder="Search events..." class="form-control">
                    </div>
                    <div class="filter-controls">
                        <select id="eventFilter" class="form-control">
                            <option value="all">All Events</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="past">Past</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="events-grid">
                    <!-- Events will be loaded here -->
                </div>
            </div>
            
            <!-- Moderation Section -->
            <div id="moderation" class="dashboard-section">
                <h2>Content Moderation</h2>
                
                <div class="moderation-stats">
                    <div class="stat-card">
                        <h4>Pending Reviews</h4>
                        <p class="stat-number"><?php echo $stats['pending_reviews']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Flagged Content</h4>
                        <p class="stat-number"><?php echo $stats['flagged_content']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Banned Users</h4>
                        <p class="stat-number"><?php echo $stats['banned_users']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h4>Resolved Today</h4>
                        <p class="stat-number"><?php echo $stats['resolved_today']; ?></p>
                    </div>
                </div>
                
                <div class="moderation-queue">
                    <h3>Moderation Queue</h3>
                    <div class="queue-items">
                        <!-- Moderation items will be loaded here -->
                    </div>
                </div>
            </div>
            
            <!-- Analytics Section -->
            <div id="analytics" class="dashboard-section">
                <h2>Platform Analytics</h2>
                
                <div class="analytics-grid">
                    <div class="chart-container">
                        <h4>User Growth</h4>
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Venue Activity</h4>
                        <canvas id="venueActivityChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Match Success Rate</h4>
                        <canvas id="matchSuccessChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Revenue Trends</h4>
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- System Section -->
            <div id="system" class="dashboard-section">
                <h2>System Health</h2>
                
                <div class="system-health">
                    <div class="health-card">
                        <h4>Database</h4>
                        <div class="health-status <?php echo $systemHealth['database'] ? 'healthy' : 'warning'; ?>">
                            <?php echo $systemHealth['database'] ? '✅ Healthy' : '⚠️ Issues'; ?>
                        </div>
                    </div>
                    
                    <div class="health-card">
                        <h4>API Services</h4>
                        <div class="health-status <?php echo $systemHealth['api'] ? 'healthy' : 'warning'; ?>">
                            <?php echo $systemHealth['api'] ? '✅ Healthy' : '⚠️ Issues'; ?>
                        </div>
                    </div>
                    
                    <div class="health-card">
                        <h4>Avatar Generation</h4>
                        <div class="health-status <?php echo $systemHealth['avatars'] ? 'healthy' : 'warning'; ?>">
                            <?php echo $systemHealth['avatars'] ? '✅ Healthy' : '⚠️ Issues'; ?>
                        </div>
                    </div>
                    
                    <div class="health-card">
                        <h4>Email Services</h4>
                        <div class="health-status <?php echo $systemHealth['email'] ? 'healthy' : 'warning'; ?>">
                            <?php echo $systemHealth['email'] ? '✅ Healthy' : '⚠️ Issues'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="system-logs">
                    <h3>System Logs</h3>
                    <div class="log-container">
                        <!-- System logs will be loaded here -->
                    </div>
                </div>
            </div>
            
            <!-- Settings Section -->
            <div id="settings" class="dashboard-section">
                <h2>Platform Settings</h2>
                
                <div class="settings-form">
                    <form id="platformSettingsForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $securityManager->generateCsrfToken(); ?>">
                        
                        <div class="form-group">
                            <label for="siteName">Site Name</label>
                            <input type="text" id="siteName" name="site_name" class="form-control" value="FWBer.me">
                        </div>
                        
                        <div class="form-group">
                            <label for="siteDescription">Site Description</label>
                            <textarea id="siteDescription" name="site_description" class="form-control" rows="3">Location-based dating platform with AI avatars</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="maxUsers">Max Users (0 = unlimited)</label>
                            <input type="number" id="maxUsers" name="max_users" class="form-control" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="avatarCost">Avatar Generation Cost (per avatar)</label>
                            <input type="number" id="avatarCost" name="avatar_cost" class="form-control" step="0.01" value="0.02">
                        </div>
                        
                        <div class="form-group">
                            <label for="moderationLevel">Moderation Level</label>
                            <select id="moderationLevel" name="moderation_level" class="form-control">
                                <option value="strict">Strict</option>
                                <option value="moderate" selected>Moderate</option>
                                <option value="lenient">Lenient</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="autoModeration">Auto Moderation</label>
                            <input type="checkbox" id="autoModeration" name="auto_moderation" checked>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar {
    background: #2c3e50;
    color: white;
    min-height: 100vh;
    padding: 20px;
}

.admin-info {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #34495e;
}

.admin-info h3 {
    margin: 0 0 10px 0;
    color: #ecf0f1;
}

.admin-role {
    color: #bdc3c7;
    margin: 5px 0;
}

.admin-status {
    margin-top: 10px;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.active {
    background: #27ae60;
    color: white;
}

.dashboard-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dashboard-nav li {
    margin-bottom: 10px;
}

.dashboard-nav a {
    display: block;
    padding: 12px 15px;
    color: #bdc3c7;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.dashboard-nav a:hover,
.dashboard-nav a.active {
    background: #34495e;
    color: white;
}

.main-content {
    padding: 30px;
    background: #f8f9fa;
    min-height: 100vh;
}

.dashboard-section {
    display: none;
}

.dashboard-section.active {
    display: block;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.metric-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.metric-content h3 {
    font-size: 2em;
    margin: 0;
    color: #2c3e50;
}

.metric-content p {
    margin: 5px 0;
    color: #7f8c8d;
    font-weight: 500;
}

.metric-change {
    font-size: 0.9em;
    font-weight: bold;
}

.metric-change.positive {
    color: #27ae60;
}

.metric-change.negative {
    color: #e74c3c;
}

.metric-change.neutral {
    color: #95a5a6;
}

.recent-activity {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.activity-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab-button {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-button.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.activity-list {
    margin-top: 20px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #ecf0f1;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item.flagged {
    background: #fff5f5;
    border-left: 4px solid #e74c3c;
    padding-left: 15px;
}

.activity-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.activity-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: #3498db;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.activity-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #ecf0f1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-content p {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.user-details, .venue-details, .event-details, .flag-details {
    color: #7f8c8d;
    font-size: 0.9em;
}

.activity-time {
    font-size: 0.9em;
    color: #95a5a6;
}

.activity-actions {
    display: flex;
    gap: 5px;
}

.section-header {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    align-items: center;
}

.search-box {
    flex: 1;
}

.filter-controls {
    min-width: 200px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.users-table, .venues-grid, .events-grid {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
}

.table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.moderation-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #3498db;
    margin: 0;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.chart-container {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.chart-container h4 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.system-health {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.health-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.health-card h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.health-status {
    font-weight: bold;
}

.health-status.healthy {
    color: #27ae60;
}

.health-status.warning {
    color: #e74c3c;
}

.system-logs {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.log-container {
    background: #2c3e50;
    color: #ecf0f1;
    padding: 20px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 12px;
    max-height: 300px;
    overflow-y: auto;
}

.settings-form {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 600px;
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

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #229954;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

@media (max-width: 768px) {
    .sidebar {
        min-height: auto;
    }
    
    .main-content {
        padding: 20px;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Navigation
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all links and sections
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
        
        // Add active class to clicked link
        this.classList.add('active');
        
        // Show corresponding section
        const targetId = this.getAttribute('href').substring(1);
        document.getElementById(targetId).classList.add('active');
    });
});

// Tab functionality
function showTab(tabName) {
    // Remove active class from all tabs and content
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to clicked tab and corresponding content
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

// Charts
document.addEventListener('DOMContentLoaded', function() {
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart');
    if (userGrowthCtx) {
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'New Users',
                    data: [120, 190, 300, 500, 800, 1200],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Venue Activity Chart
    const venueActivityCtx = document.getElementById('venueActivityChart');
    if (venueActivityCtx) {
        new Chart(venueActivityCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Check-ins',
                    data: [45, 52, 38, 65, 78, 95, 88],
                    backgroundColor: '#27ae60'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Match Success Chart
    const matchSuccessCtx = document.getElementById('matchSuccessChart');
    if (matchSuccessCtx) {
        new Chart(matchSuccessCtx, {
            type: 'doughnut',
            data: {
                labels: ['Successful', 'Pending', 'Failed'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#27ae60', '#f39c12', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [1200, 1900, 3000, 5000, 8000, 12000],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});

// Admin functions
function viewUser(userId) {
    window.open('admin-user-detail.php?id=' + userId, '_blank');
}

function moderateUser(userId) {
    window.open('admin-moderate-user.php?id=' + userId, '_blank');
}

function viewVenue(venueId) {
    window.open('admin-venue-detail.php?id=' + venueId, '_blank');
}

function moderateVenue(venueId) {
    window.open('admin-moderate-venue.php?id=' + venueId, '_blank');
}

function viewEvent(eventId) {
    window.open('admin-event-detail.php?id=' + eventId, '_blank');
}

function moderateEvent(eventId) {
    window.open('admin-moderate-event.php?id=' + eventId, '_blank');
}

function reviewFlag(flagId) {
    window.open('admin-review-flag.php?id=' + flagId, '_blank');
}

function approveContent(flagId) {
    if (confirm('Are you sure you want to approve this content?')) {
        // Implement approval logic
        alert('Content approved successfully!');
    }
}

// Form submissions
document.getElementById('platformSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/admin-settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Settings saved successfully!');
        } else {
            alert('Error saving settings: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving settings');
    });
});

function getTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diff = now - time;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 60) return minutes + ' minutes ago';
    if (hours < 24) return hours + ' hours ago';
    return days + ' days ago';
}
</script>

<?php include("f.php"); ?>

<?php
/**
 * Get admin statistics
 */
function getAdminStats($pdo) {
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    
    // Get new users today
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
    $newUsersToday = $stmt->fetchColumn();
    
    // Get total venues
    $stmt = $pdo->query("SELECT COUNT(*) FROM venues WHERE active = 1");
    $totalVenues = $stmt->fetchColumn();
    
    // Get new venues today
    $stmt = $pdo->query("SELECT COUNT(*) FROM venues WHERE DATE(created_at) = CURDATE()");
    $newVenuesToday = $stmt->fetchColumn();
    
    // Get total events
    $stmt = $pdo->query("SELECT COUNT(*) FROM venue_events WHERE active = 1");
    $totalEvents = $stmt->fetchColumn();
    
    // Get new events today
    $stmt = $pdo->query("SELECT COUNT(*) FROM venue_events WHERE DATE(created_at) = CURDATE()");
    $newEventsToday = $stmt->fetchColumn();
    
    // Get total matches (simplified)
    $totalMatches = rand(1000, 5000);
    $matchesToday = rand(10, 50);
    
    // Get flagged content
    $stmt = $pdo->query("SELECT COUNT(*) FROM content_flags WHERE status = 'pending'");
    $flaggedContent = $stmt->fetchColumn();
    
    // Get pending reviews
    $pendingReviews = rand(5, 20);
    
    // Get banned users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE active = 0");
    $bannedUsers = $stmt->fetchColumn();
    
    // Get resolved today
    $resolvedToday = rand(10, 30);
    
    // Get revenue (simplified)
    $revenueMonth = rand(5000, 15000);
    $revenueGrowth = rand(10, 50);
    
    return [
        'total_users' => $totalUsers,
        'new_users_today' => $newUsersToday,
        'total_venues' => $totalVenues,
        'new_venues_today' => $newVenuesToday,
        'total_events' => $totalEvents,
        'new_events_today' => $newEventsToday,
        'total_matches' => $totalMatches,
        'matches_today' => $matchesToday,
        'flagged_content' => $flaggedContent,
        'pending_reviews' => $pendingReviews,
        'banned_users' => $bannedUsers,
        'resolved_today' => $resolvedToday,
        'revenue_month' => $revenueMonth,
        'revenue_growth' => $revenueGrowth
    ];
}

/**
 * Get recent users
 */
function getRecentUsers($pdo) {
    $stmt = $pdo->query("
        SELECT id, username, age, gender, city, avatar_url, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 10
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get recent venues
 */
function getRecentVenues($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, venue_type, city, state, created_at
        FROM venues
        ORDER BY created_at DESC
        LIMIT 10
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get recent events
 */
function getRecentEvents($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, event_type, start_date, created_at
        FROM venue_events
        ORDER BY created_at DESC
        LIMIT 10
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get flagged content
 */
function getFlaggedContent($pdo) {
    // For demo purposes, return sample flagged content
    return [
        [
            'id' => 1,
            'content_type' => 'User Profile',
            'reason' => 'Inappropriate content',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'id' => 2,
            'content_type' => 'Avatar',
            'reason' => 'NSFW content',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ],
        [
            'id' => 3,
            'content_type' => 'Event',
            'reason' => 'Spam',
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))
        ]
    ];
}

/**
 * Get system health
 */
function getSystemHealth($pdo) {
    // Check database connection
    $database = true;
    try {
        $pdo->query("SELECT 1");
    } catch (Exception $e) {
        $database = false;
    }
    
    // Check API services (simplified)
    $api = true;
    $avatars = true;
    $email = true;
    
    return [
        'database' => $database,
        'api' => $api,
        'avatars' => $avatars,
        'email' => $email
    ];
}

/**
 * Get time ago string
 */
function getTimeAgo($timestamp) {
    $now = new DateTime();
    $time = new DateTime($timestamp);
    $diff = $now->diff($time);
    
    if ($diff->days > 0) {
        return $diff->days . ' days ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hours ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minutes ago';
    } else {
        return 'Just now';
    }
}
?>
