describe('Onboarding skip flow', () => {
  const user = {
    id: 1,
    name: 'Skip Test User',
    email: 'skip@example.com',
    onboarding_completed_at: null,
  };

  beforeEach(() => {
    cy.intercept('GET', '**/api/profile', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          email: 'skip@example.com',
          profile: {
            display_name: '',
            gender: '',
            looking_for: [],
            location: { city: '', state: '', latitude: 0, longitude: 0 },
            preferences: { age_range_min: 18, age_range_max: 50 },
          },
        },
      },
    }).as('getProfile');
  });

  it('lets users skip optional onboarding steps without entering data', () => {
    cy.visit('/onboarding', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      },
    });

    cy.wait('@getProfile');
    cy.contains('Welcome to').should('be.visible');
    cy.contains('Next').click();

    cy.contains('Basic Info').should('be.visible');
    cy.contains('Next').click();

    cy.contains('Add your best photos').should('be.visible');
    cy.contains('Next').click();

    cy.contains('Physical Attributes').should('be.visible');
    cy.contains('Next').click();

    cy.contains('Lifestyle & Habits').should('be.visible');
  });
});
