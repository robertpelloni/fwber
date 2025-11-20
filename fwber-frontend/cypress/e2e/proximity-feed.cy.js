describe('Proximity Feed (Local Pulse)', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    emailVerifiedAt: new Date().toISOString(),
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    profile: {
      displayName: 'Test User',
      dateOfBirth: '1990-01-01',
      gender: 'non-binary',
      pronouns: 'they/them',
      sexualOrientation: 'pansexual',
      relationshipStyle: 'polyamorous',
      bio: 'Test bio',
      locationLatitude: 40.7128,
      locationLongitude: -74.0060,
      locationDescription: 'New York, NY',
      stiStatus: 'negative',
      preferences: {},
      avatarUrl: 'https://via.placeholder.com/150',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }
  };

  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'fake-token', user }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: { data: user }
    }).as('getUser');

    // Mock proximity feed
    cy.intercept('GET', '**/api/proximity/feed*', {
      statusCode: 200,
      body: {
        artifacts: [
          {
            id: 101,
            user_id: 2,
            type: 'text',
            content: 'Anyone want to play frisbee?',
            latitude: 40.7128,
            longitude: -74.0060,
            created_at: new Date().toISOString(),
            expires_at: new Date(Date.now() + 86400000).toISOString(),
            distance: 50
          }
        ]
      }
    }).as('getFeed');

    // Mock create artifact
    cy.intercept('POST', '**/api/proximity/artifacts', {
      statusCode: 201,
      body: {
        data: {
          id: 102,
          user_id: 1,
          type: 'text',
          content: 'New post content',
          latitude: 40.7128,
          longitude: -74.0060,
          created_at: new Date().toISOString(),
          expires_at: new Date(Date.now() + 86400000).toISOString(),
          distance: 0
        }
      }
    }).as('createArtifact');
  });

  it('shows loading state if unauthenticated', () => {
    cy.visit('/pulse');
    cy.contains('Finding local pulse...').should('be.visible');
  });

  it('loads the feed when authenticated', () => {
    // Set up the authenticated state with onBeforeLoad
    cy.visit('/pulse', {
      onBeforeLoad(win) {
        // Use the AuthProvider's built-in dev bypass
        win.localStorage.setItem('auth_token', 'dev');
        // And our component's geo bypass
        win.localStorage.setItem('mock_geo', 'true');
      }
    });

    // Wait for potential redirect or load
    cy.wait(1000);

    // Check that we don't have errors
    cy.contains('Geolocation is not supported').should('not.exist');
    cy.contains('Could not get your location').should('not.exist');
    cy.contains('Failed to load local pulse').should('not.exist');

    // Ensure the page content is loaded
    cy.contains('h1', 'Local Pulse', { timeout: 10000 }).should('be.visible');

    // Wait for feed request
    cy.wait('@getFeed');

    // Check for existing artifact
    cy.contains('Anyone want to play frisbee?', { timeout: 10000 }).should('be.visible');
    cy.contains('50m away').should('be.visible');

    // Post new artifact
    cy.get('textarea[placeholder*="What\'s happening"]').type('New post content');
    cy.contains('button', 'Post').click();

    cy.wait('@createArtifact').its('request.body').should('include', {
      content: 'New post content',
      type: 'text'
    });
  });
});
