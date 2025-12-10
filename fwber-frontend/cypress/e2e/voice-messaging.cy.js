describe('Voice Messaging Flow', () => {
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

    // Mock sending audio message
    cy.intercept('POST', '**/api/messages', {
      statusCode: 201,
      body: {
        message: {
          id: 3,
          conversation_id: 101,
          sender_id: 1,
          receiver_id: 2,
          content: '',
          message_type: 'audio',
          media_url: '/audio/test-voice.webm',
          created_at: new Date().toISOString()
        }
      }
    }).as('sendAudioMessage');
  });

  it('allows recording and sending a voice message', () => {
    // 1. Visit Messages with mocked MediaRecorder
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));

        // Mock getUserMedia
        cy.stub(win.navigator.mediaDevices, 'getUserMedia').resolves({
          getTracks: () => [{ stop: () => {} }]
        });

        // Mock MediaRecorder
        win.MediaRecorder = class MockMediaRecorder {
          constructor(stream) {
            this.stream = stream;
            this.state = 'inactive';
            this.ondataavailable = null;
            this.onstop = null;
          }
          start() {
            this.state = 'recording';
          }
          stop() {
            this.state = 'inactive';
            if (this.onstop) this.onstop();
            if (this.ondataavailable) {
              // Simulate data available with a fake blob
              const blob = new Blob(['fake audio data'], { type: 'audio/webm' });
              this.ondataavailable({ data: blob });
            }
          }
        };
      }
    });

    cy.wait('@getConversations');

    // 2. Select conversation
    cy.contains('Future Partner').should('be.visible').click();
    cy.wait('@getMessages');

    // 3. Click Record button (Mic icon)
    cy.get('button[title="Record Voice Message"]').click();

    // 4. Verify recording state UI
    cy.contains('Recording 0:0').should('be.visible');
    
    // Wait a second to simulate recording time
    cy.wait(1000);

    // 5. Stop recording
    cy.get('button[title="Stop"]').click();

    // 6. Verify review state UI (Play button, Delete, Send)
    cy.get('audio').should('exist');
    cy.get('button[title="Send"]').should('be.visible');

    // 7. Send the recording
    cy.get('button[title="Send"]').click();
    cy.wait('@sendAudioMessage');

    // 8. Verify message appears (mocked response)
    cy.get('audio').should('have.length', 1); // One in the message list
  });
});
