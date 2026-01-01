describe('Event Group Invitations', () => {
  beforeEach(() => {
    // 1. Mock the user as logged in (User ID 1)
    // IMPORTANT: Inject token to ensure hooks like useEvent(id) are enabled.
    // AuthProvider checks for 'auth_token' === 'dev' to bypass auth logic.
    window.localStorage.setItem('auth_token', 'dev');
    
    cy.intercept('GET', '**/api/user', { fixture: 'user.json' }).as('getUser');
    
    // 2. Mock established matches (for the default "Friends" tab)
    cy.intercept('GET', '**/api/matches/established', { fixture: 'matches.json' }).as('getMatches');
    
    // 3. Mock the specific event details
    cy.intercept('GET', '**/api/events/1', {
      statusCode: 200,
      body: {
        id: 1,
        title: 'Group Party',
        description: 'A party for groups.',
        starts_at: '2025-12-31T20:00:00Z',
        ends_at: '2026-01-01T02:00:00Z',
        location_name: 'The Club',
        status: 'upcoming',
        attendees_count: 10,
        max_attendees: 100,
        price: 0,
        token_cost: 0,
        chatroom_id: null
      }
    }).as('getEvent');

    // 4. Mock the user's groups list
    cy.intercept('GET', '**/api/groups/my-groups', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 101,
            name: 'Weekend Hikers',
            icon: null,
            member_count: 15
          },
          {
            id: 102,
            name: 'Book Club',
            icon: 'https://via.placeholder.com/40',
            member_count: 5
          }
        ],
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            per_page: 15,
            to: 2,
            total: 2
        }
      }
    }).as('getMyGroups');

    // 5. Mock the invite endpoint
    cy.intercept('POST', '**/api/events/1/invite', (req) => {
      // Verify payload
      if (req.body.group_id) {
        req.reply({
          statusCode: 201,
          body: {
            message: 'Invitations sent to 14 group members.',
            count: 14
          }
        });
      } else {
        req.reply({ statusCode: 400, body: { message: 'Invalid payload' } });
      }
    }).as('inviteGroup');

    // Visit the event page
    cy.visit('/events/1', {
      onBeforeLoad: (win) => {
        // Use 'dev' to trigger the special development bypass in AuthProvider
        win.localStorage.setItem('auth_token', 'dev');
      }
    });
    // cy.wait('@getEvent'); // Removed explicit wait to avoid race condition if fetch happens too fast
  });

  it('allows inviting an entire group to the event', () => {
    // 1. Open the Invite Modal
    cy.contains('button', 'Invite').click();
    cy.contains('h2', 'Invite to Event').should('be.visible');

    // 2. Verify default tab is Friends
    cy.contains('button', 'Friends').should('have.class', 'text-blue-600');
    
    // 3. Switch to My Groups tab
    cy.contains('button', 'My Groups').click();
    cy.contains('button', 'My Groups').should('have.class', 'text-blue-600');
    
    // 4. Wait for groups to load
    cy.wait('@getMyGroups');
    
    // 5. Verify groups are listed
    cy.contains('Weekend Hikers').should('be.visible');
    cy.contains('15 members').should('be.visible');
    cy.contains('Book Club').should('be.visible');

    // 6. Invite "Weekend Hikers"
    // Find the button inside the row containing "Weekend Hikers"
    cy.contains('div', 'Weekend Hikers')
      .parents('.flex.items-center.justify-between')
      .find('button[aria-label="Invite Group"]')
      .click();

    // 7. Verify API call
    cy.wait('@inviteGroup').its('request.body').should('deep.equal', {
      group_id: 101
    });

    // 8. Verify UI update (Checkmark)
    cy.contains('div', 'Weekend Hikers')
      .parents('.flex.items-center.justify-between')
      .find('button')
      .should('have.attr', 'aria-label', 'Invited');
      
    // 9. Close modal
    cy.get('button[aria-label="Close"]').click();
    cy.contains('Invite to Event').should('not.exist');
  });

  it('handles empty groups state', () => {
    // Override groups mock to return empty
    cy.intercept('GET', '**/api/groups/my-groups', {
      statusCode: 200,
      body: { data: [], meta: { total: 0 } }
    }).as('getEmptyGroups');

    cy.contains('button', 'Invite').click();
    cy.contains('button', 'My Groups').click();
    cy.wait('@getEmptyGroups');

    cy.contains('No groups found to invite.').should('be.visible');
  });
});
