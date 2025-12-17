describe('Onboarding Flow', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile_complete: false
  };

  beforeEach(() => {
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: user
      }
    }).as('login');

    cy.intercept('GET', '**/api/profile', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          email: 'test@example.com',
          profile: {
            display_name: '',
            location: { city: '', state: '', latitude: 0, longitude: 0 },
            preferences: {}
          }
        }
      }
    }).as('getProfile');

    cy.intercept('PUT', '**/api/profile', {
      statusCode: 200,
      body: {
        success: true,
        data: {
          id: 1,
          profile: {
            display_name: 'Test User',
            birthdate: '1990-01-01',
            gender: 'non-binary'
          }
        }
      }
    }).as('updateProfile');
  });

  it('completes the basic info step', () => {
    cy.visit('/onboarding', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // Welcome step
    cy.contains('Welcome to FWBer!').should('be.visible');
    cy.contains('Next').click();

    // Basic Info step
    cy.contains('Basic Info').should('be.visible');
    
    // Wait for hydration/render and ensure input is ready
    cy.wait(1000); // Wait for any initial state updates
    cy.get('#display_name').should('be.visible').click().clear().type('Test User');
    cy.get('#display_name').should('have.value', 'Test User'); // Verify input value before proceeding

    cy.get('#dob').type('1990-01-01');
    cy.get('#gender').select('non-binary');
    
    // Leave location empty to test sanitization
    
    cy.contains('Next').click();

    // Verify payload
    cy.wait('@updateProfile').then((interception) => {
      const body = interception.request.body;
      expect(body.display_name).to.equal('Test User');
      expect(body.birthdate).to.equal('1990-01-01');
      expect(body.gender).to.equal('non-binary');
      
      // Verify location was sanitized (empty fields removed)
      expect(body.location).to.deep.equal({});
    });

    // Should move to Photos step
    cy.contains('Add your best photos').should('be.visible');
  });
});
