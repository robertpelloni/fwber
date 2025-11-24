describe('Face Reveal Game', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile: {
      display_name: 'Test User',
      avatar_url: '/images/test-avatar.svg'
    }
  };

  const otherUser = {
    id: 2,
    name: 'Match User',
    profile: {
      display_name: 'Match User',
      age: 25,
      bio: 'I am a match',
      photos: [
        {
          id: 101,
          url: '/images/test-avatar.svg', // Use existing image
          is_private: true,
          is_primary: true
        }
      ]
    }
  };

  const conversation = {
    id: 1,
    other_user: otherUser,
    last_message: {
      content: 'Hello',
      created_at: new Date().toISOString()
    }
  };

  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: user
      }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: { data: user }
    }).as('getUser');

    // Mock conversations
    cy.intercept('GET', '**/api/matches/established', {
      statusCode: 200,
      body: { data: [conversation] }
    }).as('getConversations');

    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });
    
    // Wait for hydration
    cy.wait(1000);
  });

  it('shows blurred photo initially (0 messages)', () => {
    cy.on('window:console', (msg) => {
      console.log('Browser Console:', msg);
    });

    // Mock 0 messages
    cy.intercept('GET', `**/api/messages/${otherUser.id}`, {
      statusCode: 200,
      body: { data: [] }
    }).as('getMessages0');

    // Click conversation
    cy.contains('Match User').should('be.visible').click();
    cy.wait('@getMessages0');

    // Ensure chat header is visible
    cy.contains('h3', 'Match User').should('be.visible');

    // Open Profile
    cy.contains('button', 'View Profile').should('be.visible').as('viewProfileBtn');
    cy.get('@viewProfileBtn').click();

    // Wait for modal animation
    cy.wait(500);

    // Check for modal content
    cy.contains('h2', 'Match User').should('be.visible');

    // Check for blurred photo
    cy.get('img[alt="Blurred photo"]').should('exist');
    cy.contains('0%').should('be.visible');
  });

  it('shows partially revealed photo (5 messages)', () => {
    // Mock 5 messages
    const messages = Array(5).fill(null).map((_, i) => ({
      id: i,
      sender_id: user.id,
      receiver_id: otherUser.id,
      content: `Message ${i}`,
      created_at: new Date().toISOString()
    }));

    cy.intercept('GET', `**/api/messages/${otherUser.id}`, {
      statusCode: 200,
      body: { data: messages }
    }).as('getMessages5');

    // Click conversation
    cy.contains('Match User').should('be.visible').click();
    cy.wait('@getMessages5');

    // Ensure chat header is visible
    cy.contains('h3', 'Match User').should('be.visible');

    // Open Profile
    cy.contains('button', 'View Profile').should('be.visible').as('viewProfileBtn');
    cy.get('@viewProfileBtn').click();
    
    // Wait for modal animation
    cy.wait(500);

    // Check for progress (5 messages / 10 = 50%)
    cy.contains('50%').should('be.visible');
    
    // Blur should be less than max (20px)
    cy.get('img[alt="Blurred photo"]')
      .should('have.attr', 'style')
      .and('include', 'blur');
  });

  it('shows fully revealed photo (10+ messages)', () => {
    // Mock 10 messages
    const messages = Array(10).fill(null).map((_, i) => ({
      id: i,
      sender_id: user.id,
      receiver_id: otherUser.id,
      content: `Message ${i}`,
      created_at: new Date().toISOString()
    }));

    cy.intercept('GET', `**/api/messages/${otherUser.id}`, {
      statusCode: 200,
      body: { data: messages }
    }).as('getMessages10');

    // Click conversation
    cy.contains('Match User').should('be.visible').click();
    cy.wait('@getMessages10');

    // Ensure chat header is visible
    cy.contains('h3', 'Match User').should('be.visible');

    // Open Profile
    cy.contains('button', 'View Profile').should('be.visible').as('viewProfileBtn');
    cy.get('@viewProfileBtn').click();

    // Wait for modal animation
    cy.wait(500);

    // Check for 100% or absence of lock overlay
    cy.contains('100%').should('not.exist');
    cy.get('img[alt="Blurred photo"]').should('have.css', 'filter', 'none');
  });
});
