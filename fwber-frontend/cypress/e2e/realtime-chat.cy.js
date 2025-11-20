describe('Real-time Chat', () => {
  beforeEach(() => {
    // Mock session
    cy.intercept('GET', '/api/auth/session', {
      statusCode: 200,
      body: {
        user: { name: 'Test User', email: 'test@example.com', id: 1 },
        expires: new Date(Date.now() + 86400 * 1000).toISOString()
      }
    }).as('session');

    // Mock messages list
    cy.intercept('GET', '/api/messages/2', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 101,
            sender_id: 2,
            content: 'Hello there!',
            created_at: new Date(Date.now() - 60000).toISOString(),
            is_read: true
          }
        ]
      }
    }).as('getMessages');

    // Mock sending message
    cy.intercept('POST', '/api/messages', {
      statusCode: 201,
      body: {
        data: {
          id: 102,
          sender_id: 1,
          receiver_id: 2,
          content: 'General Kenobi!',
          created_at: new Date().toISOString(),
          is_read: false
        }
      }
    }).as('sendMessage');
    
    // Mock user profile for header
    cy.intercept('GET', '/api/user', {
        statusCode: 200,
        body: {
          data: { id: 1, name: 'Test User', profile_complete: true }
        }
      }).as('getUser');
  });

  it('loads conversation and sends a message', () => {
    cy.visit('/messages/2');
    
    // Verify existing messages
    cy.wait('@getMessages');
    cy.contains('Hello there!').should('be.visible');

    // Type and send new message
    cy.get('input[placeholder="Type a message..."]').type('General Kenobi!{enter}');

    // Verify API call
    cy.wait('@sendMessage').its('request.body').should('include', {
      content: 'General Kenobi!',
      receiver_id: '2'
    });

    // Verify message appears in UI (optimistic update or from response)
    cy.contains('General Kenobi!').should('be.visible');
  });
});
