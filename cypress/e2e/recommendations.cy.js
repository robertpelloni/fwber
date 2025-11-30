describe('AI-Powered Recommendations', () => {
  const testUser = {
    id: 1,
    name: 'Recommendation Test User',
    email: `rec-test-${Date.now()}@example.com`,
    password: 'password123',
    profile: {
      bio: 'Test bio',
      locationLatitude: 40.7128,
      locationLongitude: -74.0060
    }
  };

  beforeEach(() => {
    // Mock Auth Endpoints
    cy.intercept('POST', '/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'mock-jwt-token',
        user: testUser,
        message: 'Login successful'
      }
    }).as('login');

    cy.intercept('GET', '/api/user', {
      statusCode: 200,
      body: testUser
    }).as('getUser');

    // Mock Dashboard Stats (needed if login redirects to dashboard)
    cy.intercept('GET', '/api/dashboard/stats', {
      statusCode: 200,
      body: {
        total_matches: 5,
        pending_matches: 2,
        accepted_matches: 3,
        conversations: 4,
        profile_views: 12,
        today_views: 3,
        match_score_avg: 85,
        response_rate: 90,
        days_active: 1,
        last_login: new Date().toISOString()
      }
    }).as('getDashboardStats');

    // Mock Recommendations API
    cy.intercept('GET', '/api/recommendations?*', {
      statusCode: 200,
      body: {
        recommendations: [
          {
            id: 'rec_1',
            type: 'ai',
            score: 0.95,
            reason: 'Based on your interest in hiking',
            content: {
              id: 'content_1',
              title: 'Hiking Group in Yosemite',
              description: 'Join us for a weekend hike.',
            },
          },
          {
            id: 'rec_2',
            type: 'location',
            score: 0.88,
            reason: 'Near your location',
            content: {
              id: 'content_2',
              name: 'Downtown Coffee Meetup',
              description: 'Socialize with locals.',
              distance: 500,
            },
          },
        ],
        metadata: {
          total: 2,
          types: ['ai', 'location'],
          generated_at: new Date().toISOString(),
          cache_hit: false,
        },
      },
    }).as('getRecommendations');

    cy.intercept('GET', '/api/recommendations/type/ai*', {
      statusCode: 200,
      body: {
        recommendations: [
          {
            id: 'rec_1',
            type: 'ai',
            score: 0.95,
            reason: 'Based on your interest in hiking',
            content: {
              id: 'content_1',
              title: 'Hiking Group in Yosemite',
              description: 'Join us for a weekend hike.',
            },
          },
        ],
        metadata: { total: 1 },
      },
    }).as('getAIRecommendations');

    cy.intercept('GET', '/api/recommendations/trending*', {
      statusCode: 200,
      body: {
        trending: [
          {
            id: 'trend_1',
            type: 'bulletin_board',
            title: 'Trending Topic 1',
            description: 'Popular discussion topic',
            engagement_score: 0.95,
          },
        ],
        metadata: { total: 1 },
      },
    }).as('getTrending');

    cy.intercept('POST', '/api/recommendations/feedback', {
      statusCode: 200,
      body: {
        message: 'Feedback recorded successfully',
        feedback_id: 'fb_123',
      },
    }).as('submitFeedback');

    // Perform Login
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();
    cy.wait('@login');
    cy.url().should('include', '/dashboard');

    // Visit the recommendations page
    cy.visit('/recommendations');
  });

  it('should load the recommendations page and display mixed recommendations', () => {
    cy.wait('@getRecommendations');
    cy.contains('AI-Powered Recommendations').should('be.visible');
    cy.contains('Hiking Group in Yosemite').should('be.visible');
    cy.contains('Downtown Coffee Meetup').should('be.visible');
    cy.contains('AI').should('be.visible');
    cy.contains('LOCATION').should('be.visible');
  });

  it('should switch tabs and load specific recommendation types', () => {
    cy.contains('button', 'AI-Powered').click();
    cy.wait('@getAIRecommendations');
    cy.contains('Hiking Group in Yosemite').should('be.visible');
  });

  it('should load trending recommendations', () => {
    cy.contains('button', 'Trending').click();
    cy.wait('@getTrending');
    cy.contains('Trending Topic 1').should('be.visible');
  });

  it('should handle feedback actions', () => {
    cy.wait('@getRecommendations');
    // Click "Like" button (thumbs up)
    cy.contains('Hiking Group in Yosemite')
      .parents('.bg-gray-800')
      .find('button')
      .contains('👍')
      .click();

    cy.wait('@submitFeedback').its('request.body').should('deep.include', {
      action: 'like',
      rating: 5,
    });
  });

  it('should refresh recommendations', () => {
    cy.contains('button', 'Refresh').click();
    cy.wait('@getRecommendations'); // Should trigger a new fetch
  });
});
