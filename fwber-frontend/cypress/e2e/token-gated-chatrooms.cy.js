describe('Token-Gated Chatrooms', () => {
  beforeEach(() => {
    // Login as a user with tokens
    cy.intercept('GET', '/api/user', {
      statusCode: 200,
      body: {
        id: 1,
        name: 'Rich User',
        email: 'rich@example.com',
        wallet: {
          balance: 1000,
          address: 'SOL_WALLET_ADDRESS'
        }
      }
    }).as('getUser');

    // Mock chatroom list
    cy.intercept('GET', '/api/chatrooms*', {
      statusCode: 200,
      body: {
        data: [],
        current_page: 1,
        last_page: 1,
        total: 0
      }
    }).as('getChatrooms');
  });

  it('should allow creating a token-gated chatroom', () => {
    cy.visit('/chatrooms/create');

    // Fill out form
    cy.get('input[name="name"]').type('Exclusive VIP Lounge');
    cy.get('textarea[name="description"]').type('Only for the elite.');
    cy.get('select[name="type"]').select('interest');
    cy.get('input[name="category"]').type('Luxury');
    
    // Set entry fee
    cy.get('input[name="token_entry_fee"]').type('50');

    // Mock creation response
    cy.intercept('POST', '/api/chatrooms', {
      statusCode: 201,
      body: {
        id: 99,
        name: 'Exclusive VIP Lounge',
        token_entry_fee: 50,
        type: 'interest'
      }
    }).as('createChatroom');

    // Submit
    cy.contains('button', 'Create Chatroom').click();

    // Verify redirection
    cy.url().should('include', '/chatrooms/99');
  });

  it('should show pay-to-join overlay for non-members', () => {
    // Mock chatroom details (preview mode)
    cy.intercept('GET', '/api/chatrooms/99', {
      statusCode: 200,
      body: {
        chatroom: {
          id: 99,
          name: 'Exclusive VIP Lounge',
          token_entry_fee: 50,
          type: 'interest',
          member_count: 5
        },
        messages: { data: [] },
        is_member: false,
        preview_mode: true
      }
    }).as('getChatroom');

    cy.visit('/chatrooms/99');

    // Verify overlay
    cy.contains('Join to Chat').should('be.visible');
    cy.contains('This chatroom requires an entry fee of 50 tokens').should('be.visible');
    cy.contains('button', 'Pay & Join').should('be.visible');
  });

  it('should process payment and join successfully', () => {
    // Mock chatroom details (preview mode)
    cy.intercept('GET', '/api/chatrooms/99', {
      statusCode: 200,
      body: {
        chatroom: {
          id: 99,
          name: 'Exclusive VIP Lounge',
          token_entry_fee: 50,
          type: 'interest'
        },
        messages: { data: [] },
        is_member: false,
        preview_mode: true
      }
    }).as('getChatroom');

    // Mock join request
    cy.intercept('POST', '/api/chatrooms/99/join', {
      statusCode: 200,
      body: { message: 'Successfully joined chatroom' }
    }).as('joinChatroom');

    // Mock chatroom details (member mode) after join
    cy.intercept('GET', '/api/chatrooms/99', (req) => {
      req.reply({
        statusCode: 200,
        body: {
          chatroom: {
            id: 99,
            name: 'Exclusive VIP Lounge',
            token_entry_fee: 50,
            type: 'interest'
          },
          messages: { data: [] },
          is_member: true,
          preview_mode: false
        }
      });
    }).as('getChatroomMember');

    cy.visit('/chatrooms/99');

    // Click Join
    cy.contains('button', 'Pay & Join').click();

    // Verify API call
    cy.wait('@joinChatroom');

    // Verify overlay is gone (implied by being able to see chat interface, but we can check for absence)
    cy.contains('Join to Chat').should('not.exist');
  });
});
