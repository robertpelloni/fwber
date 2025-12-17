describe('Account Settings', () => {
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
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  beforeEach(() => {
    cy.intercept('GET', '**/api/user', { statusCode: 200, body: { data: user } }).as('getUser');
    
    cy.visit('/settings/account', {
      onBeforeLoad: (win) => {
        win.localStorage.clear();
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });
    // Wait for hydration
    cy.wait(2000);
    cy.contains('h1', 'Account Settings').should('be.visible');
  });

  it('allows updating email address', () => {
    cy.intercept('PUT', '**/api/profile', {
      statusCode: 200,
      body: { message: 'Email updated successfully' }
    }).as('updateEmail');

    cy.contains('h2', 'Email Address').should('be.visible');
    cy.get('input[name="email"]').should('be.visible').clear().type('newemail@example.com');
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

    cy.contains('h2', 'Change Password').scrollIntoView().should('be.visible');
    
    cy.wait(1000); // Wait for any animations/renders

    // Check if inputs exist
    cy.get('input[name="current_password"]').should('exist');
    cy.get('input[name="password"]').should('exist');
    cy.get('input[name="password_confirmation"]').should('exist');

    // Type
    cy.get('input[name="current_password"]').type('oldpassword', { force: true });
    cy.get('input[name="password"]').type('newpassword', { force: true });
    cy.get('input[name="password_confirmation"]').type('newpassword', { force: true });
    
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

    cy.contains('h2', 'Delete Account').scrollIntoView().should('be.visible');

    // Click the initial delete button
    cy.contains('button', 'Delete Account').click({ force: true });
    
    cy.wait(1000); // Wait for confirmation section to appear

    // Should show confirmation modal/section
    cy.contains('Are you sure?').should('be.visible');
    cy.contains('This action cannot be undone').should('be.visible');
    
    cy.get('input[name="confirm_password"]').should('exist').should('be.visible').type('password123', { force: true });
    cy.contains('button', 'Delete My Account').click({ force: true });

    cy.wait('@deleteAccount');
    
    // Should redirect to login or home
    cy.url().should('include', '/login');
  });
});
