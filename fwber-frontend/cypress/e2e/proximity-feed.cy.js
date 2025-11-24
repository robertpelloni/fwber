describe('Proximity Feed (Local Pulse)', () => {
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
      body: { token: 'fake-token', user }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: { data: user }
    }).as('getUser');

    // Mock proximity feed (artifacts)
    cy.intercept('GET', '**/api/proximity/feed*', {
      statusCode: 200,
      body: {
        artifacts: [
          {
            id: 101,
            user_id: 2,
            type: 'board_post',
            content: 'Anyone want to play frisbee?',
            lat: 40.7128,
            lng: -74.0060,
            created_at: new Date().toISOString(),
            expires_at: new Date(Date.now() + 86400000).toISOString(),
            distance: 50
          }
        ]
      }
    }).as('getFeed');

    // Mock nearby chatrooms
    cy.intercept('GET', '**/api/proximity-chatrooms/nearby*', {
      statusCode: 200,
      body: {
        chatrooms: [
          {
            id: 201,
            name: 'Central Park Meetup',
            type: 'event',
            distance_meters: 100,
            current_members: 5,
            max_members: 20,
            lat: 40.7128,
            lng: -74.0060,
            is_member: false
          },
          {
            id: 202,
            name: 'Joined Group',
            type: 'venue',
            distance_meters: 50,
            current_members: 10,
            max_members: 50,
            lat: 40.7128,
            lng: -74.0060,
            is_member: true
          }
        ]
      }
    }).as('getChatrooms');

    // Mock join chatroom
    cy.intercept('POST', '**/api/proximity-chatrooms/201/join', {
      statusCode: 200,
      body: { message: 'Successfully joined proximity chatroom' }
    }).as('joinChatroom');

    // Mock leave chatroom
    cy.intercept('POST', '**/api/proximity-chatrooms/202/leave', {
      statusCode: 200,
      body: { message: 'Successfully left proximity chatroom' }
    }).as('leaveChatroom');

    // Mock create artifact
    cy.intercept('POST', '**/api/proximity/artifacts', {
      statusCode: 201,
      body: {
        data: {
          id: 102,
          user_id: 1,
          type: 'board_post',
          content: 'New post content',
          lat: 40.7128,
          lng: -74.0060,
          created_at: new Date().toISOString(),
          expires_at: new Date(Date.now() + 86400000).toISOString(),
          distance: 0
        }
      }
    }).as('createArtifact');
  });

  it('shows loading state or error if unauthenticated', () => {
    cy.visit('/pulse');
    cy.url().then(url => cy.log('Current URL:', url));
    cy.get('body').invoke('text').then(text => cy.log('Body text:', text.substring(0, 200)));

    // Depending on environment, it might show loading or immediate geo error
    cy.get('body').then(($body) => {
      if ($body.find(':contains("Finding local pulse...")').length > 0) {
        cy.contains('Finding local pulse...').should('be.visible');
      } else {
        // If we are on home page, this will fail, which is what we want to know
        if ($body.find('h1:contains("Find Your Perfect Match")').length > 0) {
            throw new Error('Redirected to Home Page!');
        }
        cy.contains(/Geolocation is not supported|Could not get your location/).should('be.visible');
      }
    });
  });

  it('loads the feed when authenticated', () => {
    // Catch unhandled exceptions to debug
    cy.on('uncaught:exception', (err, runnable) => {
      console.error('Uncaught exception:', err);
      return false; // prevent test failure
    });

    // Set up the authenticated state with onBeforeLoad
    cy.visit('/pulse', {
      onBeforeLoad(win) {
        // Use the AuthProvider's built-in dev bypass
        win.localStorage.setItem('auth_token', 'dev');
        // And our component's geo bypass
        win.localStorage.setItem('mock_geo', 'true');
      }
    });

    // Wait for potential redirect or load
    cy.wait(1000);

    // Check that we don't have errors
    cy.contains('Geolocation is not supported').should('not.exist');
    cy.contains('Could not get your location').should('not.exist');
    cy.contains('Failed to load local pulse').should('not.exist');

    // Ensure the page content is loaded
    cy.get('h1', { timeout: 10000 }).should('contain', 'Local Pulse');

    // Wait for feed request
    cy.wait('@getFeed');

    // Check for existing artifact
    cy.contains('Anyone want to play frisbee?', { timeout: 10000 }).should('be.visible');
    // Since we calculate distance locally and coordinates are identical in mock, it should be 0m
    cy.contains('0m away').should('be.visible');

    // Post new artifact
    cy.get('textarea[placeholder*="What\'s happening"]').type('New post content');
    cy.contains('button', 'Post').click();

    cy.wait('@createArtifact').its('request.body').should('include', {
      content: 'New post content',
      type: 'board_post'
    });

    // Check for chatroom and join
    cy.contains('Central Park Meetup').should('be.visible');
    cy.contains('button', 'Join Room')
      .should('have.class', 'bg-purple-600')
      .click();
    
    // Verify navigation
    cy.url().should('include', '/proximity-chatrooms/201');

    // Go back
    cy.go('back');

    // Check for joined room
    cy.contains('Joined Group').should('be.visible');
    
    // Test Leave functionality
    cy.contains('Joined Group').parent().within(() => {
      cy.get('button[title="Leave Room"]').click();
    });
    
    // Handle confirm dialog automatically (Cypress defaults to auto-accept, but good to be explicit if needed)
    // Note: Cypress auto-accepts confirms by default.

    cy.wait('@leaveChatroom');

    // Verify UI update (optimistic)
    cy.contains('Joined Group').parent().within(() => {
      cy.contains('button', 'Join Room').should('have.class', 'bg-purple-600');
      cy.get('button[title="Leave Room"]').should('not.exist');
    });

    // Re-verify Enter Room navigation (now Join Room)
    cy.contains('Joined Group').parent().contains('button', 'Join Room').click();

    // Verify navigation
    cy.url().should('include', '/proximity-chatrooms/202');
  });
});
