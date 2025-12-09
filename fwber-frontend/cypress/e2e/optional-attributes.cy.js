describe('Optional Profile Attributes (Personality & Social)', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    email_verified_at: new Date().toISOString(),
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    profile: {
      display_name: 'Test User',
      birthdate: '1990-01-01',
      gender: 'non-binary',
      pronouns: 'they/them',
      sexual_orientation: 'pansexual',
      relationship_style: 'polyamorous',
      bio: 'Test bio',
      location: {
        latitude: 40.7128,
        longitude: -74.0060,
        city: 'New York',
        state: 'NY',
        max_distance: 25
      },
      // Initial values for optional attributes
      love_language: null,
      personality_type: null,
      political_views: null,
      religion: null,
      sleep_schedule: null,
      social_media: {},
      
      looking_for: ['friendship'],
      interested_in: ['everyone'],
      preferences: {},
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
  };

  beforeEach(() => {
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: { token: 'fake-token', user }
    }).as('login');

    cy.intercept('GET', '**/api/auth/me', {
      statusCode: 200,
      body: user
    }).as('getMe');

    cy.intercept('GET', '**/api/profile', {
      statusCode: 200,
      body: { 
        success: true,
        data: {
            ...user,
            profile: user.profile
        }
      }
    }).as('getProfile');

    cy.intercept('GET', '**/api/profile/completeness', {
        statusCode: 200,
        body: { percentage: 50, missing_fields: [] }
    }).as('getCompleteness');

    cy.intercept('PUT', '**/api/profile', {
      statusCode: 200,
      body: {
        success: true,
        message: 'Profile updated successfully',
        data: {
            ...user,
            profile: {
                ...user.profile,
                love_language: 'quality_time',
                personality_type: 'INTJ',
                political_views: 'liberal',
                religion: 'agnostic',
                sleep_schedule: 'night_owl',
                social_media: { instagram: 'test_handle' }
            }
        }
      }
    }).as('updateProfile');
  });

  it('allows editing Personality & Social attributes', () => {
    cy.visit('/profile', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('auth_token', 'dev');
      }
    });

    // Wait for profile fetch
    cy.wait('@getProfile');

    // Scroll to the new section
    cy.contains('h2', 'Personality & Social').scrollIntoView().should('be.visible');

    // Fill in the fields
    // Note: Selectors depend on how EnhancedProfileEditor renders them. 
    // Assuming standard selects/inputs based on the implementation.
    
    // Love Language
    cy.contains('label', 'Love Language').parent().find('select').select('Quality Time');
    
    // Personality Type
    cy.contains('label', 'Personality Type').parent().find('input').clear().type('INTJ');
    
    // Political Views
    cy.contains('label', 'Political Views').parent().find('select').select('Liberal');
    
    // Religion
    cy.contains('label', 'Religion').parent().find('select').select('Agnostic');
    
    // Sleep Schedule
    cy.contains('label', 'Sleep Schedule').parent().find('select').select('Night Owl');
    
    // Social Media (Instagram)
    cy.contains('label', 'Instagram').parent().find('input').clear().type('test_handle');

    // Save
    cy.contains('button', 'Save Changes').click();

    // Verify request payload
    cy.wait('@updateProfile').then((interception) => {
      const body = interception.request.body;
      expect(body).to.have.property('love_language', 'quality_time');
      expect(body).to.have.property('personality_type', 'INTJ');
      expect(body).to.have.property('political_views', 'liberal');
      expect(body).to.have.property('religion', 'agnostic');
      expect(body).to.have.property('sleep_schedule', 'night_owl');
      expect(body.social_media).to.deep.equal({ instagram: 'test_handle' });
    });

    // Verify success message
    cy.contains('Profile updated successfully').should('be.visible');
  });
});
