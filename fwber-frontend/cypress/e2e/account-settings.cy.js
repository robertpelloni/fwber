describe('Account Settings', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    emailVerifiedAt: new Date().toISOString(),
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    profile: {
      displayName: 'Test User',
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  beforeEach(() => {
    cy.intercept('GET', '**/api/user', { statusCode: 200, body: { data: user } }).as('getUser');
    
    cy.visit('/settings/account', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });
  });

  it('allows updating email address', () => {
    cy.intercept('PUT', '**/api/profile', {
      statusCode: 200,
      body: { message: 'Email updated successfully' }
    }).as('updateEmail');

    cy.contains('Email Address').should('be.visible');
    cy.get('input[name="email"]').clear().type('newemail@example.com');
    cy.contains('button', 'Save Changes').click();

    cy.wait('@updateEmail').its('request.body').should('deep.equal', {
      email: 'newemail@example.com'
    });

    cy.contains('Email updated successfully').should('be.visible');
  });

  it('allows changing password', () => {
    cy.intercept('PUT', '**/api/profile/password', {
      statusCode: 200,
      body: { message: 'Password updated successfully' }
    }).as('updatePassword');

    cy.contains('Change Password').should('be.visible');
    
    cy.get('input[name="current_password"]').scrollIntoView().should('be.visible').type('oldpassword', { force: true });
    cy.get('input[name="password"]').scrollIntoView().should('be.visible').type('newpassword', { force: true });
    cy.get('input[name="password_confirmation"]').scrollIntoView().should('be.visible').type('newpassword', { force: true });
    
    cy.contains('button', 'Update Password').click({ force: true });

    cy.wait('@updatePassword').its('request.body').should('include', {
      current_password: 'oldpassword',
      password: 'newpassword',
      password_confirmation: 'newpassword'
    });

    cy.contains('Password updated successfully').should('be.visible');
  });

  it('allows deleting account', () => {
    cy.intercept('DELETE', '**/api/profile', {
      statusCode: 200,
      body: { message: 'Account deleted successfully' }
    }).as('deleteAccount');

    // Click the button, not the header
    cy.contains('button', 'Delete Account').scrollIntoView().click({ force: true });
    
    // Should show confirmation modal
    cy.contains('Are you sure?').should('be.visible');
    cy.contains('This action cannot be undone').should('be.visible');
    
    cy.get('input[name="confirm_password"]').type('password123', { force: true });
    cy.contains('button', 'Delete My Account').click({ force: true });

    cy.wait('@deleteAccount');
    
    // Should redirect to login or home
    cy.url().should('include', '/login');
  });
});
