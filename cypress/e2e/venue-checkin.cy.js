describe('Venue Check-in System', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile: {
      displayName: 'Test User',
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  const venues = [
    {
      id: 1,
      name: 'The Golden Bar',
      description: 'A fancy bar downtown',
      address: '123 Main St',
      city: 'New York',
      state: 'NY',
      zip: '10001',
      latitude: 40.7128,
      longitude: -74.0060
    },
    {
      id: 2,
      name: 'Club Neon',
      description: 'Late night dance club',
      address: '456 Broadway',
      city: 'New York',
      state: 'NY',
      zip: '10002',
      latitude: 40.7138,
      longitude: -74.0070
    }
  ];

  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: user
      }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: {
        data: user
      }
    }).as('getUser');

    // Mock venues list
    cy.intercept('GET', '**/api/venues', {
      statusCode: 200,
      body: {
        data: venues
      }
    }).as('getVenues');

    // Mock initial check-in status (not checked in)
    cy.intercept('GET', '**/api/user/checkin', {
      statusCode: 200,
      body: null
    }).as('getCheckin');
  });

  it('displays venues and allows check-in/check-out', () => {
    cy.visit('/venues', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // Verify venues are displayed
    cy.wait('@getVenues');
    cy.contains('The Golden Bar').should('be.visible');
    cy.contains('Club Neon').should('be.visible');
    cy.contains('New York, NY').should('be.visible');

    // Mock check-in action
    cy.intercept('POST', '**/api/venues/1/checkin', {
      statusCode: 200,
      body: {
        message: 'Checked in successfully',
        checkin: {
          id: 101,
          user_id: 1,
          venue_id: 1,
          venue: venues[0],
          checked_in_at: new Date().toISOString(),
          message: 'Hanging out!'
        }
      }
    }).as('checkIn');

    // Mock updated check-in status
    cy.intercept('GET', '**/api/user/checkin', {
      statusCode: 200,
      body: {
        id: 101,
        user_id: 1,
        venue_id: 1,
        venue: venues[0],
        checked_in_at: new Date().toISOString(),
        message: 'Hanging out!'
      }
    }).as('getCheckinUpdated');

    // Perform Check In
    cy.window().then(win => {
      cy.stub(win, 'prompt').returns('Hanging out!');
    });
    
    // Find the check-in button for the first venue and click it
    cy.contains('h2', 'The Golden Bar')
      .parents('.bg-white')
      .contains('button', 'Check In')
      .click();

    cy.wait('@checkIn');
    
    // Verify UI updates to show check-in status
    cy.contains('Checked in at:').should('be.visible');
    cy.contains('The Golden Bar').should('be.visible');
    cy.contains('"Hanging out!"').should('be.visible');
    
    // Verify the specific venue card shows "Checked In" badge
    cy.contains('h2', 'The Golden Bar')
      .parents('.bg-white')
      .contains('Checked In')
      .should('be.visible');

    // Mock check-out action
    cy.intercept('POST', '**/api/venues/1/checkout', {
      statusCode: 200,
      body: {
        message: 'Checked out successfully'
      }
    }).as('checkOut');

    // Mock cleared check-in status
    cy.intercept('GET', '**/api/user/checkin', {
      statusCode: 200,
      body: null
    }).as('getCheckinCleared');

    // Perform Check Out
    cy.contains('button', 'Check Out').click();
    cy.wait('@checkOut');

    // Verify UI updates to show available for check-in again
    cy.contains('Checked in at:').should('not.exist');
    cy.contains('h2', 'The Golden Bar')
      .parents('.bg-white')
      .contains('button', 'Check In')
      .should('be.visible');
  });
});
