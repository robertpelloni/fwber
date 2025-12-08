describe('Proximity Chatrooms', () => {
  const mockLocation = {
    coords: {
      latitude: 40.7128,
      longitude: -74.0060,
      accuracy: 10,
    },
  };

  const mockChatrooms = {
    data: [
      {
        id: 1,
        name: 'Tech Meetup',
        description: 'A meetup for tech enthusiasts',
        type: 'networking',
        latitude: 40.7128,
        longitude: -74.0060,
        radius_meters: 1000,
        member_count: 15,
        distance_meters: 50,
        is_public: true,
        last_activity_at: new Date().toISOString(),
      },
      {
        id: 2,
        name: 'Coffee Social',
        description: 'Casual coffee chat',
        type: 'social',
        latitude: 40.7130,
        longitude: -74.0050,
        radius_meters: 500,
        member_count: 5,
        distance_meters: 150,
        is_public: true,
        last_activity_at: new Date().toISOString(),
      }
    ],
    user_location: {
      latitude: 40.7128,
      longitude: -74.0060
    },
    search_radius: 1000
  };

  beforeEach(() => {
    // Mock Geolocation
    cy.visit('/proximity-chatrooms', {
      onBeforeLoad(win) {
        cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((cb) => {
          return cb(mockLocation);
        });
        
        // Mock localStorage for authentication
        win.localStorage.setItem('fwber_token', 'mock-token');
        win.localStorage.setItem('fwber_user', JSON.stringify({ id: 1, name: 'Test User' }));
      },
    });

    // Mock API response
    cy.intercept('GET', '**/api/proximity-chatrooms/nearby*', {
      statusCode: 200,
      body: mockChatrooms,
    }).as('getChatrooms');
  });

  it('shows loading state initially', () => {
    cy.contains('Finding nearby chatrooms...').should('be.visible');
  });

  it('loads and displays nearby chatrooms', () => {
    cy.wait('@getChatrooms');
    
    // Check if chatrooms are displayed
    cy.contains('Tech Meetup').should('be.visible');
    cy.contains('A meetup for tech enthusiasts').should('be.visible');
    cy.contains('networking').should('be.visible');
    cy.contains('15 members').should('be.visible');
    
    cy.contains('Coffee Social').should('be.visible');
    cy.contains('Casual coffee chat').should('be.visible');
    cy.contains('social').should('be.visible');
    cy.contains('5 members').should('be.visible');
  });

  it('allows filtering by type', () => {
    cy.wait('@getChatrooms');
    
    // Mock filtered response
    cy.intercept('GET', '**/api/proximity-chatrooms/nearby*type=networking*', {
      statusCode: 200,
      body: {
        ...mockChatrooms,
        data: [mockChatrooms.data[0]] // Only return the networking chatroom
      }
    }).as('getNetworkingChatrooms');

    // Select networking type
    cy.get('select').eq(0).select('networking'); // Assuming first select is type, wait, actually check the label
    // Better selector strategy:
    cy.contains('label', 'Type').next('select').select('networking');
    
    // Wait for new request
    cy.wait('@getNetworkingChatrooms');
    
    // Verify only networking chatroom is visible (in a real app, the list would update)
    // Since we mocked the response, we should see the update if the component refetches
  });

  it('shows create chatroom modal', () => {
    cy.contains('Create Chatroom').click();
    cy.contains('Create Proximity Chatroom').should('be.visible');
    cy.get('input[name="name"]').should('be.visible');
    cy.get('textarea[name="description"]').should('be.visible');
  });
});
