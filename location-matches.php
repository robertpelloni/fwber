<?php
/*
    Location-based matching page
    Shows nearby users and venue-based matches
*/

require_once('_init.php');

// Ensure the user is logged in
if (!validateSessionOrCookiesReturnLoggedIn()) {
    header('Location: /signin.php');
    exit();
}

$userId = getUserIdByEmail($_SESSION['email']);
$profileManager = new ProfileManager($pdo);
$userProfile = $profileManager->getProfile($userId);

// Get user's current location
$latitude = $userProfile['latitude'] ?? null;
$longitude = $userProfile['longitude'] ?? null;
$currentVenue = $userProfile['current_venue_name'] ?? null;

// Get nearby users
$nearbyUsers = [];
if ($latitude && $longitude) {
    $nearbyUsers = getNearbyUsers($pdo, $userId, $latitude, $longitude, 50);
}

// Get venue-based matches
$venueMatches = [];
if ($userProfile['current_venue_id']) {
    $venueMatches = getVenueMatches($pdo, $userId, $userProfile['current_venue_id']);
}

include('head.php');
?>

<div class="container">
    <h1>Location Matches</h1>
    
    <?php if (!$latitude || !$longitude): ?>
        <div class="alert alert-warning">
            <h3>📍 Enable Location Services</h3>
            <p>To see location-based matches, please enable location services and update your location.</p>
            <button id="enableLocation" class="btn btn-primary">Enable Location</button>
        </div>
    <?php else: ?>
        <div class="location-info">
            <h3>📍 Your Location</h3>
            <p><strong>Coordinates:</strong> <?php echo number_format($latitude, 4); ?>, <?php echo number_format($longitude, 4); ?></p>
            <?php if ($currentVenue): ?>
                <p><strong>Current Venue:</strong> <?php echo htmlspecialchars($currentVenue); ?></p>
            <?php endif; ?>
            <button id="updateLocation" class="btn btn-secondary">Update Location</button>
        </div>
    <?php endif; ?>

    <!-- Venue Check-in Section -->
    <div class="venue-section">
        <h3>🏢 Venue Check-in</h3>
        <div class="venue-controls">
            <button id="findNearbyVenues" class="btn btn-primary">Find Nearby Venues</button>
            <button id="manualCheckin" class="btn btn-secondary">Manual Check-in</button>
        </div>
        
        <div id="nearbyVenues" class="venue-list" style="display: none;">
            <!-- Nearby venues will be populated here -->
        </div>
        
        <div id="checkinForm" class="checkin-form" style="display: none;">
            <h4>Check-in to Venue</h4>
            <form id="venueCheckinForm">
                <input type="hidden" name="csrf_token" value="<?php echo $securityManager->generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="checkin">
                
                <div class="form-group">
                    <label for="venueName">Venue Name</label>
                    <input type="text" id="venueName" name="venue_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="announcement">Announcement (Optional)</label>
                    <textarea id="announcement" name="announcement" class="form-control" rows="3" 
                              placeholder="What are you looking for? What's your vibe?"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration (hours)</label>
                    <select id="duration" name="duration" class="form-control">
                        <option value="2">2 hours</option>
                        <option value="4" selected>4 hours</option>
                        <option value="6">6 hours</option>
                        <option value="8">8 hours</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Check In</button>
                <button type="button" id="cancelCheckin" class="btn btn-secondary">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Nearby Users Section -->
    <?php if (!empty($nearbyUsers)): ?>
        <div class="matches-section">
            <h3>👥 Nearby Users (<?php echo count($nearbyUsers); ?>)</h3>
            <div class="matches-grid">
                <?php foreach ($nearbyUsers as $user): ?>
                    <div class="match-card">
                        <div class="match-avatar">
                            <?php if ($user['avatar_url']): ?>
                                <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="match-info">
                            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                            <p><?php echo $user['age']; ?> • <?php echo ucfirst($user['gender']); ?></p>
                            <p class="distance"><?php echo number_format($user['distance_km'], 1); ?> km away</p>
                            <p class="last-seen">Last seen: <?php echo timeAgo($user['last_online']); ?></p>
                        </div>
                        <div class="match-actions">
                            <button class="btn btn-primary btn-sm" onclick="sendMatch(<?php echo $user['id']; ?>)">Match</button>
                            <button class="btn btn-secondary btn-sm" onclick="viewProfile(<?php echo $user['id']; ?>)">View</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="no-matches">
            <h3>👥 Nearby Users</h3>
            <p>No nearby users found. Try updating your location or checking into a venue!</p>
        </div>
    <?php endif; ?>

    <!-- Venue Matches Section -->
    <?php if (!empty($venueMatches)): ?>
        <div class="venue-matches-section">
            <h3>🏢 Venue Matches (<?php echo count($venueMatches); ?>)</h3>
            <div class="matches-grid">
                <?php foreach ($venueMatches as $user): ?>
                    <div class="match-card venue-match">
                        <div class="match-avatar">
                            <?php if ($user['avatar_url']): ?>
                                <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="match-info">
                            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                            <p><?php echo $user['age']; ?> • <?php echo ucfirst($user['gender']); ?></p>
                            <p class="venue-info">At <?php echo htmlspecialchars($user['venue_name']); ?></p>
                            <?php if ($user['announcement']): ?>
                                <p class="announcement">"<?php echo htmlspecialchars($user['announcement']); ?>"</p>
                            <?php endif; ?>
                        </div>
                        <div class="match-actions">
                            <button class="btn btn-primary btn-sm" onclick="sendMatch(<?php echo $user['id']; ?>)">Match</button>
                            <button class="btn btn-secondary btn-sm" onclick="viewProfile(<?php echo $user['id']; ?>)">View</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.location-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.venue-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border: 1px solid #dee2e6;
}

.venue-controls {
    margin-bottom: 20px;
}

.venue-controls button {
    margin-right: 10px;
}

.venue-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.venue-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    cursor: pointer;
    transition: all 0.3s ease;
}

.venue-item:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.checkin-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.matches-section, .venue-matches-section {
    margin-bottom: 30px;
}

.matches-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.match-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.match-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.match-avatar {
    margin-bottom: 15px;
}

.match-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    margin: 0 auto;
}

.match-info h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.match-info p {
    margin: 5px 0;
    color: #666;
    font-size: 14px;
}

.distance {
    color: #007bff !important;
    font-weight: bold;
}

.venue-info {
    color: #28a745 !important;
    font-weight: bold;
}

.announcement {
    font-style: italic;
    color: #6c757d !important;
    background: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    margin-top: 10px;
}

.match-actions {
    margin-top: 15px;
}

.match-actions button {
    margin: 0 5px;
}

.venue-match {
    border-left: 4px solid #28a745;
}

.no-matches {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

@media (max-width: 768px) {
    .matches-grid {
        grid-template-columns: 1fr;
    }
    
    .venue-list {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Location services
document.getElementById('enableLocation').addEventListener('click', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            updateLocation(position.coords.latitude, position.coords.longitude);
        }, function(error) {
            alert('Error getting location: ' + error.message);
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
});

document.getElementById('updateLocation').addEventListener('click', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            updateLocation(position.coords.latitude, position.coords.longitude);
        }, function(error) {
            alert('Error getting location: ' + error.message);
        });
    }
});

function updateLocation(lat, lng) {
    fetch('api/update-location.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'csrf_token': '<?php echo $securityManager->generateCsrfToken(); ?>',
            'latitude': lat,
            'longitude': lng,
            'check_in_type': 'gps'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating location: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating location');
    });
}

// Venue functionality
document.getElementById('findNearbyVenues').addEventListener('click', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            findNearbyVenues(position.coords.latitude, position.coords.longitude);
        }, function(error) {
            alert('Error getting location: ' + error.message);
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
});

document.getElementById('manualCheckin').addEventListener('click', function() {
    document.getElementById('checkinForm').style.display = 'block';
    document.getElementById('nearbyVenues').style.display = 'none';
});

document.getElementById('cancelCheckin').addEventListener('click', function() {
    document.getElementById('checkinForm').style.display = 'none';
});

function findNearbyVenues(lat, lng) {
    fetch('api/venue-checkin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'csrf_token': '<?php echo $securityManager->generateCsrfToken(); ?>',
            'action': 'nearby',
            'latitude': lat,
            'longitude': lng
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayNearbyVenues(data.venues);
        } else {
            alert('Error finding venues: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error finding venues');
    });
}

function displayNearbyVenues(venues) {
    const container = document.getElementById('nearbyVenues');
    container.innerHTML = '';
    
    if (venues.length === 0) {
        container.innerHTML = '<p>No nearby venues found. Try manual check-in.</p>';
    } else {
        venues.forEach(venue => {
            const venueDiv = document.createElement('div');
            venueDiv.className = 'venue-item';
            venueDiv.innerHTML = `
                <h5>${venue.name}</h5>
                <p>${venue.distance_km.toFixed(1)} km away</p>
                <p>${venue.active_users} active users</p>
                <button class="btn btn-primary btn-sm" onclick="checkinToVenue(${venue.id}, '${venue.name}')">Check In</button>
            `;
            container.appendChild(venueDiv);
        });
    }
    
    container.style.display = 'block';
    document.getElementById('checkinForm').style.display = 'none';
}

function checkinToVenue(venueId, venueName) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            performCheckin(venueId, venueName, position.coords.latitude, position.coords.longitude);
        }, function(error) {
            alert('Error getting location: ' + error.message);
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

function performCheckin(venueId, venueName, lat, lng) {
    const announcement = document.getElementById('announcement').value || '';
    const duration = document.getElementById('duration').value || 4;
    
    fetch('api/venue-checkin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'csrf_token': '<?php echo $securityManager->generateCsrfToken(); ?>',
            'action': 'checkin',
            'venue_id': venueId,
            'venue_name': venueName,
            'latitude': lat,
            'longitude': lng,
            'announcement': announcement,
            'duration': duration
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Checked in successfully!');
            location.reload();
        } else {
            alert('Error checking in: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error checking in');
    });
}

// Venue check-in form
document.getElementById('venueCheckinForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const formData = new FormData(this);
            formData.append('latitude', position.coords.latitude);
            formData.append('longitude', position.coords.longitude);
            
            fetch('api/venue-checkin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Checked in successfully!');
                    location.reload();
                } else {
                    alert('Error checking in: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking in');
            });
        }.bind(this), function(error) {
            alert('Error getting location: ' + error.message);
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
});

// Match functions
function sendMatch(userId) {
    // Implement match sending logic
    alert('Match sent to user ' + userId);
}

function viewProfile(userId) {
    // Implement profile viewing logic
    window.open('profile.php?id=' + userId, '_blank');
}

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
 * Get nearby users for location-based matching
 */
function getNearbyUsers($pdo, $userId, $latitude, $longitude, $radiusKm = 50) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.age,
            u.gender,
            u.latitude,
            u.longitude,
            u.avatar_url,
            u.last_online,
            (
                $earthRadius * acos(
                    cos(radians(?)) * 
                    cos(radians(u.latitude)) * 
                    cos(radians(u.longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(u.latitude))
                )
            ) AS distance_km
        FROM users u
        WHERE u.id != ?
        AND u.latitude IS NOT NULL 
        AND u.longitude IS NOT NULL
        AND u.active = 1
        AND u.last_online > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        HAVING distance_km <= ?
        ORDER BY distance_km ASC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$latitude, $longitude, $latitude, $userId, $radiusKm]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get venue-based matches
 */
function getVenueMatches($pdo, $userId, $venueId) {
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.age,
            u.gender,
            u.avatar_url,
            u.last_online,
            v.name as venue_name,
            uvc.announcement,
            uvc.checked_in_at
        FROM user_venue_checkins uvc
        JOIN users u ON uvc.user_id = u.id
        JOIN venues v ON uvc.venue_id = v.id
        WHERE uvc.venue_id = ?
        AND uvc.user_id != ?
        AND uvc.checked_out_at IS NULL
        AND u.active = 1
        ORDER BY uvc.checked_in_at DESC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$venueId, $userId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
