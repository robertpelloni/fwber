describe('Proximity Chatroom Messaging', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
  };

  const chatroom = {
    id: 202,
    name: 'Joined Group',
    description: 'A group for testing',
    type: 'venue',
    radius_meters: 100,
    member_count: 10,
    is_public: true,
    is_networking: true,
    is_social: true,
    owner_id: 2,
    distance_meters: 50,
    tags: ['test'],
    last_activity_at: new Date().toISOString(),
  };

  const messages = [
    {
      id: 1,
      proximity_chatroom_id: 202,
      user_id: 2,
      content: 'Hello everyone!',
      message_type: 'text',
      created_at: new Date(Date.now() - 3600000).toISOString(),
      user: { id: 2, name: 'Other User' },
      reactions: [],
      is_pinned: false,
    },
    {
      id: 2,
      proximity_chatroom_id: 202,
      user_id: 1,
      content: 'Hi there!',
      message_type: 'text',
      created_at: new Date(Date.now() - 1800000).toISOString(),
      user: { id: 1, name: 'Test User' },
      reactions: [{ emoji: '👍', count: 1 }],
      is_pinned: false,
    }
  ];

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

    // Mock chatroom details
    cy.intercept('GET', '**/api/proximity-chatrooms/202*', {
      statusCode: 200,
      body: chatroom
    }).as('getChatroom');

    // Mock members (user is a member)
    cy.intercept('GET', '**/api/proximity-chatrooms/202/members*', {
      statusCode: 200,
      body: {
        data: [
          { id: 1, user_id: 1, name: 'Test User', role: 'member', user: { id: 1, name: 'Test User' } },
          { id: 2, user_id: 2, name: 'Other User', role: 'admin', user: { id: 2, name: 'Other User' } }
        ]
      }
    }).as('getMembers');

    // Mock messages
    cy.intercept('GET', '**/api/proximity-chatrooms/202/messages*', {
      statusCode: 200,
      body: {
        data: messages,
        total: 2,
        current_page: 1,
        last_page: 1
      }
    }).as('getMessages');

    // Mock send message
    cy.intercept('POST', '**/api/proximity-chatrooms/202/messages', {
      statusCode: 201,
      body: {
        id: 3,
        proximity_chatroom_id: 202,
        user_id: 1,
        content: 'This is a new message',
        message_type: 'text',
        created_at: new Date().toISOString(),
        user: { id: 1, name: 'Test User' },
        reactions: [],
        is_pinned: false,
      }
    }).as('sendMessage');
  });

  it('loads chatroom and sends a message', () => {
    // Set up authenticated state
    cy.visit('/proximity-chatrooms/202', {
      onBeforeLoad(win) {
        win.localStorage.setItem('auth_token', 'dev');
        // Mock geolocation
        cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((cb) => {
          return cb({ coords: { latitude: 40.7128, longitude: -74.0060 } });
        });
      }
    });

    // Verify chatroom details loaded
    cy.contains('Joined Group').should('be.visible');
    cy.contains('10 members').should('be.visible');

    // Verify existing messages
    cy.contains('Hello everyone!').should('be.visible');
    cy.contains('Hi there!').should('be.visible');

    // Send a new message
    cy.get('input[placeholder="Type your message..."]').type('This is a new message');
    cy.contains('button', 'Send').click();

    cy.wait('@sendMessage');

    // Verify new message appears (optimistic update or refetch)
    cy.contains('This is a new message').should('be.visible');
  });

  it('filters messages by tab', () => {
    cy.visit('/proximity-chatrooms/202', {
      onBeforeLoad(win) {
        win.localStorage.setItem('auth_token', 'dev');
        cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((cb) => {
          return cb({ coords: { latitude: 40.7128, longitude: -74.0060 } });
        });
      }
    });

    // Mock networking messages
    cy.intercept('GET', '**/api/proximity-chatrooms/202/messages?*type=networking*', {
      statusCode: 200,
      body: {
        data: [],
        total: 0
      }
    }).as('getNetworkingMessages');

    // Click Networking tab
    cy.contains('button', 'Networking').click();
    cy.wait('@getNetworkingMessages');
    
    // Should show empty state or filtered list
    cy.contains('No messages yet').should('be.visible');

    // Click All Messages tab
    cy.contains('button', 'All Messages').click();
    cy.contains('Hello everyone!').should('be.visible');
  });
});
