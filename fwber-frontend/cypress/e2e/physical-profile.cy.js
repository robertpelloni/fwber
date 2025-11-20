describe('Physical Profile Editor', () => {
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
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'fake-token', user }
    }).as('login');

    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: { data: user }
    }).as('getUser');

    cy.intercept('GET', '**/api/profile/completeness', {
        statusCode: 200,
        body: { percentage: 50, missing_fields: [] }
    }).as('getCompleteness');

    cy.intercept('GET', '**/api/physical-profile', {
      statusCode: 200,
      body: {
        data: {
          height_cm: 175,
          body_type: 'average'
        }
      }
    }).as('getPhysicalProfile');

    cy.intercept('PUT', '**/api/physical-profile', {
      statusCode: 200,
      body: {
        data: {
          height_cm: 180,
          body_type: 'athletic'
        }
      }
    }).as('updatePhysicalProfile');
    
    cy.intercept('POST', '**/api/physical-profile/avatar/request', {
        statusCode: 200,
        body: {
            data: {
                avatar_status: 'requested'
            }
        }
    }).as('requestAvatar');
  });

  it('loads existing data and updates profile', () => {
    cy.visit('/profile', {
      onBeforeLoad: (win) => {
        // Use dev bypass for robust auth
        win.localStorage.setItem('auth_token', 'dev');
      }
    });

    // Wait for profile to load
    cy.contains('Physical Attributes', { timeout: 10000 }).should('be.visible');

    // Check existing values
    cy.get('input[id="height_cm"]').should('have.value', '175');
    cy.get('select[id="body_type"]').should('have.value', 'average');

    // Update values
    cy.get('input[id="height_cm"]').clear().type('180');
    cy.get('select[id="body_type"]').select('Athletic');

    // Save
    cy.contains('button', 'Save Physical Profile').click();

    cy.wait('@updatePhysicalProfile').its('request.body').should('include', {
      height_cm: 180,
      body_type: 'athletic'
    });

    cy.contains('Physical profile updated successfully').should('be.visible');
  });
  
  it('requests avatar generation', () => {
      cy.visit('/profile', {
        onBeforeLoad: (win) => {
          // Use dev bypass for robust auth
          win.localStorage.setItem('auth_token', 'dev');
        }
      });
      
      cy.get('textarea[id="avatar_prompt"]').type('A cool avatar');
      cy.contains('button', 'Generate Avatar').click();
      
      cy.wait('@requestAvatar').its('request.body').should('include', {
          style: 'realistic' // default
      });
      
      cy.contains('Avatar generation requested!').should('be.visible');
  });
});
