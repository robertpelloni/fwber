describe('Critical Flow: Match -> Chat', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile_complete: true,
    profile: {
      displayName: 'Test User',
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  const potentialMatch = {
    id: 2,
    name: 'Future Partner',
    bio: 'I like hiking',
    age: 25,
    locationDescription: 'New York, NY',
    avatarUrl: '/images/test-avatar.svg',
    compatibilityScore: 0.9,
    distance: 5
  };

  beforeEach(() => {
    // 1. Setup global mocks/stubs
    cy.on('uncaught:exception', (err) => {
      // Ignore specific known errors
      if (err.message.includes('styles.css') || err.message.includes('SolanaProvider')) {
        return false;
      }
      return true;
    });

    // Mock EventSource
    cy.window().then((win) => {
      win.EventSource = class MockEventSource {
        constructor(url) {
          this.url = url;
          this.readyState = 1; // OPEN
        }
        close() {}
        addEventListener() {}
        removeEventListener() {}
      };
    });

    // 2. Mock API endpoints
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'fake-token', user }
    }).as('login');

    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: { data: user }
    }).as('getUser');

    // Matches
    cy.intercept('GET', '**/api/matches*', {
      statusCode: 200,
      body: {
        data: {
          matches: [potentialMatch]
        }
      }
    }).as('getMatches');

    // Swipe/Like Action -> Result is a Match
    cy.intercept('POST', '**/api/matches/action', {
      statusCode: 200,
      body: {
        data: {
          action: 'like',
          is_match: true,
          message: "It's a match!"
        }
      }
    }).as('likeAction');

    // Conversations List
    cy.intercept('GET', '**/api/matches/established', {
      statusCode: 200,
      body: {
        data: [{
          id: 101,
          user1_id: 1,
          user2_id: 2,
          updated_at: new Date().toISOString(),
          last_message: null,
          other_user: {
            id: 2,
            name: 'Future Partner',
            profile: {
              display_name: 'Future Partner',
              avatar_url: '/images/test-avatar.svg'
            }
          }
        }]
      }
    }).as('getConversations');

    // Messages for specific conversation
    cy.intercept('GET', '**/api/messages/2', {
      statusCode: 200,
      body: { messages: [] }
    }).as('getMessages');

    // Send Message
    cy.intercept('POST', '**/api/websocket/message', {
      statusCode: 201,
      body: { success: true }
    }).as('sendMessage');

    // WebSocket Token
    cy.intercept('GET', '**/api/websocket/token', {
      statusCode: 200,
      body: { token: 'mock-token', hub_url: 'http://localhost:3000' }
    }).as('getWsToken');
  });

  it('completes the flow: View Match -> Like -> Connect -> Send Message', () => {
    // --- Step 1: Matching ---
    cy.visit('/matches', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // Ensure we are not redirected to login
    cy.url().should('include', '/matches');

    // Wait for the API call
    cy.wait('@getMatches');

    // Check for loading state removal
    cy.contains('Loading matches...').should('not.exist');

    // Debug: Check if "No more matches" is shown
    cy.get('body').then($body => {
      if ($body.text().includes('No more matches')) {
        cy.log('TEST FAILURE: Matches list is empty');
      }
    });

    // Verify card is present
    cy.contains('Future Partner', { timeout: 30000 }).should('be.visible');
    cy.contains('I like hiking').should('be.visible');

    // Action: Like
    // Look for the green heart button wrapper or button itself
    cy.get('.text-green-500').parent().click({ force: true });

    // Expect Match Toast/Notification
    cy.wait('@likeAction');
    cy.contains("matched with Future Partner", { timeout: 10000 }).should('be.visible');

    // --- Step 2: Messaging ---
    // Navigate to messages (simulating user clicking nav or toast)
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    cy.wait('@getConversations');
    
    // Verify conversation exists in list
    cy.contains('Future Partner').should('be.visible').click();

    // Verify chat area opens
    cy.wait('@getMessages');
    cy.get('input[placeholder*="Type a message"]').should('exist');

    // Action: Send Message
    const messageText = 'Hello World from E2E';
    cy.get('input[placeholder*="Type a message"]').type(`${messageText}{enter}`);

    cy.wait('@sendMessage');

    // Optimistically, the UI should show the message (or via WS mock if implemented, 
    // but usually optimistic UI handles it immediately)
    // If the app relies strictly on WS echo, we might need to mock the incoming message like in messaging-flow.
    // For now, let's assume optimistic update or check input cleared.
    cy.get('input[placeholder*="Type a message"]').should('have.value', '');
    
    // In a real scenario, we'd emit the WS event here to see it appear.
    // For this critical path test, verifying the send API call was made is a good checkpoint.
  });
});
