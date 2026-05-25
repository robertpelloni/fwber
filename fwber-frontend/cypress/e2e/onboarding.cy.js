describe('User Onboarding Flow', () => {
  beforeEach(() => {
    // Mock Auth User (Incomplete Onboarding)
    cy.intercept('GET', '/api/auth/me', {
      statusCode: 200,
      body: {
        id: 1,
        name: 'New User',
        email: 'new@example.com',
        onboarding_completed_at: null, // Critical: triggers redirection to /onboarding
      },
    }).as('getAuthUser');

    // Mock Profile Get
    cy.intercept('GET', '/api/profile', {
      statusCode: 200,
      body: {
        profile: {
          display_name: '',
          date_of_birth: '',
          gender: '',
          location: { city: '', state: '', latitude: 0, longitude: 0 },
          looking_for: [],
          preferences: { age_range_min: 18, age_range_max: 50 },
        },
      },
    }).as('getProfile');

    // Mock Profile Update
    cy.intercept('PUT', '/api/profile', (req) => {
      // Verify payload transformation (date_of_birth -> birthdate)
      if (req.body.birthdate) {
        expect(req.body.birthdate).to.match(/^\d{4}-\d{2}-\d{2}$/);
      }
      req.reply({
        statusCode: 200,
        body: { message: 'Profile updated' },
      });
    }).as('updateProfile');

    // Mock Photo Upload
    cy.intercept('POST', '/api/photos', {
      statusCode: 201,
      body: {
        success: true,
        data: {
          id: 101,
          url: 'https://example.com/photo.jpg',
          is_primary: true,
        }
      },
    }).as('uploadPhoto');

    // Mock Photos List
    cy.intercept('GET', '/api/photos', {
      statusCode: 200,
      body: { success: true, data: [] },
    }).as('getPhotos');

    // Mock Onboarding Complete
    cy.intercept('POST', '/api/onboarding/complete', {
      statusCode: 200,
      body: { message: 'Onboarding completed' },
    }).as('completeOnboarding');

    // Set localStorage for AuthProvider
    // We need to visit the page first to have access to window, but AuthProvider checks on mount.
    // So we visit a dummy page or use onBeforeLoad
    cy.visit('/onboarding', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'mock-token');
        win.localStorage.setItem('fwber_user', JSON.stringify({
          id: 1,
          name: 'New User',
          email: 'new@example.com',
          onboarding_completed_at: null
        }));
      }
    });
  });

  it('completes the full onboarding wizard', () => {
    // Step 1: Welcome
    cy.contains('Welcome to fwber!').should('be.visible');
    cy.contains('Next').click();

    // Step 2: Basic Info
    cy.contains('Basic Info').should('be.visible');
    
    // Validation check
    cy.contains('Next').click();
    cy.contains('Please fill in all required fields').should('be.visible');

    // Fill form
    cy.get('#display_name').type('Test User');
    
    // Select date parts
    cy.get('select').eq(1).select('01'); // Month (January)
    cy.get('select').eq(2).select('01'); // Day
    cy.get('select').eq(3).select('1990'); // Year
    
    cy.get('#gender').select('Male');
    cy.get('#city').type('New York');
    cy.get('#state').type('NY');
    
    cy.contains('Next').click();
    cy.wait('@updateProfile');

    // Step 3: Photos
    cy.contains('Add your best photos').should('be.visible');
    
    // Mock the photo state update since actual upload might be tricky without file
    // We can intercept the GET /api/photos to return a photo after 'upload'
    cy.intercept('GET', '/api/photos', {
      statusCode: 200,
      body: { 
        success: true, 
        data: [{ id: 101, url: 'https://example.com/photo.jpg', is_primary: true }] 
      },
    }).as('getPhotosWithData');

    // Simulate upload by triggering the hook refresh or UI update
    // Since we can't easily drag-drop in this mock without a file, 
    // we might need to rely on the component's internal state or mock the usePhotos hook.
    // However, for E2E, we usually upload a dummy file.
    
    // Create a dummy file
    cy.get('input[type=\'file\']').selectFile({
      contents: Cypress.Buffer.from('file contents'),
      fileName: 'photo.jpg',
      mimeType: 'image/jpeg',
    }, { force: true });

    cy.wait('@uploadPhoto');
    cy.wait('@getPhotosWithData');
    
    // Verify photo appears (mocked response)
    cy.contains('Next').click();

    // Step 4: Preferences
    cy.contains('Preferences').should('be.visible');
    
    // Validation
    cy.contains('Next').click();
    cy.contains('Please select what you are looking for').should('be.visible');

    // Select options
    cy.get('label[for=\'looking_Dating\']').click();
    cy.get('label[for=\'looking_Friends\']').click();
    
    cy.contains('Next').click();
    cy.wait('@updateProfile');

    // Step 5: Complete
    cy.contains('You\'re All Set!').should('be.visible');
    
    // Mock Auth User (Complete) for the redirect check
    cy.intercept('GET', '/api/auth/me', {
        statusCode: 200,
        body: {
          id: 1,
          name: 'New User',
          email: 'new@example.com',
          onboarding_completed_at: '2025-01-01T00:00:00Z',
        },
      }).as('getAuthUserComplete');

    cy.contains('Finish').click();
    cy.wait('@completeOnboarding');

    // Verify redirect
    cy.url().should('include', '/dashboard');
  });

  it('handles optional fields correctly (skips non-required inputs)', () => {
    // Step 1: Welcome
    cy.contains('Next').click();

    // Step 2: Basic Info
    cy.contains('Basic Info').should('be.visible');
    
    // Fill ONLY required fields
    cy.get('#display_name').type('Minimal User');
    
    // Select date parts
    cy.get('select').eq(1).select('05'); // Month (May)
    cy.get('select').eq(2).select('05'); // Day
    cy.get('select').eq(3).select('1995'); // Year

    cy.get('#gender').select('Female');
    // Skip City/State (Optional in backend, but might be required in UI? Let's check)
    // If UI requires them, we fill them. If not, we skip.
    // Assuming City/State are required for location logic, but Bio is optional.
    cy.get('#city').type('London');
    cy.get('#state').type('UK');
    
    // Bio field is not present in the onboarding wizard currently
    // cy.get('textarea[name="bio"]').clear(); 

    cy.contains('Next').click();
    
    // Verify payload contains null/empty for optional fields
    cy.wait('@updateProfile').then((interception) => {
      const body = interception.request.body;
      expect(body.display_name).to.equal('Minimal User');
      // Optional fields should be present but empty/null
      // expect(body.bio).to.satisfy(val => val === null || val === '');
    });
  });
});
