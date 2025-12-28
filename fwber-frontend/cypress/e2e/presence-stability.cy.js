describe('Presence Stability & Pusher Integration', () => {
  beforeEach(() => {
    // Mock API endpoints to prevent network errors from cluttering logs
    cy.intercept('GET', '**/websocket/token', { statusCode: 200, body: { token: 'mock-token' } }).as('tokenRequest');
    cy.intercept('POST', '**/broadcasting/auth', { statusCode: 200 }).as('pusherAuth');

    // Set up authenticated state
    cy.visit('/websocket', {
      onBeforeLoad(win) {
        win.localStorage.setItem('fwber_token', 'mock-jwt-token');
        win.localStorage.setItem('fwber_user', JSON.stringify({
          id: 1,
          name: 'Test User',
          email: 'test@example.com'
        }));
      }
    });
  });

  it('should render the WebSocket page without crashing', () => {
    // Verify the main header is visible - this confirms the page component mounted
    cy.contains('h1', 'WebSocket Real-Time Communication').should('be.visible');
  });

  it('should render presence components', () => {
    // Verify Presence components are rendered
    cy.contains('h3', 'Connection Status').should('be.visible');
    cy.contains('h3', 'Online Users').should('be.visible');
    
    // Verify the connection status badge exists (even if disconnected)
    cy.contains('Disconnected').should('exist');
  });

  it('should render RealTimeChat component', () => {
    // Verify RealTimeChat placeholder is visible
    cy.contains('Select a recipient to start chatting').should('be.visible');
  });
});
