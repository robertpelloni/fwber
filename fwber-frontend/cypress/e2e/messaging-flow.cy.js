describe('Messaging Flow', () => {
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

  const setupMockEventSource = (win) => {
    win.mockEventSources = [];
    
    win.EventSource = class MockEventSource {
      constructor(url) {
        this.url = url;
        this.readyState = 0; // CONNECTING
        this.listeners = {};
        
        win.mockEventSources.push(this);

        setTimeout(() => {
          this.readyState = 1; // OPEN
          if (this.onopen) this.onopen({ type: 'open' });
        }, 10);
      }
      close() {
        this.readyState = 2; // CLOSED
      }
      addEventListener(type, listener) {
        if (!this.listeners[type]) this.listeners[type] = [];
        this.listeners[type].push(listener);
      }
      removeEventListener(type, listener) {
        if (this.listeners[type]) {
          this.listeners[type] = this.listeners[type].filter(l => l !== listener);
        }
      }
      // Helper to simulate incoming events
      emit(type, data) {
        const event = { data: JSON.stringify(data) };
        if (this.listeners[type]) {
          this.listeners[type].forEach(l => l(event));
        }
        if (this.onmessage && type === 'message') {
          this.onmessage(event);
        }
      }
    };
  };

  beforeEach(() => {
    // Inject MockEventSource
    cy.window().then((win) => {
      setupMockEventSource(win);
    });

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

    // Mock matches feed (Flat structure as per API)
    cy.intercept('GET', '**/api/matches*', {
      statusCode: 200,
      body: {
        data: {
          matches: [
            {
              id: 2,
              name: 'Future Partner',
              bio: 'I like hiking',
              age: 25,
              locationDescription: 'New York, NY',
              avatarUrl: '/images/test-avatar.svg',
              compatibilityScore: 0.9,
              distance: 5
            }
          ]
        }
      }
    }).as('getMatches');

    // Mock match action (Mutual Match)
    cy.intercept('POST', '**/api/matches/action', {
      statusCode: 200,
      body: {
        data: {
          action: 'like',
          is_match: true,
          message: 'It\'s a match!'
        }
      }
    }).as('mutualMatch');

    // Mock conversations list (matches/established)
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

    // Mock sending message via WebSocket endpoint
    cy.intercept('POST', '**/api/websocket/message', {
      statusCode: 201,
      body: {
        success: true
      }
    }).as('sendMessage');

    // Mock WebSocket token endpoint to avoid 404s
    cy.intercept('GET', '**/api/websocket/token', {
      statusCode: 200,
      body: {
        token: 'mock-mercure-token',
        hub_url: 'http://localhost:3000/.well-known/mercure'
      }
    }).as('getWebsocketToken');
  });

  it('allows matching and then sending a message', () => {
    // 1. Visit Matches
    cy.visit('/matches', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        setupMockEventSource(win);
      }
    });

    // 2. Like the user to trigger match
    cy.contains('Future Partner', { timeout: 10000 }).should('be.visible');
    cy.get('button.text-green-500').click({ force: true });
    cy.wait('@mutualMatch');

    // 3. Verify Toast and Navigate to Messages
    cy.contains('You matched with Future Partner!').should('be.visible');
    
    // Manually navigate to messages since there is no modal button
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        setupMockEventSource(win);
      }
    });

    // 4. Verify Redirect to Messages
    cy.url().should('include', '/messages');
    
    cy.wait('@getConversations');

    // 5. Verify Conversation List contains the match
    cy.contains('Future Partner').should('be.visible');
    
    // 6. Click the conversation to open chat
    cy.contains('Future Partner').click();
    cy.wait('@getMessages');

    // 7. Send a message
    // Input placeholder might vary depending on encryption status
    cy.get('input[type=\'text\']').should('be.visible').type('Hello!{enter}');
    cy.wait('@sendMessage');

    // Simulate incoming message via WebSocket
    cy.window().then((win) => {
      const es = win.mockEventSources[win.mockEventSources.length - 1];
      es.emit('chat_message', {
        id: 'msg-1',
        type: 'chat_message',
        from_user_id: '1', // Me
        to_user_id: '2', // Partner
        content: 'Hello!',
        timestamp: new Date().toISOString(),
        message_type: 'text'
      });
    });

    // 8. Verify message appears
    cy.contains('Hello!').should('be.visible');
  });
});
