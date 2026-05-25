describe('NFC Tap-to-Pay Physical Redemption Flow', () => {
    beforeEach(() => {
      // Mock authentication
      cy.intercept('POST', '/api/auth/login', {
        statusCode: 200,
        body: { token: 'mock-token', user: { id: 1, name: 'Test User', token_balance: 500 } }
      }).as('login');
      
      cy.intercept('GET', '/api/auth/me', {
        statusCode: 200,
        body: { id: 1, name: 'Test User', token_balance: 500 }
      }).as('getMe');
  
      // Mock Marketplace Inventory
      cy.intercept('GET', '/api/marketplace/1', {
        statusCode: 200,
        body: {
          items: [
            {
              id: 101,
              merchant_profile_id: 1,
              name: 'Detroit Dark Roast',
              description: 'Fresh artisanal coffee.',
              price_tokens: 15,
              stock_count: 50,
              is_available: true
            }
          ]
        }
      }).as('getInventory');
  
      // Mock Purchase
      cy.intercept('POST', '/api/marketplace/purchase/101', {
        statusCode: 201,
        body: {
          success: true,
          message: 'Purchase successful!',
          redemption_code: 'FWB-TEST-1234',
          remaining_balance: 485
        }
      }).as('purchaseItem');
      
      cy.visit('/login');
      cy.get('input[type="email"]').type('test@example.com');
      cy.get('input[type="password"]').type('password123');
      cy.get('button[type="submit"]').click();
      cy.wait('@login');
    });
  
    it('should trigger the payment modal when receiving a native NFC payment request', () => {
      cy.visit('/dashboard'); // Dashboards usually hold the physical interaction hub
  
      // 1. Simulate a native NFC message being posted from the app container
      const paymentRequest = {
        type: 'fwber_payment_request',
        itemId: 101,
        itemName: 'Detroit Dark Roast',
        price: 15,
        merchantId: 1,
        timestamp: Date.now()
      };
  
      // Directly call the global bridge callback we established in useNfc
      cy.window().then((win) => {
        if (win.handleNativeNFC) {
          win.handleNativeNFC(JSON.stringify(paymentRequest));
        }
      });
  
      // 2. Verify payment modal appears
      cy.contains('Pay with Tokens').should('be.visible');
      cy.contains('Detroit Dark Roast').should('be.visible');
      cy.contains('15 FWB').should('be.visible');
  
      // 3. Confirm payment
      cy.contains('Pay Now').click();
  
      // 4. Verify API call and success state
      cy.wait('@purchaseItem');
      cy.contains('Confirmed').should('be.visible');
      cy.contains('Successfully paid 15 FWB to merchant').should('be.visible');
    });
  });
