describe('Nearby Users', () => {
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
      avatarUrl: '/images/test-avatar.svg',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }
  };

  beforeEach(() => {
    // Inject MockEventSource
    cy.window().then((win) => {
      win.EventSource = MockEventSource;
    });

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

    // Mock nearby users
    cy.intercept('GET', '**/api/location/nearby*', {
      statusCode: 200,
      body: {
        success: true,
        data: [
          {
            id: 2,
            display_name: 'Nearby Friend',
            age: 25,
            gender: 'female',
            location: {
              latitude: 40.7130,
              longitude: -74.0060,
              distance: '20m',
              last_updated: new Date().toISOString()
            },
            privacy_level: 'public',
            is_recent: true
          },
          {
            id: 3,
            display_name: 'Far Friend',
            age: 30,
            gender: 'male',
            location: {
              latitude: 40.7200,
              longitude: -74.0060,
              distance: '800m',
              last_updated: new Date(Date.now() - 3600000).toISOString() // 1 hour ago
            },
            privacy_level: 'friends',
            is_recent: false
          }
        ],
        meta: {
          total: 2,
          radius: 1000,
          center: {
            latitude: 40.7128,
            longitude: -74.0060
          }
        }
      }
    }).as('getNearbyUsers');

    // Mock WebSocket token endpoint to avoid 404s
    cy.intercept('GET', '**/api/websocket/token', {
      statusCode: 200,
      body: {
        token: 'mock-mercure-token',
        hub_url: 'http://localhost:3000/.well-known/mercure'
      }
    }).as('getWebsocketToken');
  });

  it('loads login page', () => {
    cy.visit('/login');
    cy.contains('Sign in to fwber').should('be.visible');
  });

  it('redirects to login if unauthenticated', () => {
    cy.clearLocalStorage();
    cy.visit('/nearby');
    
    // Should redirect to login
    cy.url().should('include', '/login')
  });

  it('loads nearby users when authenticated', () => {
    cy.visit('/nearby', {
      onBeforeLoad(win) {
        // Mock auth token and user
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        win.EventSource = MockEventSource;
        
        // Mock geolocation
        cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((cb) => {
          return cb({
            coords: {
              latitude: 40.7128,
              longitude: -74.0060,
              accuracy: 10
            }
          });
        });
      }
    });

    // Wait for page to load
    cy.contains('People Nearby', { timeout: 10000 }).should('be.visible');
    
    // Wait for API call
    cy.wait('@getNearbyUsers');

    // Check for users
    cy.contains('Nearby Friend').should('be.visible');
    cy.contains('20m').should('be.visible');
    cy.contains('Active').should('be.visible');

    cy.contains('Far Friend').should('be.visible');
    cy.contains('800m').should('be.visible');
    cy.contains('Inactive').should('be.visible');
    
    // Check radius slider exists
    cy.get('input[type="range"]').should('exist');
  });
});
