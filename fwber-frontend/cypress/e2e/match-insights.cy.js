describe('Match Insights Integration', () => {
    const user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      emailVerifiedAt: new Date().toISOString(),
      onboarding_completed_at: new Date().toISOString(),
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
      },
      token_balance: 100
    };
  
    const match = {
        id: 2,
        name: 'Potential Match',
        bio: 'I like hiking',
        age: 25,
        locationDescription: 'New York, NY',
        avatarUrl: '/images/test-avatar.svg',
        compatibilityScore: 0.9,
        distance: 5,
        is_verified: true,
        photos: [{ id: 2, url: '/images/test-avatar.svg', is_private: false, is_primary: true }]
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
      
      // Mock share-unlock status
      cy.intercept('GET', '**/api/share-unlock/*', {
          statusCode: 200,
          body: { unlocked: false }
      }).as('getShareUnlock');

      // Mock subscription check
      cy.intercept('GET', '**/api/subscriptions/creator/*', {
          statusCode: 200,
          body: { is_subscribed: false }
      }).as('checkSubscription');
    });
  
    it('shows locked insights and unlocks with tokens', () => {
        // Mock matches feed with one match
        cy.intercept('GET', '**/api/matches*', {
            statusCode: 200,
            body: {
            data: {
                matches: [match]
            }
            }
        }).as('getMatches');
  
      // Mock locked insights
      cy.intercept('GET', `**/api/matches/${match.id}/insights`, {
        statusCode: 200,
        body: {
          data: {
            total_score: 85,
            is_locked: true,
            cost: 10,
            preview_message: 'Unlock detailed compatibility analysis and AI insights for 10 tokens.'
          }
        }
      }).as('getLockedInsights');
  
      // Mock unlock action
      cy.intercept('POST', `**/api/matches/${match.id}/insights/unlock`, {
        statusCode: 200,
        body: {
          message: 'Unlocked successfully',
          balance: 90
        }
      }).as('unlockInsights');
  
      // Mock unlocked insights (after unlock)
      cy.intercept('GET', `**/api/matches/${match.id}/insights`, (req) => {
        // Simple logic to simulate server state
        // If we haven't unlocked yet (based on previous requests?), return locked
        // But for Cypress, we rely on the order of interception or aliases usually.
        // However, a simpler way is to just define the "locked" one first, wait for it, then define the unlocked one?
        // No, `cy.intercept` matches the most recent one unless otherwise specified.
        // Let's rely on specific aliases.
      }).as('anyInsights');

       // Mock locked insights
       cy.intercept('GET', `**/api/matches/${match.id}/insights`, {
        statusCode: 200,
        body: {
          data: {
            total_score: 85,
            is_locked: true,
            cost: 10,
            preview_message: 'Unlock detailed compatibility analysis and AI insights for 10 tokens.'
          }
        }
      }).as('getLockedInsights');
  
      cy.visit('/matches', {
        onBeforeLoad: (win) => {
          win.localStorage.setItem('fwber_token', 'fake-token');
          win.localStorage.setItem('fwber_user', JSON.stringify(user));
        }
      });
  
      // Wait for matches and click info button
      cy.contains('Potential Match').should('be.visible');
      
      // Click the info button (or the image area which also opens profile)
      // The image area has onClick={() => setIsProfileOpen(true)}
      cy.get('.bg-gray-200.relative.cursor-pointer').first().click();
  
      // Verify modal opens
      cy.contains('AI Compatibility Report').should('be.visible');

      // Wait for the insights request to actually happen
      cy.wait('@getLockedInsights');
      
      // Debugging: Check if we are in a loading state or error state
      cy.get('body').then(($body) => {
        if ($body.find('div.animate-pulse').length > 0) {
            cy.log('STILL LOADING INSIGHTS');
        }
        if ($body.text().includes('Analysis unavailable')) {
            cy.log('ANALYSIS UNAVAILABLE DETECTED');
        }
      });

      // Verify locked state elements
      cy.contains('85%').should('be.visible');
      cy.contains('Unlock Analysis (10 Tokens)').should('be.visible');
      
      // Click unlock
      cy.contains('Unlock Analysis (10 Tokens)').click();

      // Now override the intercept to return UNLOCKED insights
      cy.intercept('GET', `**/api/matches/${match.id}/insights`, {
        statusCode: 200,
        body: {
            data: {
                total_score: 85,
                is_locked: false,
                ai_explanation: 'You both love hiking and outdoor adventures. This is a high compatibility match!',
                breakdown: {
                    base: 80,
                    preferences: 90,
                    communication: 85,
                    mutual: 88
                }
            }
        }
      }).as('getUnlockedInsights');

      // Verify API call and UI update
      cy.wait('@unlockInsights');
      
      // Should now see the explanation
      cy.contains('You both love hiking and outdoor adventures').should('be.visible');
      cy.contains('Base').should('be.visible');
    });
  });
  