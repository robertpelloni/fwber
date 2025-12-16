describe('User Blocking', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile: { displayName: 'Test User' }
  };

  const blockedUsers = [
    {
      id: 2,
      name: 'Blocked User 1',
      profile: { displayName: 'Blocked User 1', avatarUrl: '/images/avatar1.png' },
      blocked_at: new Date().toISOString()
    },
    {
      id: 3,
      name: 'Blocked User 2',
      profile: { displayName: 'Blocked User 2', avatarUrl: '/images/avatar2.png' },
      blocked_at: new Date().toISOString()
    }
  ];

  beforeEach(() => {
    cy.intercept('GET', '**/api/user', { statusCode: 200, body: { data: user } }).as('getUser');
    cy.intercept('GET', '**/api/blocks', { statusCode: 200, body: { data: blockedUsers } }).as('getBlockedUsers');
    
    cy.visit('/settings/blocked', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });
  });

  it('displays list of blocked users', () => {
    cy.wait('@getBlockedUsers');
    cy.contains('Blocked User 1').should('be.visible');
    cy.contains('Blocked User 2').should('be.visible');
  });

  it('allows unblocking a user', () => {
    cy.intercept('DELETE', '**/api/blocks/2', {
      statusCode: 200,
      body: { message: 'User unblocked successfully' }
    }).as('unblockUser');

    cy.contains('Blocked User 1')
      .parents('li')
      .find('button')
      .contains('Unblock')
      .click();

    cy.contains('Are you sure you want to unblock this user?').should('be.visible');
    cy.contains('button', 'Confirm').click();

    cy.wait('@unblockUser');
    // The page doesn't show a toast for success, it just removes the user.
    // But maybe I should check if the user is gone.
    cy.contains('Blocked User 1').should('not.exist');
  });
});
