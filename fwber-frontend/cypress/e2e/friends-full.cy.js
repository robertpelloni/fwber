describe('Friends Feature', () => {
  const testUser = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    password: 'password123'
  };

  const friendUser = {
    id: 2,
    name: 'Friend User',
    email: 'friend@example.com',
    profile_photo_url: '/placeholder.jpg'
  };

  const requestUser = {
    id: 3,
    name: 'Request User',
    email: 'request@example.com',
    profile_photo_url: '/placeholder.jpg'
  };

  const searchUser = {
    id: 4,
    name: 'Search User',
    email: 'search@example.com',
    profile_photo_url: '/placeholder.jpg'
  };

  beforeEach(() => {
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'mock-jwt-token',
        user: testUser
      }
    }).as('login');

    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: testUser
    }).as('getUser');

    // Mock Friends API
    cy.intercept('GET', '**/api/friends', {
      statusCode: 200,
      body: [friendUser]
    }).as('getFriends');

    cy.intercept('GET', '**/api/friends/requests', {
      statusCode: 200,
      body: [{
        id: 101,
        user_id: requestUser.id,
        friend_id: testUser.id,
        status: 'pending',
        user: requestUser
      }]
    }).as('getFriendRequests');

    cy.intercept('GET', '**/api/friends/search?query=Search', {
      statusCode: 200,
      body: [searchUser]
    }).as('searchFriends');

    cy.intercept('POST', '**/api/friends/requests', {
      statusCode: 201,
      body: { status: 'pending' }
    }).as('sendRequest');

    cy.intercept('POST', '**/api/friends/requests/*', {
      statusCode: 200,
      body: { status: 'accepted' }
    }).as('respondRequest');
  });

  it('should list friends and requests', () => {
    // Login flow
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();
    cy.wait('@login');
    
    cy.visit('/friends');
    cy.wait('@getFriends');
    cy.wait('@getFriendRequests');

    // Check Friends List
    cy.contains('Your Friends').should('be.visible');
    cy.contains(friendUser.name).should('be.visible');

    // Check Friend Requests
    cy.contains('Friend Requests').should('be.visible');
    cy.contains(requestUser.name).should('be.visible');
  });

  it('should search and send friend request', () => {
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();
    cy.wait('@login');
    
    cy.visit('/friends');
    
    // Search
    cy.get('input[placeholder="Search by name or email"]').type('Search');
    cy.contains('button', 'Search').click();
    cy.wait('@searchFriends');
    
    cy.contains(searchUser.name).should('be.visible');
    
    // Send Request
    cy.contains(searchUser.name).parent().find('button').click();
    // Note: The button text might be "Add Friend" or icon.
    // I need to check UserSearch component.
    // Assuming it has a button.
    cy.wait('@sendRequest');
    cy.on('window:alert', (str) => {
      expect(str).to.equal('Friend request sent!');
    });
  });

  it('should accept friend request', () => {
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();
    cy.wait('@login');
    
    cy.visit('/friends');
    cy.wait('@getFriends');
    cy.wait('@getFriendRequests');
    
    cy.contains('Friend Requests').should('be.visible');
    cy.contains(requestUser.name).should('be.visible');
    
    cy.contains('button', 'Accept').click();
    cy.wait('@respondRequest').its('request.body').should('deep.equal', { status: 'accepted' });
  });
});
