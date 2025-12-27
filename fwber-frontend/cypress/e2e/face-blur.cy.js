describe('Face Blur Feature', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    profile_complete: false
  };

  beforeEach(() => {
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'fake-token', user }
    }).as('login');

    cy.intercept('GET', '**/api/profile', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          email: 'test@example.com',
          profile: {
            display_name: 'Test User',
            birthdate: '1990-01-01',
            gender: 'non-binary',
            location: { city: 'Test City', state: 'TC', latitude: 0, longitude: 0 }
          }
        }
      }
    }).as('getProfile');

    // Mock feature flags to ensure face blur is enabled
    cy.intercept('GET', '**/api/config/features', {
      statusCode: 200,
      body: {
        clientFaceBlur: true
      }
    }).as('getFeatures');
  });

  it('loads the photo upload component without crashing', () => {
    cy.visit('/onboarding', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        // Force feature flag if using local storage or window object
        win.__CYPRESS_FEATURE_FLAGS__ = { clientFaceBlur: true };
      }
    });

    // Navigate to photos step
    cy.contains('Next').click(); // Basic Info -> Photos
    
    // Verify component is visible
    cy.contains('Add your best photos').should('be.visible');
    cy.contains('Client-side face blur active').should('be.visible');
  });

  it('handles file selection without module error', () => {
    cy.visit('/onboarding', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        win.__CYPRESS_FEATURE_FLAGS__ = { clientFaceBlur: true };
      }
    });

    cy.contains('Next').click();

    // Upload a file
    cy.get('input[type="file"]').selectFile('cypress/fixtures/test-image.jpg', { force: true });

    // Check for processing state (this confirms the code is running)
    // Note: We don't expect the actual blur to finish successfully in this mock environment 
    // without loading the heavy models, but we want to ensure no "Module factory" crash.
    cy.contains('Detecting faces locally').should('exist');
  });
});
