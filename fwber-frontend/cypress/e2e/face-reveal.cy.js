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
          url: '/images/blurred-photo.jpg',
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
    cy.intercept('GET', '**/api/conversations', {
      statusCode: 200,
      body: [conversation]
    }).as('getConversations');

    // Mock messages
    cy.intercept('GET', `**/api/messages/${otherUser.id}`, {
      statusCode: 200,
      body: []
    }).as('getMessages');

    // Mock reveal API
    cy.intercept('POST', `**/api/photos/101/reveal`, {
      statusCode: 200,
      body: { success: true, status: 'revealed' }
    }).as('revealPhoto');

    // Mock original photo download
    // We return a simple blob/image response
    cy.intercept('GET', `**/api/photos/101/original`, {
      fixture: 'test-image.jpg' 
    }).as('getOriginalPhoto');
  });

  it('allows revealing a photo in the profile view', () => {
    // 1. Login and visit messages
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // 2. Wait for conversations to load
    cy.wait('@getConversations');
    cy.contains('Match User').should('be.visible');

    // 3. Click on the conversation
    cy.contains('Match User').click();
    cy.wait('@getMessages');

    // 4. Click "View Profile"
    cy.contains('View Profile').click();

    // 5. Verify Profile Modal is open and photo is blurred (visually checked by class or structure)
    cy.contains('Match User').should('be.visible');
    cy.contains('Reveal Original').should('be.visible');

    // Check for blur class
    cy.get('img[alt="User photo"]').should('have.class', 'blur-md');

    // 6. Click "Reveal Original"
    cy.contains('button', 'Reveal Original').click();

    // 7. Verify API calls
    cy.wait('@revealPhoto').its('request.body').should('deep.equal', { match_id: '50' });
    cy.wait('@getOriginalPhoto');

    // 8. Verify UI updates
    // The button should disappear or change state
    cy.contains('Reveal Original').should('not.exist');
    cy.contains('Original').should('be.visible'); // The badge

    // The image should no longer have the blur class
    cy.get('img[alt="User photo"]').should('not.have.class', 'blur-md');
  });
});
