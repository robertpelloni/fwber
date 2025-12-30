describe('Matching Flow', () => {
  // Mock EventSource to prevent real connection attempts
  class MockEventSource {
    constructor(url) {
      this.url = url;
      this.readyState = 0; // CONNECTING
      setTimeout(() => {
        this.readyState = 1; // OPEN
        if (this.onopen) this.onopen({ type: 'open' });
      }, 10);
    }
    close() {
      this.readyState = 2; // CLOSED
    }
    addEventListener() {}
    removeEventListener() {}
  }

  beforeEach(() => {
    // Inject MockEventSource
    cy.window().then((win) => {
      win.EventSource = MockEventSource;
    });

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
        data: {
          matches: [
            {
              id: 2,
              name: 'Potential Match',
              bio: 'I like hiking',
              age: 25,
              locationDescription: 'New York, NY',
              avatarUrl: '/images/test-avatar.svg',
              compatibilityScore: 0.9,
              distance: 5
            },
            {
              id: 3,
              name: 'Another Match',
              bio: 'Coffee lover',
              age: 28,
              locationDescription: 'Brooklyn, NY',
              avatarUrl: '/images/test-avatar.svg',
              compatibilityScore: 0.85,
              distance: 10
            }
          ]
        }
      }
    }).as('getMatches');

    // Mock match action
    cy.intercept('POST', '**/api/matches/action', {
      statusCode: 200,
      body: {
        data: {
          action: 'like',
          is_match: false,
          message: 'Action recorded'
        }
      }
    }).as('matchAction');

    // Mock WebSocket token endpoint to avoid 404s
    cy.intercept('GET', '**/api/websocket/token', {
      statusCode: 200,
      body: {
        token: 'mock-mercure-token',
        hub_url: 'http://localhost:3000/.well-known/mercure'
      }
    }).as('getWebsocketToken');
  });

  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    emailVerifiedAt: new Date().toISOString(),
    onboarding_completed_at: new Date().toISOString(),
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
      avatarUrl: '/images/test-avatar.svg',
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
        win.EventSource = MockEventSource;
      }
    });
    
    // Check if loading spinner is present initially
    // cy.get('.animate-spin').should('exist'); // Optional check

    // Wait for matches to load
    // cy.wait('@getMatches'); // Commented out to debug

    // Check if first match is visible
    cy.url().should('include', '/matches');
    
    // Wait for loading to finish
    cy.contains('Loading matches...').should('not.exist');
    
    // Debug: Check if we hit the empty state
    cy.get('body').then($body => {
      if ($body.text().includes('No more matches')) {
        throw new Error('Matches list is empty, mock failed?');
      }
    });
    
    cy.contains('Potential Match', { timeout: 30000 }).should('be.visible');

    // The component renders "Name, Age" e.g. "Potential Match, 25"
    cy.contains('25').should('be.visible');
    cy.contains('I like hiking').should('be.visible');

    // Perform Like action (click Like button - Green Heart)
    cy.get('.text-green-500').click({ force: true });

    // Verify API call
    cy.wait('@matchAction').its('request.body').should('deep.equal', {
      action: 'like',
      target_user_id: 2
    });

    // Verify next card appears
    cy.contains('Another Match').should('be.visible');
  });

  it('shows match toast on mutual like', () => {
    cy.intercept('POST', '**/api/matches/action', {
      statusCode: 200,
      body: {
        data: {
          action: 'like',
          is_match: true,
          message: "It's a match!"
        }
      }
    }).as('mutualMatch');

    cy.visit('/matches', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        win.EventSource = MockEventSource;
      }
    });

    // cy.wait('@getMatches');

    // Click Like button
    cy.get('.text-green-500').parent().click({ force: true });

    cy.wait('@mutualMatch');

    // Verify toast appears
    cy.contains("You matched with Potential Match!", { timeout: 10000 }).should('be.visible');
  });
});
