describe('Proximity Chatroom Creation', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
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

    // Mock nearby chatrooms (empty initially)
    cy.intercept('GET', '**/api/proximity-chatrooms/nearby*', {
      statusCode: 200,
      body: {
        chatrooms: [],
        user_location: { latitude: 40.7128, longitude: -74.0060 },
        search_radius: 1000
      }
    }).as('getNearby');

    // Mock create chatroom
    cy.intercept('POST', '**/api/proximity-chatrooms', {
      statusCode: 201,
      body: {
        id: 301,
        name: 'New Test Room',
        description: 'Created via test',
        type: 'social',
        latitude: 40.7128,
        longitude: -74.0060,
        radius_meters: 500,
        is_public: true,
        created_by: 1,
        member_count: 1,
        distance_meters: 0
      }
    }).as('createChatroom');

    // Mock get new chatroom details (for redirection)
    cy.intercept('GET', '**/api/proximity-chatrooms/301*', {
      statusCode: 200,
      body: {
        id: 301,
        name: 'New Test Room',
        description: 'Created via test',
        type: 'social',
        latitude: 40.7128,
        longitude: -74.0060,
        radius_meters: 500,
        is_public: true,
        created_by: 1,
        member_count: 1,
        distance_meters: 0,
        is_member: true // Creator is auto-joined
      }
    }).as('getNewChatroom');
    
    // Mock members for the new room
    cy.intercept('GET', '**/api/proximity-chatrooms/301/members*', {
        statusCode: 200,
        body: { data: [{ id: 1, user_id: 1, name: 'Test User', role: 'admin' }] }
    }).as('getNewMembers');

    // Mock messages for the new room
    cy.intercept('GET', '**/api/proximity-chatrooms/301/messages*', {
        statusCode: 200,
        body: { data: [], total: 0 }
    }).as('getNewMessages');
  });

  it('creates a new chatroom successfully', () => {
    cy.visit('/proximity-chatrooms', {
      onBeforeLoad(win) {
        win.localStorage.setItem('auth_token', 'dev');
        cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((cb) => {
          return cb({ coords: { latitude: 40.7128, longitude: -74.0060 } });
        });
      }
    });

    // Open modal
    cy.contains('button', 'Create Chatroom').click();
    cy.contains('Create Proximity Chatroom').should('be.visible');

    // Fill form
    cy.get('input[name="name"]').type('New Test Room');
    cy.get('textarea[name="description"]').type('Created via test');
    cy.get('select[name="type"]').select('social');
    cy.get('select[name="radius_meters"]').select('500m');

    // Submit
    cy.get('form').within(() => {
      cy.contains('button', 'Create Chatroom').click();
    });

    cy.wait('@createChatroom');

    // Verify redirection
    cy.url().should('include', '/proximity-chatrooms/301');
    cy.contains('New Test Room').should('be.visible');
  });
});
