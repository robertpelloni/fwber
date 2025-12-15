describe('Gifting Flow', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    token_balance: 100,
    profile: {
      displayName: 'Test User',
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  const partner = {
    id: 2,
    name: 'Recipient User',
    profile: {
      displayName: 'Recipient User',
      avatarUrl: '/images/test-avatar.svg'
    }
  };

  const gifts = [
    { id: 1, name: 'Rose', cost: 10, icon_url: '/images/gifts/rose.svg' },
    { id: 2, name: 'Diamond', cost: 50, icon_url: '/images/gifts/diamond.svg' }
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

    // Mock available gifts
    cy.intercept('GET', '**/api/gifts', {
      statusCode: 200,
      body: { data: gifts }
    }).as('getGifts');

    // Mock partner profile
    cy.intercept('GET', '**/api/users/2', {
      statusCode: 200,
      body: { data: partner }
    }).as('getPartner');
  });

  it('allows sending a gift from profile', () => {
    cy.visit('/users/2', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // 1. Click Gift Button
    cy.contains('Send Gift').should('be.visible').click();

    // 2. Verify Gift Modal
    cy.contains('Select a Gift').should('be.visible');
    cy.contains('Rose (10 tokens)').should('be.visible');
    
    // 3. Select Gift
    cy.contains('Rose').click();
    
    // 4. Enter Message
    cy.get('textarea[placeholder="Add a message..."]').type('A rose for you!');

    // 5. Mock Send API
    cy.intercept('POST', '**/api/gifts/send', {
      statusCode: 200,
      body: { success: true }
    }).as('sendGift');

    // 6. Send
    cy.contains('button', 'Send Gift').click();

    // 7. Verify Success
    cy.wait('@sendGift').its('request.body').should('deep.include', {
      receiver_id: 2,
      gift_id: 1,
      message: 'A rose for you!'
    });

    cy.contains('Gift sent successfully!').should('be.visible');
  });

  it('shows error when insufficient tokens', () => {
    const poorUser = { ...user, token_balance: 5 };
    
    cy.visit('/users/2', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(poorUser));
      }
    });

    cy.contains('Send Gift').click();
    cy.contains('Rose').click();

    // Mock Send API Failure
    cy.intercept('POST', '**/api/gifts/send', {
      statusCode: 400,
      body: { message: 'Insufficient tokens' }
    }).as('sendGiftFail');

    cy.contains('button', 'Send Gift').click();

    cy.wait('@sendGiftFail');
    cy.contains('Insufficient tokens').should('be.visible');
  });
});
