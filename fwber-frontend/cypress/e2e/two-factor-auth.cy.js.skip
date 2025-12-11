describe('Two-Factor Authentication Flow', () => {
  const email = `test-2fa-${Date.now()}@example.com`;
  const password = 'password123';
  let secret;

  it('should enable 2FA, logout, and require 2FA on login', () => {
    // Register a new user
    cy.visit('/register');
    cy.get('input[name="name"]').type('Test User 2FA');
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type(password);
    cy.get('input[name="passwordConfirmation"]').type(password);
    cy.get('button[type="submit"]').click();
    
    // Check for success message or error
    cy.get('body').then(($body) => {
      if ($body.text().includes('Registration failed')) {
        throw new Error('Registration failed: ' + $body.text());
      }
    });

    // Should redirect to dashboard
    cy.url({ timeout: 30000 }).should('include', '/dashboard');

    // Go to 2FA settings
    cy.visit('/settings/two-factor');
    
    // Click Enable
    cy.contains('Enable Two-Factor Authentication').click();
    
    // Wait for QR code and intercept the request to get the secret
    cy.intercept('GET', '/api/user/two-factor-qr-code').as('getQrCode');
    cy.wait('@getQrCode').then((interception) => {
      const url = interception.response.body.url;
      // Extract secret from otpauth URL
      // otpauth://totp/FWBer:test-2fa-...?secret=SECRET&...
      const match = url.match(/secret=([^&]+)/);
      secret = match[1];
      expect(secret).to.be.ok;

      // Generate TOTP code
      cy.task('generateOTP', secret).then((token) => {
        // Enter code
        cy.get('input[id="code"]').type(token);
        cy.contains('button', 'Verify').click();
        
        // Should show success message and recovery codes
        cy.contains('Two-Factor Authentication is Enabled').should('be.visible');
        cy.contains('Recovery Codes').should('be.visible');
      });
    });

    // Logout
    cy.contains('Log Out').click(); // Assuming there is a logout button in the sidebar or header, or we go to settings
    // Actually, let's use the logout button in settings page since we are there, or navigate to settings
    cy.visit('/settings');
    cy.contains('Log Out').click();
    cy.url().should('include', '/login');

    // Login again
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();

    // Should show 2FA challenge
    cy.contains('Two-Factor Authentication').should('be.visible');
    cy.contains('Please confirm access to your account').should('be.visible');

    // Enter TOTP code
    cy.task('generateOTP', secret).then((token) => {
      cy.get('input[id="code"]').type(token);
      cy.contains('button', 'Verify').click();
      
      // Should redirect to dashboard
      cy.url().should('include', '/dashboard');
    });
  });
});
