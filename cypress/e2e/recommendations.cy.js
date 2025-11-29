describe('AI-Powered Recommendations', () => {
  const testUser = {
    name: 'Recommendation Test User',
    email: `rec-test-${Date.now()}@example.com`,
    password: 'password123',
  };

  before(() => {
    // Register a new user once for all tests
    cy.visit('/register');
    cy.get('input[name="name"]').type(testUser.name);
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('input[name="passwordConfirmation"]').type(testUser.password);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
    cy.contains(`Welcome, ${testUser.name}`);
    cy.get('button').contains('Logout').click();
  });

  beforeEach(() => {
    // Login before each test
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');

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
    // Should not see location recommendation if we only mock AI response for this tab
    // But since we mocked it specifically, we can check.
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
