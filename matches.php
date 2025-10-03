<?php
require_once('_init.php');
require_once('MatchingEngine.php');
require_once('ProfileManager.php');

// Ensure the user is logged in
if (!validateSessionOrCookiesReturnLoggedIn()) {
    header('Location: /signin.php');
    exit();
}

// Redirect to edit profile if it's not done yet
if (!isProfileDone()) {
    header('Location: /edit-profile.php');
    exit();
}

$userId = getUserIdByEmail($_SESSION['email']);
$profileManager = new ProfileManager($pdo);
$userProfile = $profileManager->getProfile($userId);

?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo getSiteName(); ?> - AI-Enhanced Matches</title>
    <?php include("head.php");?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .match-card { 
            transition: all 0.3s ease; 
        }
        .match-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15); 
        }
        .score-circle {
            background: conic-gradient(from 0deg, #10b981 0deg, #10b981 var(--score-deg), #e5e7eb var(--score-deg), #e5e7eb 360deg);
        }
        .filter-badge {
            @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mr-2 mb-2 cursor-pointer transition-colors duration-200;
        }
        .filter-badge:hover {
            @apply bg-blue-200;
        }
        .filter-badge.active {
            @apply bg-blue-600 text-white;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
<?php include("h.php");?>

<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">AI-Enhanced Matches</h1>
        <p class="text-gray-600">Discover compatible connections powered by advanced matching algorithms</p>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Filter Your Matches</h2>
            <button id="toggle-filters" class="text-blue-600 hover:text-blue-800 transition-colors">
                <i class="fas fa-sliders-h mr-2"></i>Advanced Filters
            </button>
        </div>
        
        <div id="filter-panel" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Age Range</label>
                <div class="flex items-center space-x-2">
                    <input type="number" id="min-age" placeholder="18" min="18" max="99" 
                           class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <span class="text-gray-500">to</span>
                    <input type="number" id="max-age" placeholder="65" min="18" max="99"
                           class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Distance</label>
                <select id="max-distance" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="5">5 km</option>
                    <option value="10">10 km</option>
                    <option value="25" selected>25 km</option>
                    <option value="50">50 km</option>
                    <option value="100">100 km</option>
                    <option value="">Any distance</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select id="sort-by" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="score">Match Score</option>
                    <option value="distance">Distance</option>
                    <option value="activity">Last Active</option>
                    <option value="age">Age</option>
                </select>
            </div>
        </div>
        
        <div class="flex justify-end mt-4">
            <button id="apply-filters" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Find Matches
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Finding your perfect matches...</p>
    </div>

    <!-- No Matches State -->
    <div id="no-matches" class="hidden text-center py-12">
        <div class="max-w-md mx-auto">
            <i class="fas fa-heart-broken text-6xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No matches found</h3>
            <p class="text-gray-600 mb-4">Try adjusting your filters or completing more of your profile to find better matches.</p>
            <a href="/edit-profile.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                <i class="fas fa-user-edit mr-2"></i>Complete Profile
            </a>
        </div>
    </div>

    <!-- Matches Grid -->
    <div id="matches-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Match cards will be populated by JavaScript -->
    </div>

    <!-- Load More Button -->
    <div id="load-more" class="hidden text-center mt-8">
        <button class="bg-gray-600 text-white px-8 py-3 rounded-lg hover:bg-gray-700 transition-colors">
            Load More Matches
        </button>
    </div>
</div>

<?php include("f.php");?>

<script src="/js/jquery-3.4.1.min.js" type="text/javascript"></script>
<script type="text/javascript">
class MatchingInterface {
    constructor() {
        this.matches = [];
        this.currentPage = 1;
        this.loading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadMatches();
    }

    bindEvents() {
        $('#toggle-filters').on('click', () => {
            $('#filter-panel').toggleClass('hidden');
        });

        $('#apply-filters').on('click', () => {
            this.loadMatches(true);
        });

        $(document).on('click', '.match-action', (e) => {
            this.handleMatchAction(e);
        });
    }

    async loadMatches(reset = false) {
        if (this.loading) return;
        
        this.loading = true;
        $('#loading-state').removeClass('hidden');
        $('#no-matches, #matches-grid').addClass('hidden');

        const filters = {
            min_age: $('#min-age').val(),
            max_age: $('#max-age').val(),
            max_distance: $('#max-distance').val(),
            limit: 20
        };

        try {
            const response = await $.ajax({
                url: '/api/get-ai-matches.php',
                method: 'GET',
                data: filters,
                dataType: 'json'
            });

            if (response.success) {
                if (reset) {
                    this.matches = [];
                    $('#matches-grid').empty();
                }
                
                this.matches = this.matches.concat(response.matches);
                this.renderMatches(response.matches);
                
                if (this.matches.length === 0) {
                    $('#no-matches').removeClass('hidden');
                } else {
                    $('#matches-grid').removeClass('hidden');
                }
            } else {
                this.showError(response.error || 'Failed to load matches');
            }
        } catch (error) {
            this.showError('Network error. Please try again.');
        } finally {
            this.loading = false;
            $('#loading-state').addClass('hidden');
        }
    }

    renderMatches(matches) {
        const grid = $('#matches-grid');
        
        matches.forEach(match => {
            const scoreDeg = match.match_score * 3.6; // Convert to degrees
            
            const card = $(`
                <div class="match-card bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="relative">
                        <img src="${match.avatar_url}" alt="${match.username}" 
                             class="w-full h-48 object-cover" onerror="this.src='/images/default-avatar.png'">
                        <div class="absolute top-4 right-4">
                            <div class="score-circle w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                 style="--score-deg: ${scoreDeg}deg;">
                                ${match.match_score}%
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-xl font-semibold text-gray-900">${match.username}</h3>
                            <span class="text-sm text-gray-500">${match.age} years</span>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-3">
                            <i class="fas fa-map-marker-alt mr-1"></i>${match.location}
                            ${match.distance_km ? ` • ${match.distance_km} km away` : ''}
                        </p>
                        
                        ${match.interests ? `
                            <div class="mb-4">
                                <p class="text-sm text-gray-700 line-clamp-2">${match.interests}</p>
                            </div>
                        ` : ''}
                        
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-4">
                            <div>Compatibility: ${match.score_breakdown.interests * 100}%</div>
                            <div>Distance: ${match.score_breakdown.location * 100}%</div>
                            <div>Age Match: ${match.score_breakdown.age * 100}%</div>
                            <div>Activity: ${match.score_breakdown.activity * 100}%</div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button class="match-action flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition-colors"
                                    data-action="pass" data-user-id="${match.id}">
                                <i class="fas fa-times mr-1"></i>Pass
                            </button>
                            <button class="match-action flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors"
                                    data-action="interested" data-user-id="${match.id}">
                                <i class="fas fa-heart mr-1"></i>Interested
                            </button>
                        </div>
                    </div>
                </div>
            `);
            
            grid.append(card);
        });
    }

    async handleMatchAction(e) {
        const button = $(e.currentTarget);
        const action = button.data('action');
        const userId = button.data('user-id');
        const card = button.closest('.match-card');
        
        button.prop('disabled', true);
        
        try {
            const response = await $.ajax({
                url: '/api/match-action.php',
                method: 'POST',
                data: { action, user_id: userId },
                dataType: 'json'
            });
            
            if (response.success) {
                card.fadeOut(500, () => card.remove());
                
                if (action === 'interested' && response.mutual_match) {
                    this.showMatchNotification(response.match_data);
                }
            } else {
                this.showError(response.error || 'Action failed');
            }
        } catch (error) {
            this.showError('Network error. Please try again.');
        } finally {
            button.prop('disabled', false);
        }
    }

    showMatchNotification(matchData) {
        // Show a celebration modal for mutual matches
        const modal = $(`
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
                    <i class="fas fa-heart text-6xl text-pink-500 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">It's a Match! 🎉</h2>
                    <p class="text-gray-600 mb-6">You and ${matchData.username} are interested in each other!</p>
                    <div class="flex space-x-4">
                        <button class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600" onclick="$(this).closest('.fixed').remove()">
                            Close
                        </button>
                        <a href="/matches.php?show=fwbs" class="flex-1 bg-pink-500 text-white py-2 px-4 rounded-lg hover:bg-pink-600 text-center">
                            View Match
                        </a>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        setTimeout(() => modal.remove(), 10000);
    }

    showError(message) {
        const toast = $(`
            <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
                <i class="fas fa-exclamation-triangle mr-2"></i>${message}
            </div>
        `);
        
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(() => toast.remove()), 5000);
    }
}

// Initialize the matching interface
$(document).ready(() => {
    new MatchingInterface();
});
</script>

</body>
</html>
