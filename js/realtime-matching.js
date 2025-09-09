/**
 * Real-time matching client using Server-Sent Events
 * Handles live match updates, nearby users, and interactions
 */

class RealtimeMatching {
    constructor(options = {}) {
        this.eventSource = null;
        this.isConnected = false;
        this.reconnectInterval = 5000;
        this.maxReconnectAttempts = 10;
        this.reconnectAttempts = 0;
        
        // Callbacks
        this.onNewMatches = options.onNewMatches || this.defaultMatchHandler;
        this.onNearbyUsers = options.onNearbyUsers || this.defaultNearbyHandler;
        this.onInteractions = options.onInteractions || this.defaultInteractionHandler;
        this.onConnected = options.onConnected || this.defaultConnectedHandler;
        this.onError = options.onError || this.defaultErrorHandler;
        
        // UI elements
        this.matchNotificationContainer = document.getElementById('match-notifications') || this.createNotificationContainer();
        this.nearbyUsersContainer = document.getElementById('nearby-users');
        this.interactionContainer = document.getElementById('interactions');
        
        this.init();
    }
    
    init() {
        if (!window.EventSource) {
            console.error('Server-Sent Events not supported');
            return;
        }
        
        this.connect();
        this.setupLocationTracking();
        this.setupVisibilityHandling();
    }
    
    connect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        
        this.eventSource = new EventSource('/realtime-matches.php');
        
        this.eventSource.addEventListener('connected', (e) => {
            const data = JSON.parse(e.data);
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.onConnected(data);
        });
        
        this.eventSource.addEventListener('new_matches', (e) => {
            const data = JSON.parse(e.data);
            this.onNewMatches(data);
            this.showMatchNotification(data);
        });
        
        this.eventSource.addEventListener('nearby_users', (e) => {
            const data = JSON.parse(e.data);
            this.onNearbyUsers(data);
        });
        
        this.eventSource.addEventListener('interactions', (e) => {
            const data = JSON.parse(e.data);
            this.onInteractions(data);
            this.showInteractionNotification(data);
        });
        
        this.eventSource.addEventListener('error', (e) => {
            this.isConnected = false;
            this.handleConnectionError(e);
        });
        
        this.eventSource.onerror = (e) => {
            this.isConnected = false;
            this.handleConnectionError(e);
        };
    }
    
    handleConnectionError(error) {
        console.error('SSE Connection error:', error);
        this.onError(error);
        
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectInterval);
        } else {
            console.error('Max reconnection attempts reached');
            this.showOfflineNotification();
        }
    }
    
    setupLocationTracking() {
        if (!navigator.geolocation) {
            console.warn('Geolocation not supported');
            return;
        }
        
        // Update location every 5 minutes
        const updateLocation = () => {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.updateUserLocation(position.coords.latitude, position.coords.longitude);
                },
                (error) => {
                    console.warn('Location update failed:', error);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
            );
        };
        
        // Initial location
        updateLocation();
        
        // Periodic updates
        setInterval(updateLocation, 300000); // 5 minutes
    }
    
    setupVisibilityHandling() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page is hidden, reduce update frequency
                if (this.eventSource) {
                    this.eventSource.close();
                }
            } else {
                // Page is visible, resume normal updates
                this.connect();
            }
        });
    }
    
    async updateUserLocation(lat, lng) {
        try {
            await fetch('/api/update-location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng,
                    timestamp: Date.now()
                })
            });
        } catch (error) {
            console.error('Failed to update location:', error);
        }
    }
    
    showMatchNotification(data) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            this.showInAppNotification(`🎉 ${data.count} new match${data.count > 1 ? 'es' : ''}!`, 'match');
            return;
        }
        
        // Show browser notification
        const notification = new Notification('New Match!', {
            body: `You have ${data.count} new match${data.count > 1 ? 'es' : ''}`,
            icon: '/android-chrome-192x192.png',
            badge: '/android-chrome-96x96.png',
            vibrate: [200, 100, 200],
            tag: 'match-notification',
            data: { matches: data.matches }
        });
        
        notification.onclick = () => {
            window.focus();
            window.location.href = '/matches';
            notification.close();
        };
        
        // Also show in-app notification
        this.showInAppNotification(`🎉 ${data.count} new match${data.count > 1 ? 'es' : ''}!`, 'match');
    }
    
    showInteractionNotification(data) {
        data.interactions.forEach(interaction => {
            let message = '';
            switch (interaction.type) {
                case 'like':
                    message = `${interaction.from_user} liked you!`;
                    break;
                case 'message':
                    message = `New message from ${interaction.from_user}`;
                    break;
                case 'match':
                    message = `It's a match with ${interaction.from_user}!`;
                    break;
                default:
                    message = `New interaction from ${interaction.from_user}`;
            }
            
            this.showInAppNotification(message, interaction.type);
        });
    }
    
    showInAppNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fixed top-4 right-4 bg-white border-l-4 border-primary p-4 shadow-lg rounded-lg transform translate-x-full transition-transform duration-300 z-50`;
        
        const icon = this.getNotificationIcon(type);
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="text-2xl mr-3">${icon}</div>
                <div>
                    <p class="font-semibold text-gray-800">${message}</p>
                    <p class="text-sm text-gray-500">${new Date().toLocaleTimeString()}</p>
                </div>
                <button class="ml-4 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        this.matchNotificationContainer.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }
    
    showOfflineNotification() {
        this.showInAppNotification('Connection lost. Real-time updates disabled.', 'error');
    }
    
    getNotificationIcon(type) {
        const icons = {
            match: '💕',
            like: '❤️',
            message: '💬',
            nearby: '📍',
            error: '⚠️'
        };
        return icons[type] || '📱';
    }
    
    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'match-notifications';
        container.className = 'fixed top-0 right-0 w-80 max-w-full p-4 pointer-events-none';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
    
    // Default handlers
    defaultMatchHandler(data) {
        console.log('New matches:', data);
    }
    
    defaultNearbyHandler(data) {
        console.log('Nearby users:', data);
        this.updateNearbyUsersUI(data);
    }
    
    defaultInteractionHandler(data) {
        console.log('New interactions:', data);
    }
    
    defaultConnectedHandler(data) {
        console.log('Connected to real-time matching:', data);
    }
    
    defaultErrorHandler(error) {
        console.error('Real-time matching error:', error);
    }
    
    updateNearbyUsersUI(data) {
        if (!this.nearbyUsersContainer) return;
        
        if (data.count === 0) {
            this.nearbyUsersContainer.innerHTML = '<p class="text-gray-500">No users nearby</p>';
            return;
        }
        
        const usersHtml = data.users.map(user => `
            <div class="flex items-center justify-between p-3 bg-white rounded-lg shadow mb-2">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-r from-pink-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                        ${user.username.charAt(0).toUpperCase()}
                    </div>
                    <div class="ml-3">
                        <p class="font-semibold">${user.username}</p>
                        <p class="text-sm text-gray-500">${user.distance} km away</p>
                    </div>
                </div>
                <button class="bg-primary text-white px-3 py-1 rounded-full text-sm hover:bg-indigo-700">
                    Say Hi
                </button>
            </div>
        `).join('');
        
        this.nearbyUsersContainer.innerHTML = `
            <h3 class="text-lg font-semibold mb-3">Nearby (${data.count})</h3>
            ${usersHtml}
        `;
    }
    
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        this.isConnected = false;
    }
    
    getConnectionStatus() {
        return this.isConnected;
    }
}