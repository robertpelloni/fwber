describe('Premium Features', () => {
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
    cy.intercept('GET', '**/api/user', { statusCode: 200, body: { data: user } }).as('getUser');
    
    // Mock premium status (default: free)
    cy.intercept('GET', '**/api/premium/status', {
      statusCode: 200,
      body: { is_premium: false, plan: 'free' }
    }).as('getPremiumStatus');
  });

  it('prompts upgrade and unlocks features upon purchase', () => {
    // Mock who-likes-you (locked)
    cy.intercept('GET', '**/api/premium/who-likes-you', {
      statusCode: 403,
      body: { message: 'Premium required' }
    }).as('getWhoLikesYouLocked');

    cy.visit('/premium', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    cy.contains('Upgrade to Gold').should('be.visible');
    
    // Mock purchase
    cy.intercept('POST', '**/api/premium/purchase', {
      statusCode: 200,
      body: { success: true, plan: 'gold' }
    }).as('purchasePremium');

    // Mock status after purchase
    cy.intercept('GET', '**/api/premium/status', {
      statusCode: 200,
      body: { is_premium: true, plan: 'gold' }
    }).as('getPremiumStatusGold');

    // Mock who-likes-you (unlocked)
    cy.intercept('GET', '**/api/premium/who-likes-you', {
      statusCode: 200,
      body: { users: [{ id: 2, name: 'Admirer', avatarUrl: '/images/test-avatar.svg' }] }
    }).as('getWhoLikesYouUnlocked');

    cy.contains('button', 'Upgrade to Gold').click();
    cy.wait('@purchasePremium');
    
    // Verify UI update
    cy.contains('Premium Active').should('be.visible');
    
    // Navigate to Who Likes You
    cy.visit('/who-likes-you');
    cy.wait('@getWhoLikesYouUnlocked');
    cy.contains('Admirer').should('be.visible');
  });
});
