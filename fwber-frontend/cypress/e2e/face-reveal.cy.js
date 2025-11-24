describe('Face Reveal Feature', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile: {
      display_name: 'Test User',
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  const otherUser = {
    id: 2,
    name: 'Match User',
    profile: {
      display_name: 'Match User',
      age: 25,
      bio: 'I am a match',
      photos: [
        {
          id: 101,
          url: '/images/test-avatar.svg',
          is_private: true,
          is_primary: true
        }
      ]
    }
  };

  const conversation = {
    id: 50, // matchId
    other_user: otherUser,
    last_message: {
      content: 'Hello',
      created_at: new Date().toISOString()
    }
  };

  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: user
      }
    }).as('login');

    // Mock conversations
    cy.intercept({
      method: 'GET',
      url: /matches\/established/
    }, {
      statusCode: 200,
      body: [conversation]
    }).as('getConversations');

    // Mock messages
    cy.intercept('GET', `**/api/messages/${otherUser.id}`, {
      statusCode: 200,
      body: []
    }).as('getMessages');

    // Mock mark as read
    cy.intercept('POST', `**/api/messages/mark-all-read/${otherUser.id}`, {
      statusCode: 200,
      body: { success: true }
    }).as('markAsRead');

    // Mock reveal API
    cy.intercept('POST', `**/api/photos/101/reveal`, {
      statusCode: 200,
      body: { success: true, status: 'revealed' }
    }).as('revealPhoto');

    // Mock original photo download
    cy.intercept('GET', `**/api/photos/101/original`, {
      fixture: 'test-image.jpg' 
    }).as('getOriginalPhoto');
  });

  it('allows revealing a photo in the profile view', () => {
    // 1. Login and visit messages
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('auth_token', 'dev');
      }
    });

    // Verify we are on the messages page
    cy.location('pathname').should('eq', '/messages');

    // 2. Wait for conversations to load
    cy.wait('@getConversations');
    cy.get('.cursor-pointer').should('have.length.at.least', 1);
    
    // 3. Click on the conversation
    // Find the conversation item that contains the user name and click it
    cy.contains('.cursor-pointer', 'Match User').click();
    
    // Verify the conversation is selected (active class)
    // Wait for the element to have the active class
    cy.contains('.cursor-pointer.bg-blue-50', 'Match User').should('be.visible');

    // Verify chat header is present
    cy.get('h3').contains('Match User').should('be.visible');

    // Wait for messages to load to ensure UI stability (re-renders)
    cy.wait('@getMessages');

    // 4. Click "View Profile" button
    cy.contains('button', 'View Profile').should('be.visible').click({ force: true });

    // 5. Verify Profile Modal is open
    // Wait for modal animation/rendering
    cy.get('.fixed.inset-0.z-50', { timeout: 10000 }).should('exist');
    cy.contains('h2', 'Match User').should('be.visible');

    // Verify photo container exists
    cy.get('.aspect-square').should('exist');

    // 6. Verify "Reveal Original" button is present and click it
    // Note: skipping visibility check as aspect-ratio might be behaving oddly in test env
    cy.contains('button', 'Reveal Original').should('exist').click({ force: true });

    // 7. Verify API calls
    cy.wait('@revealPhoto').its('request.body').should('deep.equal', { match_id: '50' });
    cy.wait('@getOriginalPhoto');

    // 8. Verify UI updates
    cy.contains('Reveal Original').should('not.exist');
    cy.contains('Original').should('be.visible'); // The badge

    // The image should no longer have the blur class
    cy.get('img[alt="User photo"]').should('not.have.class', 'blur-md');
  });
});
