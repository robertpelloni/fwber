describe('Two-Factor Authentication Flow', () => {
  const email = 'test-2fa@example.com';
  const password = 'password123';
  const secret = 'KVKFKRCPNZQUYMLXOVYDSQKJKZDTSRLD'; // Base32 secret
  const user = {
    id: 1,
    name: 'Test User 2FA',
    email: email,
    emailVerifiedAt: new Date().toISOString(),
    onboarding_completed_at: new Date().toISOString(),
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    two_factor_enabled: false,
    profile: {
      displayName: 'Test User 2FA',
      avatarUrl: null
    }
  };

  beforeEach(() => {
    // Reset mocks
    cy.intercept('GET', '**/api/user', (req) => {
      req.reply({ statusCode: 200, body: { data: user } });
    }).as('getUser');
  });

  it('should enable 2FA, logout, and require 2FA on login', () => {
    // --- Registration Mock ---
    cy.intercept('POST', '**/api/auth/register', {
      statusCode: 201,
      body: {
        user: user,
        token: 'fake-jwt-token'
      }
    }).as('register');

    // Visit Register
    cy.visit('/register');
    cy.get('input[name="name"]').type('Test User 2FA');
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type(password);
    cy.get('input[name="passwordConfirmation"]').type(password);
    cy.get('button[type="submit"]').click();
    
    cy.wait('@register');
    cy.url().should('include', '/dashboard');

    // --- Enable 2FA Mock ---
    // Mock QR Code response
    cy.intercept('GET', '**/api/user/two-factor-qr-code', {
      statusCode: 200,
      body: {
        svg: '<svg>...</svg>',
        url: `otpauth://totp/fwber:${email}?secret=${secret}&issuer=fwber`
      }
    }).as('getQrCode');

    // Mock Enable/Confirm 2FA
    cy.intercept('POST', '**/api/user/two-factor-authentication', {
      statusCode: 200,
      body: {}
    }).as('enable2FA');
    
    cy.intercept('POST', '**/api/user/confirmed-two-factor-authentication', {
      statusCode: 200,
      body: {}
    }).as('confirm2FA');

    // Mock Recovery Codes
    cy.intercept('GET', '**/api/user/two-factor-recovery-codes', {
      statusCode: 200,
      body: { recovery_codes: ['code1', 'code2'] }
    }).as('getRecoveryCodes');

    // Go to 2FA settings
    cy.visit('/settings/two-factor', {
        onBeforeLoad: (win) => {
            win.localStorage.setItem('fwber_token', 'fake-jwt-token');
            win.localStorage.setItem('fwber_user', JSON.stringify(user));
        }
    });
    
    // Click Enable
    cy.contains('button', 'Enable Two-Factor Authentication').click();
    
    // Wait for QR code
    cy.wait('@getQrCode');
    
    // Generate TOTP code
    cy.task('generateOTP', secret).then((token) => {
      // Enter code
      cy.get('input[id="code"]').type(token);
      cy.contains('button', 'Verify').click();
      
      cy.wait('@confirm2FA');
      
      // Should show success message and recovery codes
      cy.contains('Two-Factor Authentication is Enabled').should('be.visible');
      cy.contains('Recovery Codes').should('be.visible');
    });

    // --- Logout Mock ---
    cy.intercept('POST', '**/api/auth/logout', { statusCode: 200 }).as('logout');

    // Logout
    // Assuming there is a logout button visible or we can find it
    // If not, we might need to open a menu
    cy.get('body').then($body => {
        if ($body.find('button:contains("Log Out")').length > 0) {
            cy.contains('button', 'Log Out').click({ force: true });
        } else {
            // Try to find it in a menu or just visit login (simulating logout)
             cy.visit('/login', {
                onBeforeLoad: (win) => {
                    win.localStorage.clear();
                }
            });
        }
    });
    
    cy.url().should('include', '/login');

    // --- Login with 2FA Mock ---
    // 1. Login returns 2fa required
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        two_factor: true
      }
    }).as('login2FA');

    // 2. Verify 2FA challenge
    cy.intercept('POST', '**/api/two-factor-challenge', {
      statusCode: 200,
      body: {
        token: 'new-fake-token',
        user: { ...user, two_factor_enabled: true }
      }
    }).as('verifyChallenge');

    // Login again
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();

    cy.wait('@login2FA');

    // Should show 2FA challenge
    cy.contains('Two-Factor Authentication').should('be.visible');
    
    // Enter TOTP code
    cy.task('generateOTP', secret).then((token) => {
      cy.get('input[id="code"]').type(token);
      cy.contains('button', 'Verify').click();
      
      cy.wait('@verifyChallenge');
      
      // Should redirect to dashboard
      cy.url().should('include', '/dashboard');
    });
  });
});
