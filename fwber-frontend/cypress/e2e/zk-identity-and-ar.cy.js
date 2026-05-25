describe('ZK-Identity and AR Navigation Flows', () => {
    beforeEach(() => {
      // Mock the backend API responses for authentication and features
      cy.intercept('POST', '/api/auth/login', {
        statusCode: 200,
        body: { token: 'mock-token', user: { id: 1, name: 'Test User' } }
      }).as('login');
      
      cy.intercept('GET', '/api/profile', {
        statusCode: 200,
        body: {
          data: {
            id: 1,
            display_name: 'Test User',
            is_id_verified: true,
            zk_id_issuer: 'civic-pass'
          }
        }
      }).as('getProfile');
  
      // Mock matches
      cy.intercept('GET', '/api/matches*', {
        statusCode: 200,
        body: {
          matches: [
            {
              id: 'match-123',
              user1_id: 1,
              user2_id: 2,
              other_user: {
                id: 2,
                profile: { display_name: 'AR Target User', is_id_verified: true }
              }
            }
          ]
        }
      }).as('getMatches');
  
      cy.intercept('GET', '/api/location/aura/*', {
        statusCode: 200,
        body: {
          id: 2,
          name: 'AR Target User',
          latitude: 40.7128,
          longitude: -122.4000,
          last_updated: new Date().toISOString()
        }
      }).as('getAura');
      
      cy.visit('/login');
      cy.get('input[type="email"]').type('test@example.com');
      cy.get('input[type="password"]').type('password123');
      cy.get('button[type="submit"]').click();
      cy.wait('@login');
    });
  
    it('should display ZK-Identity verification badge on profile', () => {
      cy.visit('/profile');
      cy.wait('@getProfile');
      
      // Look for the identity verification badge
      cy.get('[data-testid="zk-verified-badge"]').should('exist');
      cy.contains('Verified via civic-pass').should('be.visible');
    });
  
    it('should open AR Navigation view from a match', () => {
      // Mock geolocation API
      cy.window().then((win) => {
        cy.stub(win.navigator.geolocation, 'watchPosition').callsFake((success) => {
          success({ coords: { latitude: 40.7125, longitude: -122.4005, accuracy: 10 }});
          return 1;
        });
      });
  
      cy.visit('/messages');
      cy.wait('@getMatches');
  
      // Click the first conversation
      cy.contains('AR Target User').click();
  
      // Click the AR Locate button (lightning bolt / map pin)
      cy.get('button[title="Locate Match (AR)"]').should('be.visible').click();
  
      // Verify AR modal/overlay opens
      cy.wait('@getAura');
      cy.contains('AR Navigation').should('be.visible');
      cy.contains('Distance:').should('be.visible');
      
      // Close the AR view
      cy.get('[data-testid="close-ar-view"]').click();
      cy.contains('AR Navigation').should('not.exist');
    });
  });
