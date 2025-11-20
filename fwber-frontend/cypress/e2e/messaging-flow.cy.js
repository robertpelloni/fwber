describe('Messaging Flow', () => {
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
      avatarUrl: 'https://via.placeholder.com/150',
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

    // Mock matches feed
    cy.intercept('GET', '**/api/matches*', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 2,
            compatibility_score: 0.9,
            profile: {
              display_name: 'Future Partner',
              age: 25,
              bio: 'I like hiking',
              location: { city: 'New York', state: 'NY' },
              photos: [{ url: 'https://via.placeholder.com/150' }]
            }
          }
        ]
      }
    }).as('getMatches');

    // Mock match action (Mutual Match)
    cy.intercept('POST', '**/api/matches/action', {
      statusCode: 200,
      body: {
        action: 'like',
        match_created: true,
        message: "It's a match!"
      }
    }).as('mutualMatch');

    // Mock conversations list
    cy.intercept('GET', '**/api/conversations', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 101,
            user1_id: 1,
            user2_id: 2,
            updated_at: new Date().toISOString(),
            last_message: null,
            other_user: {
              id: 2,
              email: 'partner@example.com',
              profile: {
                display_name: 'Future Partner',
                age: 25,
                photos: [{ url: 'https://via.placeholder.com/150' }]
              }
            }
          }
        ]
      }
    }).as('getConversations');

    // Mock messages for conversation 101
    cy.intercept('GET', '**/api/conversations/101/messages', {
      statusCode: 200,
      body: {
        data: []
      }
    }).as('getMessages');

    // Mock sending message
    cy.intercept('POST', '**/api/conversations/101/messages', {
      statusCode: 201,
      body: {
        data: {
          id: 1,
          conversation_id: 101,
          sender_id: 1,
          content: 'Hello!',
          created_at: new Date().toISOString()
        }
      }
    }).as('sendMessage');
  });

  it('allows matching and then sending a message', () => {
    // 1. Visit Matches
    cy.visit('/matches', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // 2. Like the user to trigger match
    cy.contains('Future Partner', { timeout: 10000 }).should('be.visible');
    cy.contains('button', 'Like').click({ force: true });
    cy.wait('@mutualMatch');

    // 3. Verify Modal and Click "Send a Message"
    cy.contains("It's a Match!").should('be.visible');
    cy.contains('button', 'Send a Message').click();

    // 4. Verify Redirect to Messages
    cy.url().should('include', '/messages');
    cy.wait('@getConversations');

    // 5. Verify Conversation List contains the match
    cy.contains('Future Partner').should('be.visible');
    
    // 6. Click the conversation to open chat
    cy.contains('Future Partner').click();
    cy.wait('@getMessages');

    // 7. Send a message
    cy.get('input[placeholder="Type a message..."]').type('Hello!{enter}');
    cy.wait('@sendMessage');

    // 8. Verify message appears
    cy.contains('Hello!').should('be.visible');
  });
});
