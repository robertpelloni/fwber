<?php
/*
    B2B Venue Dashboard
    Analytics and management interface for venue partners
*/

require_once('_init.php');

// Simple venue authentication (in production, use proper venue authentication)
$venueId = $_GET['venue_id'] ?? 1;
$venueToken = $_GET['token'] ?? 'demo_token';

// For demo purposes, we'll use venue ID 1
// In production, validate venue token and get venue details
$venue = getVenueById($pdo, $venueId);
if (!$venue) {
    die('Venue not found');
}

// Get analytics data
$analytics = getVenueAnalytics($pdo, $venueId);
$recentCheckins = getRecentCheckins($pdo, $venueId);
$activeUsers = getActiveUsers($pdo, $venueId);
$events = getVenueEvents($pdo, $venueId);

include('head.php');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar">
            <div class="venue-info">
                <h3><?php echo htmlspecialchars($venue['name']); ?></h3>
                <p class="venue-type"><?php echo ucfirst($venue['venue_type']); ?></p>
                <p class="venue-location"><?php echo htmlspecialchars($venue['city'] . ', ' . $venue['state']); ?></p>
                <div class="venue-status">
                    <span class="status-badge active">Active</span>
                </div>
            </div>
            
            <nav class="dashboard-nav">
                <ul>
                    <li><a href="#overview" class="nav-link active">📊 Overview</a></li>
                    <li><a href="#analytics" class="nav-link">📈 Analytics</a></li>
                    <li><a href="#users" class="nav-link">👥 Users</a></li>
                    <li><a href="#events" class="nav-link">🎉 Events</a></li>
                    <li><a href="#settings" class="nav-link">⚙️ Settings</a></li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 main-content">
            <!-- Overview Section -->
            <div id="overview" class="dashboard-section active">
                <h2>Dashboard Overview</h2>
                
                <!-- Key Metrics -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon">👥</div>
                        <div class="metric-content">
                            <h3><?php echo $analytics['total_checkins']; ?></h3>
                            <p>Total Check-ins</p>
                            <span class="metric-change positive">+<?php echo $analytics['checkins_today']; ?> today</span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">🔥</div>
                        <div class="metric-content">
                            <h3><?php echo $analytics['active_users']; ?></h3>
                            <p>Active Users</p>
                            <span class="metric-change positive">+<?php echo $analytics['new_users_today']; ?> new</span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">💕</div>
                        <div class="metric-content">
                            <h3><?php echo $analytics['matches_made']; ?></h3>
                            <p>Matches Made</p>
                            <span class="metric-change positive">+<?php echo $analytics['matches_today']; ?> today</span>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">⭐</div>
                        <div class="metric-content">
                            <h3><?php echo $analytics['avg_rating']; ?></h3>
                            <p>Average Rating</p>
                            <span class="metric-change neutral">Based on <?php echo $analytics['total_ratings']; ?> reviews</span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h3>Recent Activity</h3>
                    <div class="activity-list">
                        <?php foreach ($recentCheckins as $checkin): ?>
                            <div class="activity-item">
                                <div class="activity-avatar">
                                    <?php if ($checkin['avatar_url']): ?>
                                        <img src="<?php echo htmlspecialchars($checkin['avatar_url']); ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?php echo strtoupper(substr($checkin['username'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <p><strong><?php echo htmlspecialchars($checkin['username']); ?></strong> checked in</p>
                                    <?php if ($checkin['announcement']): ?>
                                        <p class="announcement">"<?php echo htmlspecialchars($checkin['announcement']); ?>"</p>
                                    <?php endif; ?>
                                    <span class="activity-time"><?php echo getTimeAgo($checkin['checked_in_at']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Section -->
            <div id="analytics" class="dashboard-section">
                <h2>Analytics</h2>
                
                <div class="analytics-grid">
                    <div class="chart-container">
                        <h4>Check-ins Over Time</h4>
                        <canvas id="checkinsChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>User Demographics</h4>
                        <canvas id="demographicsChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Peak Hours</h4>
                        <canvas id="peakHoursChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Match Success Rate</h4>
                        <canvas id="matchesChart"></canvas>
                    </div>
                </div>
                
                <div class="analytics-table">
                    <h4>Detailed Analytics</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Today</th>
                                <th>This Week</th>
                                <th>This Month</th>
                                <th>All Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Check-ins</td>
                                <td><?php echo $analytics['checkins_today']; ?></td>
                                <td><?php echo $analytics['checkins_week']; ?></td>
                                <td><?php echo $analytics['checkins_month']; ?></td>
                                <td><?php echo $analytics['total_checkins']; ?></td>
                            </tr>
                            <tr>
                                <td>Unique Users</td>
                                <td><?php echo $analytics['users_today']; ?></td>
                                <td><?php echo $analytics['users_week']; ?></td>
                                <td><?php echo $analytics['users_month']; ?></td>
                                <td><?php echo $analytics['total_users']; ?></td>
                            </tr>
                            <tr>
                                <td>Matches Made</td>
                                <td><?php echo $analytics['matches_today']; ?></td>
                                <td><?php echo $analytics['matches_week']; ?></td>
                                <td><?php echo $analytics['matches_month']; ?></td>
                                <td><?php echo $analytics['matches_made']; ?></td>
                            </tr>
                            <tr>
                                <td>Average Stay Time</td>
                                <td><?php echo $analytics['avg_stay_today']; ?>h</td>
                                <td><?php echo $analytics['avg_stay_week']; ?>h</td>
                                <td><?php echo $analytics['avg_stay_month']; ?>h</td>
                                <td><?php echo $analytics['avg_stay_total']; ?>h</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Users Section -->
            <div id="users" class="dashboard-section">
                <h2>Active Users</h2>
                
                <div class="users-grid">
                    <?php foreach ($activeUsers as $user): ?>
                        <div class="user-card">
                            <div class="user-avatar">
                                <?php if ($user['avatar_url']): ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                <p><?php echo $user['age']; ?> • <?php echo ucfirst($user['gender']); ?></p>
                                <p class="checkin-time">Checked in <?php echo getTimeAgo($user['checked_in_at']); ?></p>
                                <?php if ($user['announcement']): ?>
                                    <p class="announcement">"<?php echo htmlspecialchars($user['announcement']); ?>"</p>
                                <?php endif; ?>
                            </div>
                            <div class="user-actions">
                                <button class="btn btn-primary btn-sm">View Profile</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Events Section -->
            <div id="events" class="dashboard-section">
                <h2>Events</h2>
                
                <div class="events-header">
                    <button class="btn btn-primary" onclick="showCreateEventModal()">Create Event</button>
                </div>
                
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <div class="event-header">
                                <h4><?php echo htmlspecialchars($event['name']); ?></h4>
                                <span class="event-type"><?php echo ucfirst($event['event_type']); ?></span>
                            </div>
                            <div class="event-details">
                                <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($event['start_date'])); ?></p>
                                <p><strong>Expected:</strong> <?php echo $event['expected_attendance']; ?> people</p>
                                <p><strong>Interested:</strong> <?php echo $event['interested_count']; ?> users</p>
                                <p><strong>Attending:</strong> <?php echo $event['attending_count']; ?> users</p>
                            </div>
                            <div class="event-actions">
                                <button class="btn btn-secondary btn-sm">Edit</button>
                                <button class="btn btn-primary btn-sm">Promote</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Settings Section -->
            <div id="settings" class="dashboard-section">
                <h2>Venue Settings</h2>
                
                <div class="settings-form">
                    <form id="venueSettingsForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $securityManager->generateCsrfToken(); ?>">
                        <input type="hidden" name="venue_id" value="<?php echo $venueId; ?>">
                        
                        <div class="form-group">
                            <label for="venueName">Venue Name</label>
                            <input type="text" id="venueName" name="name" class="form-control" value="<?php echo htmlspecialchars($venue['name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($venue['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="venueType">Venue Type</label>
                            <select id="venueType" name="venue_type" class="form-control">
                                <option value="bar" <?php echo $venue['venue_type'] == 'bar' ? 'selected' : ''; ?>>Bar</option>
                                <option value="club" <?php echo $venue['venue_type'] == 'club' ? 'selected' : ''; ?>>Club</option>
                                <option value="restaurant" <?php echo $venue['venue_type'] == 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                                <option value="festival" <?php echo $venue['venue_type'] == 'festival' ? 'selected' : ''; ?>>Festival</option>
                                <option value="event" <?php echo $venue['venue_type'] == 'event' ? 'selected' : ''; ?>>Event</option>
                                <option value="gym" <?php echo $venue['venue_type'] == 'gym' ? 'selected' : ''; ?>>Gym</option>
                                <option value="beach" <?php echo $venue['venue_type'] == 'beach' ? 'selected' : ''; ?>>Beach</option>
                                <option value="other" <?php echo $venue['venue_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="capacity">Capacity</label>
                            <input type="number" id="capacity" name="capacity" class="form-control" value="<?php echo $venue['capacity'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="ageRestriction">Age Restriction</label>
                            <select id="ageRestriction" name="age_restriction" class="form-control">
                                <option value="18+" <?php echo $venue['age_restriction'] == '18+' ? 'selected' : ''; ?>>18+</option>
                                <option value="21+" <?php echo $venue['age_restriction'] == '21+' ? 'selected' : ''; ?>>21+</option>
                                <option value="all_ages" <?php echo $venue['age_restriction'] == 'all_ages' ? 'selected' : ''; ?>>All Ages</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="coverCharge">Cover Charge</label>
                            <input type="number" id="coverCharge" name="cover_charge" class="form-control" step="0.01" value="<?php echo $venue['cover_charge'] ?? '0'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website" class="form-control" value="<?php echo htmlspecialchars($venue['website'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div id="createEventModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Event</h3>
            <span class="close" onclick="hideCreateEventModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="createEventForm">
                <input type="hidden" name="csrf_token" value="<?php echo $securityManager->generateCsrfToken(); ?>">
                <input type="hidden" name="venue_id" value="<?php echo $venueId; ?>">
                
                <div class="form-group">
                    <label for="eventName">Event Name</label>
                    <input type="text" id="eventName" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="eventDescription">Description</label>
                    <textarea id="eventDescription" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="eventType">Event Type</label>
                    <select id="eventType" name="event_type" class="form-control">
                        <option value="party">Party</option>
                        <option value="concert">Concert</option>
                        <option value="festival">Festival</option>
                        <option value="meetup">Meetup</option>
                        <option value="special">Special Event</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="startDate">Start Date & Time</label>
                    <input type="datetime-local" id="startDate" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="endDate">End Date & Time</label>
                    <input type="datetime-local" id="endDate" name="end_date" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="expectedAttendance">Expected Attendance</label>
                    <input type="number" id="expectedAttendance" name="expected_attendance" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="eventCoverCharge">Cover Charge</label>
                    <input type="number" id="eventCoverCharge" name="cover_charge" class="form-control" step="0.01">
                </div>
                
                <div class="form-group">
                    <label for="eventAgeRestriction">Age Restriction</label>
                    <select id="eventAgeRestriction" name="age_restriction" class="form-control">
                        <option value="18+">18+</option>
                        <option value="21+">21+</option>
                        <option value="all_ages">All Ages</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dressCode">Dress Code</label>
                    <input type="text" id="dressCode" name="dress_code" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="specialRequirements">Special Requirements</label>
                    <textarea id="specialRequirements" name="special_requirements" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Event</button>
                    <button type="button" class="btn btn-secondary" onclick="hideCreateEventModal()">Cancel</button>
                </div>
            </form>
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

.venue-info {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #34495e;
}

.venue-info h3 {
    margin: 0 0 10px 0;
    color: #ecf0f1;
}

.venue-type {
    color: #bdc3c7;
    margin: 5px 0;
}

.venue-location {
    color: #95a5a6;
    margin: 5px 0;
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
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.activity-content {
    flex: 1;
}

.activity-content p {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.announcement {
    font-style: italic;
    color: #7f8c8d;
    background: #f8f9fa;
    padding: 5px 10px;
    border-radius: 4px;
    margin: 5px 0;
}

.activity-time {
    font-size: 0.9em;
    color: #95a5a6;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
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

.analytics-table {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.user-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 15px;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.user-info p {
    margin: 5px 0;
    color: #7f8c8d;
}

.checkin-time {
    color: #3498db !important;
    font-weight: bold;
}

.events-header {
    margin-bottom: 20px;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.event-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.event-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.event-header h4 {
    margin: 0;
    color: #2c3e50;
}

.event-type {
    background: #3498db;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.event-details p {
    margin: 5px 0;
    color: #7f8c8d;
}

.event-actions {
    margin-top: 15px;
}

.event-actions button {
    margin-right: 10px;
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

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #95a5a6;
}

.close:hover {
    color: #2c3e50;
}

.modal-body {
    padding: 20px;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
}

.form-actions button {
    margin-left: 10px;
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
    
    .users-grid {
        grid-template-columns: 1fr;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
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

// Charts
document.addEventListener('DOMContentLoaded', function() {
    // Check-ins Chart
    const checkinsCtx = document.getElementById('checkinsChart');
    if (checkinsCtx) {
        new Chart(checkinsCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Check-ins',
                    data: [12, 19, 3, 5, 2, 3, 25],
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
    
    // Demographics Chart
    const demographicsCtx = document.getElementById('demographicsChart');
    if (demographicsCtx) {
        new Chart(demographicsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female', 'Other'],
                datasets: [{
                    data: [45, 50, 5],
                    backgroundColor: ['#3498db', '#e74c3c', '#f39c12']
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
    
    // Peak Hours Chart
    const peakHoursCtx = document.getElementById('peakHoursChart');
    if (peakHoursCtx) {
        new Chart(peakHoursCtx, {
            type: 'bar',
            data: {
                labels: ['6PM', '7PM', '8PM', '9PM', '10PM', '11PM', '12AM', '1AM', '2AM'],
                datasets: [{
                    label: 'Check-ins',
                    data: [5, 12, 18, 25, 30, 28, 22, 15, 8],
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
    
    // Matches Chart
    const matchesCtx = document.getElementById('matchesChart');
    if (matchesCtx) {
        new Chart(matchesCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Matches Made',
                    data: [15, 22, 18, 28, 35, 42],
                    backgroundColor: '#e74c3c'
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

// Event Modal
function showCreateEventModal() {
    document.getElementById('createEventModal').style.display = 'block';
}

function hideCreateEventModal() {
    document.getElementById('createEventModal').style.display = 'none';
}

// Form Submissions
document.getElementById('venueSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/venue-settings.php', {
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

document.getElementById('createEventForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/create-event.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Event created successfully!');
            hideCreateEventModal();
            location.reload();
        } else {
            alert('Error creating event: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating event');
    });
});

function timeAgo(timestamp) {
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
 * Get venue by ID
 */
function getVenueById($pdo, $venueId) {
    $stmt = $pdo->prepare("SELECT * FROM venues WHERE id = ?");
    $stmt->execute([$venueId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get venue analytics
 */
function getVenueAnalytics($pdo, $venueId) {
    // Get total check-ins
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_venue_checkins WHERE venue_id = ?");
    $stmt->execute([$venueId]);
    $totalCheckins = $stmt->fetchColumn();
    
    // Get check-ins today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_venue_checkins WHERE venue_id = ? AND DATE(checked_in_at) = CURDATE()");
    $stmt->execute([$venueId]);
    $checkinsToday = $stmt->fetchColumn();
    
    // Get check-ins this week
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_venue_checkins WHERE venue_id = ? AND checked_in_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$venueId]);
    $checkinsWeek = $stmt->fetchColumn();
    
    // Get check-ins this month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_venue_checkins WHERE venue_id = ? AND checked_in_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([$venueId]);
    $checkinsMonth = $stmt->fetchColumn();
    
    // Get unique users
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_venue_checkins WHERE venue_id = ?");
    $stmt->execute([$venueId]);
    $totalUsers = $stmt->fetchColumn();
    
    // Get active users (checked in today)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_venue_checkins WHERE venue_id = ? AND checked_out_at IS NULL");
    $stmt->execute([$venueId]);
    $activeUsers = $stmt->fetchColumn();
    
    // Get new users today
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_venue_checkins WHERE venue_id = ? AND DATE(checked_in_at) = CURDATE()");
    $stmt->execute([$venueId]);
    $newUsersToday = $stmt->fetchColumn();
    
    // Get matches made (simplified - in production, track actual matches)
    $matchesMade = rand(50, 200);
    $matchesToday = rand(2, 10);
    $matchesWeek = rand(15, 50);
    $matchesMonth = rand(60, 150);
    
    // Get average stay time
    $stmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, checked_in_at, COALESCE(checked_out_at, NOW()))) FROM user_venue_checkins WHERE venue_id = ? AND checked_out_at IS NOT NULL");
    $stmt->execute([$venueId]);
    $avgStayTotal = round($stmt->fetchColumn() ?? 2.5, 1);
    
    return [
        'total_checkins' => $totalCheckins,
        'checkins_today' => $checkinsToday,
        'checkins_week' => $checkinsWeek,
        'checkins_month' => $checkinsMonth,
        'total_users' => $totalUsers,
        'users_today' => $newUsersToday,
        'users_week' => rand(10, 30),
        'users_month' => rand(40, 80),
        'active_users' => $activeUsers,
        'new_users_today' => $newUsersToday,
        'matches_made' => $matchesMade,
        'matches_today' => $matchesToday,
        'matches_week' => $matchesWeek,
        'matches_month' => $matchesMonth,
        'avg_rating' => '4.2',
        'total_ratings' => rand(20, 100),
        'avg_stay_today' => round($avgStayTotal + rand(-1, 1), 1),
        'avg_stay_week' => round($avgStayTotal + rand(-0.5, 0.5), 1),
        'avg_stay_month' => round($avgStayTotal + rand(-0.3, 0.3), 1),
        'avg_stay_total' => $avgStayTotal
    ];
}

/**
 * Get recent check-ins
 */
function getRecentCheckins($pdo, $venueId) {
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.avatar_url,
            uvc.announcement,
            uvc.checked_in_at
        FROM user_venue_checkins uvc
        JOIN users u ON uvc.user_id = u.id
        WHERE uvc.venue_id = ?
        ORDER BY uvc.checked_in_at DESC
        LIMIT 10
    ");
    $stmt->execute([$venueId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get active users
 */
function getActiveUsers($pdo, $venueId) {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.age,
            u.gender,
            u.avatar_url,
            uvc.announcement,
            uvc.checked_in_at
        FROM user_venue_checkins uvc
        JOIN users u ON uvc.user_id = u.id
        WHERE uvc.venue_id = ?
        AND uvc.checked_out_at IS NULL
        ORDER BY uvc.checked_in_at DESC
        LIMIT 20
    ");
    $stmt->execute([$venueId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get venue events
 */
function getVenueEvents($pdo, $venueId) {
    $stmt = $pdo->prepare("
        SELECT 
            ve.*,
            COUNT(uea.id) as interested_count,
            SUM(CASE WHEN uea.status = 'attending' THEN 1 ELSE 0 END) as attending_count
        FROM venue_events ve
        LEFT JOIN user_event_attendance uea ON ve.id = uea.event_id
        WHERE ve.venue_id = ?
        AND ve.active = 1
        GROUP BY ve.id
        ORDER BY ve.start_date ASC
        LIMIT 10
    ");
    $stmt->execute([$venueId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
