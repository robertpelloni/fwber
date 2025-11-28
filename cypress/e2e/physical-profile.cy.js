before(() => {
    // Warm up the server to ensure compilation happens before tests
    cy.request({
      url: '/profile',
      failOnStatusCode: false,
      timeout: 120000
    });
  });

describe('Physical Profile Editor', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    emailVerifiedAt: new Date().toISOString(),
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    profile: {
      display_name: 'Test User',
      date_of_birth: '1990-01-01',
      gender: 'non-binary',
      pronouns: 'they/them',
      sexual_orientation: 'pansexual',
      relationship_style: 'polyamorous',
      bio: 'Test bio',
      location: {
        latitude: 40.7128,
        longitude: -74.0060,
        city: 'New York',
        state: 'NY',
        max_distance: 25
      },
      sti_status: 'negative',
      preferences: {},
      avatar_url: '/images/test-avatar.svg',
      photos: [
        { id: 1, url: '/images/p1.jpg', is_primary: true },
        { id: 2, url: '/images/p2.jpg', is_primary: false },
        { id: 3, url: '/images/p3.jpg', is_primary: false }
      ],
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
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
          body_type: 'average',
          avatar_prompt: 'test prompt'
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
      timeout: 120000,
      onBeforeLoad: (win) => {
        // Use dev bypass for robust auth
        win.localStorage.setItem('auth_token', 'dev');
      }
    });

    // Wait for profile fetch
    cy.wait('@getPhysicalProfile');

    // Check for error message
    cy.contains('Failed to load physical profile').should('not.exist');

    // Wait for profile to load
    cy.contains('Physical Attributes', { timeout: 10000 }).should('be.visible');

    // Verify initial data load
    cy.get('input[id="height_cm"]').should('have.value', '175');
    
    // Target the select specifically within the Physical Profile Editor section to avoid ID collision
    cy.contains('h2', 'Physical Attributes')
      .parent()
      .find('select[id="body_type"]')
      .should('have.value', 'average');

    // Update values
    cy.get('input[id="height_cm"]').clear().type('180');
    cy.contains('h2', 'Physical Attributes')
      .parent()
      .find('select[id="body_type"]')
      .select('Athletic');

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
        timeout: 120000,
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
