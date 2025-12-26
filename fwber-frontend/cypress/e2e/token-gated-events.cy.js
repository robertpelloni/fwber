describe('Token-Gated Events', () => {
  beforeEach(() => {
    // Login as a user with tokens
    const user = {
      id: 1,
      name: 'Rich User',
      email: 'rich@example.com',
      wallet: {
        balance: 1000,
        address: 'SOL_WALLET_ADDRESS'
      }
    };

    cy.intercept('GET', '/api/user', {
      statusCode: 200,
      body: user
    }).as('getUser');
  });

  it('should require payment for token-gated event', () => {
    // Mock event details
    cy.intercept('GET', '/api/events/101', {
      statusCode: 200,
      body: {
        id: 101,
        title: 'Exclusive Party',
        description: 'VIP only',
        starts_at: new Date().toISOString(),
        ends_at: new Date().toISOString(),
        location_name: 'Secret Location',
        price: 0,
        token_cost: 100,
        status: 'upcoming',
        attendees_count: 10,
        attendees: []
      }
    }).as('getEvent');

    // Mock wallet
    cy.intercept('GET', '/api/wallet', {
      statusCode: 200,
      body: { 
        balance: '1000.00',
        transactions: [],
        referral_code: 'REF123',
        wallet_address: 'SOL_ADDR',
        referral_count: 0,
        golden_tickets_remaining: 0
      }
    }).as('getWallet');

    // Mock RSVP/Purchase
    cy.intercept('POST', '/api/events/101/rsvp', {
      statusCode: 200,
      body: { status: 'attending', paid: true }
    }).as('rsvpEvent');

    cy.visit('/events/101', {
      onBeforeLoad(win) {
        win.localStorage.setItem('fwber_token', 'mock-token');
        win.localStorage.setItem('fwber_user', JSON.stringify({
          id: 1,
          name: 'Rich User',
          email: 'rich@example.com',
          wallet: {
            balance: 1000,
            address: 'SOL_WALLET_ADDRESS'
          }
        }));
      },
    });

    // Click Attend
    cy.contains('button', 'Attend').click();

    // Verify Modal Opens
    cy.contains('Purchase Ticket').should('be.visible');
    cy.contains('100 Tokens').should('be.visible');

    // Click Pay
    cy.contains('button', 'Pay 100 Tokens').click();

    // Verify API call
    cy.wait('@rsvpEvent').its('request.body').should('deep.include', {
      status: 'attending',
      payment_method: 'token'
    });

    // Verify Modal Closes
    cy.contains('Purchase Ticket').should('not.exist');
  });
});
