describe('Events', () => {
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

  const events = [
    {
      id: 1,
      title: 'Community Meetup',
      description: 'A casual meetup for the community.',
      location_name: 'Central Park',
      start_time: new Date(Date.now() + 86400000).toISOString(), // Tomorrow
      end_time: new Date(Date.now() + 90000000).toISOString(),
      organizer_id: 2,
      attendees_count: 5,
      is_attending: false
    }
  ];

  beforeEach(() => {
    cy.intercept('GET', '**/api/user', { statusCode: 200, body: { data: user } }).as('getUser');
    cy.intercept('GET', '**/api/events*', { statusCode: 200, body: { data: events } }).as('getEvents');
  });

  it('displays a list of events', () => {
    cy.visit('/events', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    cy.wait('@getEvents');
    cy.contains('Community Meetup').should('be.visible');
    cy.contains('Central Park').should('be.visible');
  });

  it('allows RSVPing to an event', () => {
    cy.visit('/events', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    cy.wait('@getEvents');

    cy.intercept('GET', '**/api/events/1', {
      statusCode: 200,
      body: { ...events[0], status: 'upcoming' }
    }).as('getEventDetails');

    cy.intercept('POST', '**/api/events/1/rsvp', {
      statusCode: 200,
      body: { success: true, status: 'attending' }
    }).as('rsvpEvent');

    // Click event title to go to details
    cy.contains('Community Meetup').click();
    
    cy.wait('@getEventDetails');
    
    // Click Attend
    cy.contains('button', 'Attend').click();
    
    cy.wait('@rsvpEvent');
  });

  it('allows creating a new event', () => {
    cy.visit('/events/create', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    cy.get('input[name="title"]').type('New Test Event');
    cy.get('textarea[name="description"]').type('Description for the new test event.');
    cy.get('input[name="location"]').type('Virtual');
    cy.get('input[name="latitude"]').type('40.7128');
    cy.get('input[name="longitude"]').type('-74.0060');
    cy.get('input[name="start_time"]').type('2025-12-01T18:00');
    cy.get('input[name="end_time"]').type('2025-12-01T20:00');
    
    cy.intercept('POST', '**/api/events', {
      statusCode: 201,
      body: {
        data: {
          id: 2,
          title: 'New Test Event',
          description: 'Description for the new test event.',
          location: 'Virtual',
          start_time: '2025-12-01T18:00:00Z',
          organizer_id: 1
        }
      }
    }).as('createEvent');

    cy.contains('button', 'Create Event').click();
    cy.wait('@createEvent');
    
    // Should redirect to events list or the new event page
    cy.url().should('include', '/events');
  });
});
