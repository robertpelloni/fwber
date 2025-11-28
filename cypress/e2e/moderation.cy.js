describe('Moderation Dashboard', () => {
  const moderator = {
    id: 99,
    name: 'Mod User',
    is_moderator: true
  };

  beforeEach(() => {
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'mod-token', user: moderator }
    }).as('login');

    cy.intercept('GET', '**/api/moderation/dashboard', {
      statusCode: 200,
      body: {
        stats: {
          flagged_artifacts: 5,
          active_throttles: 2,
          pending_spoof_detections: 1,
          moderation_actions_today: 10
        },
        recent_actions: []
      }
    }).as('getDashboard');

    cy.intercept('GET', '**/api/moderation/flagged-content*', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 101,
            user_id: 5,
            type: 'text',
            content: 'Inappropriate content',
            flag_count: 3,
            created_at: new Date().toISOString()
          }
        ]
      }
    }).as('getFlagged');

    cy.intercept('POST', '**/api/moderation/flags/*/review', {
      statusCode: 200,
      body: { message: 'Action completed successfully' }
    }).as('reviewFlag');
    
    // Mock other tabs to avoid errors if they load eagerly
    cy.intercept('GET', '**/api/moderation/spoof-detections*', { body: { data: [] } });
    cy.intercept('GET', '**/api/moderation/throttles*', { body: { data: [] } });
    cy.intercept('GET', '**/api/moderation/actions*', { body: { data: [] } });
  });

  it('loads dashboard and allows reviewing flags', () => {
    cy.visit('/moderation', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'mod-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(moderator));
      }
    });

    // Check stats
    cy.contains('Moderation Dashboard', { timeout: 10000 }).should('be.visible');
    cy.contains('Flagged Posts').parent().contains('5');

    // Check flagged content
    cy.contains('Inappropriate content').should('be.visible');

    // Approve
    cy.contains('button', 'Approve').click();

    cy.wait('@reviewFlag').its('request.body').should('include', {
      action: 'approve',
      reason: 'Allowed'
    });
  });
});
