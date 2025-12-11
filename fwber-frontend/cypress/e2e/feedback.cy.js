describe('Feedback Loop', () => {
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
      avatarUrl: '/images/test-avatar.svg',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }
  };

  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: {
        data: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('getUser');

    // Mock feedback submission
    cy.intercept('POST', '**/api/feedback', {
      statusCode: 201,
      body: {
        success: true,
        message: 'Thank you for your feedback!',
        data: { id: 1, category: 'feature', message: 'Test' }
      }
    }).as('submitFeedback');

    // Visit dashboard with auth
    cy.visit('/dashboard', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        
        // Hide Performance Monitor
        const style = win.document.createElement('style');
        style.innerHTML = 'button:contains("Performance"), button:contains("Perfo") { display: none !important; }';
        win.document.head.appendChild(style);
      }
    });
  });

  it('allows submitting feedback', () => {
    // Open feedback modal
    cy.get('button[title="Send Feedback"]').click({ force: true });
    
    // Check if modal is open
    cy.contains('Send Feedback').should('be.visible');
    
    // Fill out form
    cy.get('select').select('Feature Request');
    cy.get('textarea').type('This is a test feedback message from Cypress.');
    
    // Submit
    cy.contains('button', 'Submit').click();
    
    // Check for success message
    cy.contains('Thank you for your feedback!').should('be.visible');
    
    // Modal should close automatically
    cy.wait(2500);
    cy.contains('Send Feedback').should('not.exist'); // The modal title should be gone
    cy.get('button[title="Send Feedback"]').should('exist'); // The trigger button should be back
  });

  it('validates required fields', () => {
    cy.get('button[title="Send Feedback"]').click({ force: true });
    
    // Try to submit without message
    cy.contains('button', 'Submit').should('be.disabled');
    
    // Type message
    cy.get('textarea').type('Test');
    cy.contains('button', 'Submit').should('not.be.disabled');
  });
});
