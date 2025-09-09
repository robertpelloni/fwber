<?php
/*
    Real-time Matching Demo Page
    Shows live matching functionality
*/

require_once('_init.php');

// Check if user is logged in
$loggedIn = validateSessionOrCookiesReturnLoggedIn();
if (!$loggedIn) {
    header('Location: /signin');
    exit();
}

$userId = getUserIdByEmail($_SESSION['email']);
if (!$userId) {
    // Should not happen if logged in, but good practice
    header('Location: /signin?error=user_not_found');
    exit();
}

$userProfile = getUserProfileById($userId);
if (!$userProfile) {
    // This might happen if the profile is incomplete. For the demo, we can create a placeholder.
    $userProfile = ['username' => 'New User', 'age' => ''];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Matching - FWBer</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .notification {
            pointer-events: auto;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/images/fwber_logo_icon.png" alt="FWBer" class="w-8 h-8 mr-3">
                    <h1 class="text-xl font-bold text-gray-800">Real-time Matching</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div id="connection-status" class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full pulse mr-2"></div>
                        <span class="text-sm text-gray-600">Connecting...</span>
                    </div>
                    <a href="/matches" class="text-primary hover:underline">View Matches</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="grid lg:grid-cols-3 gap-6">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Location Status -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="fas fa-map-marker-alt text-primary mr-2"></i>
                        Location Status
                    </h2>
                    <div id="location-status">
                        <p class="text-gray-600">Requesting location access...</p>
                    </div>
                    <button id="update-location" class="mt-4 bg-primary text-white px-4 py-2 rounded hover:bg-indigo-700 disabled:bg-gray-400" disabled>
                        Update Location
                    </button>
                </div>

                <!-- Recent Matches -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="fas fa-heart text-pink-500 mr-2"></i>
                        Recent Matches
                    </h2>
                    <div id="recent-matches">
                        <p class="text-gray-500">No recent matches</p>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="fas fa-clock text-green-500 mr-2"></i>
                        Real-time Activity
                    </h2>
                    <div id="activity-log" class="space-y-2 max-h-64 overflow-y-auto">
                        <!-- Activity items will be added here -->
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- User Profile Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-r from-pink-400 to-purple-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto mb-4">
                            <?php echo strtoupper(substr($userProfile['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($userProfile['username'] ?? 'User'); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($userProfile['age'] ?? ''); ?> years old</p>
                    </div>
                </div>

                <!-- Nearby Users -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">
                        <i class="fas fa-users text-blue-500 mr-2"></i>
                        Nearby Users
                    </h3>
                    <div id="nearby-users">
                        <p class="text-gray-500">No nearby users</p>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Statistics</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Active Matches:</span>
                            <span id="active-matches-count" class="font-semibold">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nearby Users:</span>
                            <span id="nearby-users-count" class="font-semibold">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Online Time:</span>
                            <span id="online-time" class="font-semibold">0m</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/js/realtime-matching.js"></script>
    <script>
        let onlineTime = 0;
        let realtimeMatching;
        
        // Initialize real-time matching
        document.addEventListener('DOMContentLoaded', () => {
            realtimeMatching = new RealtimeMatching({
                onConnected: handleConnected,
                onNewMatches: handleNewMatches,
                onNearbyUsers: handleNearbyUsers,
                onInteractions: handleInteractions,
                onError: handleError
            });
            
            setupLocationHandling();
            startOnlineTimer();
            logActivity('System initialized', 'info');
        });
        
        function handleConnected(data) {
            updateConnectionStatus(true);
            logActivity('Connected to real-time matching', 'success');
        }
        
        function handleNewMatches(data) {
            updateRecentMatches(data.matches);
            updateStatistic('active-matches-count', data.count);
            logActivity(`Found ${data.count} new match${data.count > 1 ? 'es' : ''}`, 'match');
        }
        
        function handleNearbyUsers(data) {
            updateStatistic('nearby-users-count', data.count);
            if (data.count > 0) {
                logActivity(`${data.count} users nearby`, 'nearby');
            }
        }
        
        function handleInteractions(data) {
            data.interactions.forEach(interaction => {
                logActivity(`${interaction.type} from ${interaction.from_user}`, 'interaction');
            });
        }
        
        function handleError(error) {
            updateConnectionStatus(false);
            logActivity('Connection error occurred', 'error');
        }
        
        function setupLocationHandling() {
            const locationStatus = document.getElementById('location-status');
            const updateButton = document.getElementById('update-location');
            
            if (!navigator.geolocation) {
                locationStatus.innerHTML = '<p class="text-red-600">Geolocation not supported</p>';
                return;
            }
            
            updateButton.addEventListener('click', updateLocation);
            
            // Auto-request location
            updateLocation();
        }
        
        function updateLocation() {
            const locationStatus = document.getElementById('location-status');
            const updateButton = document.getElementById('update-location');
            
            locationStatus.innerHTML = '<p class="text-blue-600">Getting your location...</p>';
            updateButton.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    locationStatus.innerHTML = `
                        <p class="text-green-600">
                            <i class="fas fa-check-circle mr-1"></i>
                            Location updated successfully
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            ${latitude.toFixed(6)}, ${longitude.toFixed(6)}
                        </p>
                    `;
                    updateButton.disabled = false;
                    logActivity('Location updated', 'info');
                },
                (error) => {
                    locationStatus.innerHTML = `<p class="text-red-600">Location access denied</p>`;
                    updateButton.disabled = false;
                    logActivity('Location access failed', 'error');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        }
        
        function updateConnectionStatus(connected) {
            const statusDiv = document.getElementById('connection-status');
            if (connected) {
                statusDiv.innerHTML = `
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-sm text-gray-600">Connected</span>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="w-3 h-3 bg-red-500 rounded-full pulse mr-2"></div>
                    <span class="text-sm text-gray-600">Disconnected</span>
                `;
            }
        }
        
        function updateRecentMatches(matches) {
            const container = document.getElementById('recent-matches');
            
            if (!matches || matches.length === 0) {
                container.innerHTML = '<p class="text-gray-500">No recent matches</p>';
                return;
            }
            
            const matchesHtml = matches.map(match => `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg mb-2">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-pink-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                            ${match.username.charAt(0).toUpperCase()}
                        </div>
                        <div class="ml-3">
                            <p class="font-semibold">${match.username}</p>
                            <p class="text-sm text-gray-500">${match.distance} km • ${match.compatibility}% match</p>
                        </div>
                    </div>
                    <button class="bg-primary text-white px-3 py-1 rounded-full text-sm hover:bg-indigo-700">
                        View
                    </button>
                </div>
            `).join('');
            
            container.innerHTML = matchesHtml;
        }
        
        function updateStatistic(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }
        
        function logActivity(message, type) {
            const log = document.getElementById('activity-log');
            const timestamp = new Date().toLocaleTimeString();
            
            const icons = {
                info: 'fas fa-info-circle text-blue-500',
                success: 'fas fa-check-circle text-green-500',
                error: 'fas fa-exclamation-circle text-red-500',
                match: 'fas fa-heart text-pink-500',
                nearby: 'fas fa-map-marker-alt text-purple-500',
                interaction: 'fas fa-comment text-orange-500'
            };
            
            const icon = icons[type] || icons.info;
            
            const activityItem = document.createElement('div');
            activityItem.className = 'flex items-center text-sm p-2 bg-gray-50 rounded';
            activityItem.innerHTML = `
                <i class="${icon} mr-2"></i>
                <span class="flex-1">${message}</span>
                <span class="text-gray-400 text-xs">${timestamp}</span>
            `;
            
            log.insertBefore(activityItem, log.firstChild);
            
            // Keep only last 20 items
            while (log.children.length > 20) {
                log.removeChild(log.lastChild);
            }
        }
        
        function startOnlineTimer() {
            setInterval(() => {
                onlineTime++;
                const minutes = Math.floor(onlineTime / 60);
                const seconds = onlineTime % 60;
                document.getElementById('online-time').textContent = `${minutes}m ${seconds}s`;
            }, 1000);
        }
    </script>
</body>
</html>