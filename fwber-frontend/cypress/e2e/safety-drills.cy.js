describe('Safety Drills (Geo-Spoofing & Shadow Banning)', () => {
  const moderator = {
    id: 99,
    name: 'Mod User',
    is_moderator: true
  };

  const mockSpoof = {
    id: 201,
    user_id: 50,
    suspicion_score: 95,
    detected_at: new Date().toISOString(),
    is_confirmed_spoof: false
  };

  const mockThrottle = {
    id: 301,
    user_id: 60,
    severity: 3,
    reason: 'Repeated abuse',
    started_at: new Date().toISOString(),
    expires_at: new Date(Date.now() + 86400000).toISOString()
  };

  beforeEach(() => {
    // Login Mock
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'mod-token', user: moderator }
    }).as('login');

    // Dashboard Stats Mock
    cy.intercept('GET', '**/api/moderation/dashboard', {
      statusCode: 200,
      body: {
        stats: {
          flagged_artifacts: 0,
          active_throttles: 1,
          pending_spoof_detections: 1,
          moderation_actions_today: 5
        },
        recent_actions: []
      }
    }).as('getDashboard');

    // Tab Mocks
    cy.intercept('GET', '**/api/moderation/flagged-content*', { body: { data: [] } });
    
    cy.intercept('GET', '**/api/moderation/spoof-detections*', {
      statusCode: 200,
      body: {
        data: [mockSpoof],
        current_page: 1,
        last_page: 1,
        total: 1
      }
    }).as('getSpoofs');

    cy.intercept('GET', '**/api/moderation/throttles*', {
      statusCode: 200,
      body: {
        data: [mockThrottle],
        current_page: 1,
        last_page: 1,
        total: 1
      }
    }).as('getThrottles');

    cy.intercept('GET', '**/api/moderation/actions*', { body: { data: [] } });

    // Action Mocks
    cy.intercept('POST', '**/api/moderation/spoof-detections/*/review', {
      statusCode: 200,
      body: { message: 'Spoof confirmed' }
    }).as('reviewSpoof');

    cy.intercept('DELETE', '**/api/moderation/throttles/*', {
      statusCode: 200,
      body: { message: 'Throttle removed' }
    }).as('removeThrottle');
  });

  it('allows confirming a geo-spoof detection', () => {
    cy.visit('/moderation', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'mod-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(moderator));
      }
    });

    // Navigate to Geo-Spoofs tab
    cy.contains('button', 'Geo-Spoofs').click();
    cy.wait('@getSpoofs');

    // Verify spoof content
    cy.contains(`User #${mockSpoof.user_id}`).should('be.visible');
    cy.contains(`Score: ${mockSpoof.suspicion_score}`).should('be.visible');

    // Confirm & Throttle
    cy.contains('button', 'Confirm & Throttle').click();

    // Verify API call
    cy.wait('@reviewSpoof').then((interception) => {
      expect(interception.request.body).to.deep.include({
        action: 'confirm',
        apply_throttle: true
      });
    });
  });

  it('allows removing an active shadow ban (throttle)', () => {
    cy.visit('/moderation', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'mod-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(moderator));
      }
    });

    // Navigate to Throttles tab
    cy.contains('button', 'Throttles').click();
    cy.wait('@getThrottles');

    // Verify throttle content
    cy.contains(`User #${mockThrottle.user_id}`).should('be.visible');
    cy.contains(`Severity ${mockThrottle.severity}`).should('be.visible');
    cy.contains(`Reason: ${mockThrottle.reason}`).should('be.visible');

    // Remove Throttle
    cy.contains('button', 'Remove').click();

    // Verify API call
    cy.wait('@removeThrottle').then((interception) => {
      expect(interception.request.url).to.include(`/api/moderation/throttles/${mockThrottle.id}`);
    });
  });
});
