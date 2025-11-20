describe('Matching Flow', () => {
  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: {
        data: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('getUser');

    // Mock matches feed
    cy.intercept('GET', '**/api/matches*', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 2,
            compatibility_score: 0.9,
            profile: {
              display_name: 'Potential Match',
              age: 25,
              bio: 'I like hiking',
              location: {
                city: 'New York',
                state: 'NY'
              },
              photos: [
                { url: 'https://via.placeholder.com/150' }
              ]
            }
          },
          {
            id: 3,
            compatibility_score: 0.85,
            profile: {
              display_name: 'Another Match',
              age: 28,
              bio: 'Coffee lover',
              location: {
                city: 'Brooklyn',
                state: 'NY'
              },
              photos: [
                { url: 'https://via.placeholder.com/150' }
              ]
            }
          }
        ]
      }
    }).as('getMatches');

    // Mock match action
    cy.intercept('POST', '**/api/matches/action', {
      statusCode: 200,
      body: {
        action: 'like',
        is_match: false,
        message: 'Action recorded'
      }
    }).as('matchAction');
  });

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

  it('displays matches and allows swiping', () => {
    cy.on('window:console', (msg) => {
      console.log('Browser Console:', msg);
    });

    cy.visit('/matches', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });
    
    // Check if loading spinner is present initially
    // cy.get('.animate-spin').should('exist'); // Optional check

    // Wait for matches to load
    // cy.wait('@getMatches'); // Commented out to debug

    // Check if first match is visible
    cy.url().should('include', '/matches');
    
    cy.get('.animate-spin', { timeout: 10000 }).should('not.exist');
    
    cy.contains('Potential Match', { timeout: 10000 }).should('be.visible');

    cy.contains('Potential Match', { timeout: 10000 }).should('be.visible');
    cy.contains('25 years old').should('be.visible');
    cy.contains('I like hiking').should('be.visible');

    // Perform Like action (click Like button)
    cy.contains('button', 'Like').click({ force: true });

    // Verify API call
    cy.wait('@matchAction').its('request.body').should('deep.equal', {
      action: 'like',
      match_id: 2
    });

    // Verify next card appears
    cy.contains('Another Match').should('be.visible');
  });

  it('shows match modal on mutual like', () => {
    cy.intercept('POST', '**/api/matches/action', {
      statusCode: 200,
      body: {
        action: 'like',
        match_created: true,
        message: "It's a match!"
      }
    }).as('mutualMatch');

    cy.visit('/matches', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // cy.wait('@getMatches');

    cy.contains('button', 'Like', { timeout: 10000 }).click({ force: true });

    cy.wait('@mutualMatch');

    // Verify modal appears
    cy.contains("It's a Match!", { timeout: 10000 }).should('be.visible');
    cy.contains('liked each other').should('be.visible');
    
    // Close modal
    cy.contains('button', 'Keep Swiping').click();
    cy.contains("It's a Match!").should('not.exist');
  });
});
