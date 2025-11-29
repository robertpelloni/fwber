describe('Profile Boosts', () => {
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
    
    cy.intercept('GET', '**/api/boosts/active', {
      statusCode: 200,
      body: { active: false, remaining_time: 0 }
    }).as('getBoostStatus');

    cy.intercept('GET', '**/api/matches*', {
      statusCode: 200,
      body: {
        matches: [
          {
            id: 2,
            name: 'Match User',
            age: 25,
            locationDescription: 'New York, NY',
            avatarUrl: '/images/match.jpg',
            compatibilityScore: 0.95,
            bio: 'Test Bio'
          }
        ],
        total: 1
      }
    }).as('getMatches');
  });

  it('allows purchasing a profile boost', () => {
    cy.visit('/matches', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    cy.contains('button', 'Boost Profile').should('be.visible');

    cy.intercept('POST', '**/api/boosts/purchase', {
      statusCode: 200,
      body: { success: true, active: true, remaining_time: 1800 }
    }).as('purchaseBoost');

    cy.intercept('GET', '**/api/boosts/active', {
      statusCode: 200,
      body: { active: true, remaining_time: 1800 }
    }).as('getBoostStatusActive');

    cy.contains('button', 'Boost Profile').click();
    cy.wait('@purchaseBoost');

    cy.contains('Boost Active').should('be.visible');
  });
});
