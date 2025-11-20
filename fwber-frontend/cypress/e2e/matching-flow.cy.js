describe('Matching Flow', () => {
  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '/api/user', {
      statusCode: 200,
      body: {
        data: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('getUser');

    // Mock matches feed
    cy.intercept('GET', '/api/matches*', {
      statusCode: 200,
      body: {
        matches: [
          {
            id: 2,
            name: 'Potential Match',
            age: 25,
            bio: 'I like hiking',
            distance: 5,
            match_score: 90,
            avatar_url: 'https://via.placeholder.com/150'
          },
          {
            id: 3,
            name: 'Another Match',
            age: 28,
            bio: 'Coffee lover',
            distance: 10,
            match_score: 85,
            avatar_url: 'https://via.placeholder.com/150'
          }
        ],
        total: 2
      }
    }).as('getMatches');

    // Mock match action
    cy.intercept('POST', '/api/matches/action', {
      statusCode: 200,
      body: {
        action: 'like',
        is_match: false,
        message: 'Action recorded'
      }
    }).as('matchAction');
  });

  it('displays matches and allows swiping', () => {
    // Visit page (assuming we have a way to bypass login or we mock the session)
    // For NextAuth, we might need to mock the session endpoint or use a custom command
    // Here we assume the app checks the token or session
    
    // Mock NextAuth session
    cy.intercept('GET', '/api/auth/session', {
      statusCode: 200,
      body: {
        user: { name: 'Test User', email: 'test@example.com' },
        expires: new Date(Date.now() + 86400 * 1000).toISOString()
      }
    }).as('session');

    cy.visit('/matches');
    
    // Wait for matches to load
    cy.wait('@getMatches');

    // Check if first match is visible
    cy.contains('Potential Match').should('be.visible');
    cy.contains('25').should('be.visible');
    cy.contains('I like hiking').should('be.visible');

    // Perform Like action (click heart button)
    cy.get('button[aria-label="Like"]').click();

    // Verify API call
    cy.wait('@matchAction').its('request.body').should('deep.equal', {
      action: 'like',
      target_user_id: 2
    });

    // Verify next card appears
    cy.contains('Another Match').should('be.visible');
  });

  it('shows match modal on mutual like', () => {
    cy.intercept('POST', '/api/matches/action', {
      statusCode: 200,
      body: {
        action: 'like',
        is_match: true,
        message: "It's a match!"
      }
    }).as('mutualMatch');

    cy.intercept('GET', '/api/auth/session', {
        statusCode: 200,
        body: {
          user: { name: 'Test User', email: 'test@example.com' },
          expires: new Date(Date.now() + 86400 * 1000).toISOString()
        }
      }).as('session');

    cy.visit('/matches');
    cy.wait('@getMatches');

    cy.get('button[aria-label="Like"]').click();

    cy.wait('@mutualMatch');

    // Verify modal
    cy.contains("It's a Match!").should('be.visible');
    cy.contains('You and Potential Match have liked each other').should('be.visible');
  });
});
