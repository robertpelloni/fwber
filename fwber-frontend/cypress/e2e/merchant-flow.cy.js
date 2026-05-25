describe('Merchant Flow', () => {
  const user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    role: 'user', // Initially a standard user
    profile: {
      displayName: 'Test User',
      locationLatitude: 40.7128,
      locationLongitude: -74.0060,
    }
  };

  const merchantUser = {
    ...user,
    role: 'merchant'
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

    // Mock initial user fetch
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: {
        data: user
      }
    }).as('getUser');
  });

  it('allows a user to register as a merchant', () => {
    // Mock registration endpoint
    cy.intercept('POST', '**/api/merchant-portal/register', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          business_name: "Joe's Pizza",
          category: "Restaurant",
          role: "merchant"
        },
        message: "Merchant profile created successfully"
      }
    }).as('registerMerchant');

    // Visit registration page
    cy.visit('/merchant/register', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
      }
    });

    // Fill form
    cy.get('input[name="business_name"]').type("Joe's Pizza");
    cy.get('select[name="category"]').select('Restaurant');
    cy.get('textarea[name="description"]').type("Best pizza in town");
    cy.get('input[name="address"]').type("123 Main St");

    // Submit
    cy.get('button[type="submit"]').click();

    // Verify API call
    cy.wait('@registerMerchant').its('request.body').should('include', {
      business_name: "Joe's Pizza",
      category: "Restaurant"
    });

    // Should redirect to dashboard
    cy.url().should('include', '/merchant/dashboard');
  });

  it('allows a merchant to create a promotion', () => {
    // Mock user as already being a merchant for this test
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: { data: merchantUser }
    }).as('getMerchantUser');

    // Mock promotion creation
    cy.intercept('POST', '**/api/merchant-portal/promotions', {
      statusCode: 201,
      body: {
        data: {
          id: 101,
          title: "50% Off Lunch",
          code: "LUNCH50"
        },
        message: "Promotion created"
      }
    }).as('createPromotion');

    cy.visit('/merchant/promotions/new', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(merchantUser));
      }
    });

    // Fill form
    cy.get('input[name="title"]').type("50% Off Lunch");
    cy.get('input[name="discount_value"]').type("50%");
    cy.get('input[name="radius"]').clear().type("500");
    cy.get('textarea[name="description"]').type("Valid on weekdays");

    // Mock map click (LocationPicker)
    // Since Leaflet is hard to interact with in Cypress without specific plugins,
    // we might need to manually trigger the form value change if the map click isn't easily simulated.
    // However, looking at the code, we might be able to just stub the map or verify validation fails first.
    // For now, let's try to simulate setting the value if possible, or just click the map container.
    // A more robust way in pure DOM terms if the map exposes input:
    // But the LocationPicker updates via onChange prop passed to it.
    
    // Workaround: If we can't easily click the map, we might need to rely on the fact that 
    // the LocationPicker might default to center or we need to click the container.
    // Let's assume the map container is clickable.
    cy.get('.leaflet-container').click();

    // Submit
    cy.get('button[type="submit"]').click();

    // Verify API call
    cy.wait('@createPromotion');

    // Should redirect to dashboard
    cy.url().should('include', '/merchant/dashboard');
  });
});
