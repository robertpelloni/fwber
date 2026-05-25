describe('User Reporting Flow', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile: {
      displayName: 'Test User',
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  const badUser = {
    id: 666,
    name: 'Bad Actor',
    profile: {
      displayName: 'Bad Actor',
      avatarUrl: '/images/bad-avatar.svg',
      bio: 'I am bad'
    }
  };

  beforeEach(() => {
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

    // Mock bad user profile
    cy.intercept('GET', '**/api/users/666', {
      statusCode: 200,
      body: { data: badUser }
    }).as('getBadUser');
  });

  it('allows reporting a user from their profile', () => {
    cy.visit('/users/666', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // 1. Open Report Modal (usually in a dropdown or explicit button)
    // Assuming there is a "Report" button or menu item
    cy.contains('Report').click({ force: true }); // Force if it's in a menu

    // 2. Verify Modal
    cy.contains('Report User').should('be.visible');
    cy.contains('Why are you reporting this user?').should('be.visible');

    // 3. Select Reason
    cy.contains('Harassment').click();

    // 4. Add details
    cy.get('textarea[name="details"]').type('This user sent mean messages.');

    // 5. Mock Report API
    cy.intercept('POST', '**/api/reports', {
      statusCode: 201,
      body: { success: true, message: 'Report submitted' }
    }).as('submitReport');

    // 6. Submit
    cy.contains('button', 'Submit Report').click();

    // 7. Verify API Call
    cy.wait('@submitReport').its('request.body').should('deep.include', {
      reported_user_id: 666,
      reason: 'harassment', // Assuming value matches label or is mapped
      details: 'This user sent mean messages.'
    });

    // 8. Verify Success Message
    cy.contains('Report submitted').should('be.visible');
  });

  it('allows blocking a user', () => {
    cy.visit('/users/666', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // 1. Click Block (might be in menu)
    cy.contains('Block').click({ force: true });

    // 2. Verify Confirmation Modal
    cy.contains('Block User?').should('be.visible');
    
    // 3. Mock Block API
    cy.intercept('POST', '**/api/blocks', {
      statusCode: 201,
      body: { success: true }
    }).as('blockUser');

    // 4. Confirm Block
    cy.contains('button', 'Block').click();

    // 5. Verify API Call
    cy.wait('@blockUser').its('request.body').should('include', {
      blocked_user_id: 666
    });

    // 6. Verify Success UI (e.g. redirect or toast)
    cy.contains('User blocked').should('be.visible');
  });
});
