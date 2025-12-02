describe('Media Messaging Flow', () => {
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

    // Mock conversations list
    cy.intercept('GET', '**/api/matches/established', {
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
              name: 'Future Partner',
              profile: {
                display_name: 'Future Partner',
                age: 25,
                bio: 'I like hiking',
                location_description: 'New York, NY',
                avatar_url: '/images/test-avatar.svg'
              }
            }
          }
        ]
      }
    }).as('getConversations');

    // Mock messages for user 2
    cy.intercept('GET', '**/api/messages/2', {
      statusCode: 200,
      body: {
        messages: []
      }
    }).as('getMessages');

    // Mock mark as read
    cy.intercept('POST', '**/api/messages/mark-all-read/2', {
      statusCode: 200,
      body: { success: true }
    }).as('markRead');

    // Mock sending media message
    cy.intercept('POST', '**/api/messages', {
      statusCode: 201,
      body: {
        message: {
          id: 2,
          conversation_id: 101,
          sender_id: 1,
          receiver_id: 2,
          content: '',
          message_type: 'image',
          media_url: '/images/test-image.jpg',
          created_at: new Date().toISOString()
        }
      }
    }).as('sendMessage');
  });

  it('allows sending a media message', () => {
    // 1. Visit Messages
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    cy.wait('@getConversations');

    // Verify conversation is visible
    cy.contains('Future Partner').should('be.visible').click();
    cy.wait('@getMessages');

    // 3. Attach a file
    cy.get('input[type="file"]').should('exist').selectFile({
        contents: Cypress.Buffer.from('fake image content'),
        fileName: 'test-image.jpg',
        mimeType: 'image/jpeg',
    }, { force: true });

    // 4. Send the message
    cy.contains('button', 'Send').click();
    cy.wait('@sendMessage');

    // 5. Verify message appears (mocked response)
    cy.get('img[alt="Attachment"]').should('be.visible');
  });
});
