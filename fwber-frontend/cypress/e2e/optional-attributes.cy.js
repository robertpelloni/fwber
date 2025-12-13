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

    cy.intercept('GET', '**/api/user', {
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

    cy.intercept('PUT', '**/api/user', {
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
                sleep_habits: 'night-owl',
                social_media: { instagram: 'test_handle' }
            }
        }
      }
    }).as('updateProfile');
  });

  it('allows editing Personality & Social attributes', () => {
    cy.visit('/profile', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'dev');
        win.localStorage.setItem('auth_token', 'dev');
      }
    });

    // Wait for profile fetch
    cy.wait('@getProfile');

    // Verify page content loaded
    cy.contains('Basic Information', { timeout: 10000 }).should('be.visible');

    // Enable Edit Mode
    cy.contains('button', 'Edit Profile').click();

    // 1. Basic Info Tab (Love Language, Personality Type, Religion, Political Views)
    cy.contains('button', 'Basic Info').click();
    
    // Love Language
    cy.contains('label', 'Love Language').parent().find('select').select('Quality Time');
    
    // Personality Type
    cy.contains('label', 'Personality Type (MBTI)').parent().find('select').select('INTJ');
    
    // Religion
    cy.contains('label', 'Religion').parent().find('select').select('Agnostic');
    
    // Political Views
    cy.contains('label', 'Political Views').parent().find('select').select('Liberal');

    // 2. Lifestyle Tab (Sleep Habits)
    cy.contains('button', 'Lifestyle').click();
    
    // Sleep Habits
    cy.contains('label', 'Sleep Habits').parent().find('select').select('Night owl');

    // 3. Dating Tab (Relationship Goals, Family Plans)
    cy.contains('button', 'Dating').click();
    
    // Relationship Goals
    cy.contains('label', 'Relationship Goals').parent().find('select').select('Long-term partner');

    // Save
    cy.contains('button', 'Save Profile').click();

    // Verify request payload
    cy.wait('@updateProfile').then((interception) => {
      const body = interception.request.body;
      expect(body).to.have.property('love_language', 'quality_time');
      expect(body).to.have.property('personality_type', 'INTJ');
      expect(body).to.have.property('political_views', 'liberal');
      expect(body).to.have.property('religion', 'agnostic');
      expect(body).to.have.property('sleep_habits', 'night-owl');
      expect(body).to.have.property('relationship_goals', 'long-term');
    });

    // Verify success message
    cy.contains('Profile updated successfully').should('be.visible');
  });
});
