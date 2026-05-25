describe('Notification Settings', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile: { displayName: 'Test User' }
  };

  const preferences = [
    {
      type: 'match_new',
      label: 'New Matches',
      mail: true,
      push: false,
      database: true
    },
    {
      type: 'message_new',
      label: 'New Messages',
      mail: false,
      push: true,
      database: true
    }
  ];

  beforeEach(() => {
    cy.intercept('GET', '**/api/user', { statusCode: 200, body: { data: user } }).as('getUser');
    cy.intercept('GET', '**/api/notification-preferences', { statusCode: 200, body: preferences }).as('getPreferences');
    
    cy.visit('/settings/notifications', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });
  });

  it('displays current preferences', () => {
    cy.wait('@getPreferences');
    
    // Check "New Matches" card
    cy.contains('h3', 'New Matches').parents('.border').within(() => {
      // Email should be checked
      cy.contains('p', 'Email Notifications').parents('.justify-between').find('button[role="switch"]').should('have.attr', 'aria-checked', 'true');
      // Push should be unchecked
      cy.contains('p', 'Push Notifications').parents('.justify-between').find('button[role="switch"]').should('have.attr', 'aria-checked', 'false');
    });
  });

  it('allows updating preferences', () => {
    cy.intercept('PUT', '**/api/notification-preferences/match_new', {
      statusCode: 200,
      body: { 
        type: 'match_new',
        label: 'New Matches',
        mail: true,
        push: true, // Toggled to true
        database: true
      }
    }).as('updatePreference');

    // Toggle Push for New Matches
    cy.contains('h3', 'New Matches').parents('.border').within(() => {
      cy.contains('p', 'Push Notifications').parents('.justify-between').find('button[role="switch"]').click();
    });

    cy.wait('@updatePreference').its('request.body').should('deep.equal', {
      push: true
    });
  });
});
