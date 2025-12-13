describe('Real-time Chat & Presence (Mercure)', () => {
  beforeEach(() => {
    // Mock the API endpoint for Mercure token
    cy.intercept('GET', '**/websocket/token', {
      statusCode: 200,
      body: {
        token: 'mock-mercure-token',
        hub_url: 'http://mock-mercure-hub',
      },
    }).as('tokenRequest');

    // Mock other API endpoints used by the hook
    cy.intercept('POST', '**/websocket/message', { statusCode: 200 }).as('sendMessage');
    cy.intercept('POST', '**/websocket/typing', { statusCode: 200 }).as('sendTyping');
    cy.intercept('POST', '**/websocket/presence', { statusCode: 200 }).as('updatePresence');

    cy.visit('/websocket', {
      onBeforeLoad(win) {
        // Set auth token
        win.localStorage.setItem('fwber_token', 'mock-jwt-token');
        win.localStorage.setItem('fwber_user', JSON.stringify({
          id: 'test-user-id',
          name: 'Test User',
          email: 'test@example.com'
        }));

        // Mock EventSource
        class MockEventSource {
          constructor(url) {
            console.log('MockEventSource instantiated with URL:', url);
            this.url = url;
            this.readyState = 0; // CONNECTING
            this.onopen = null;
            this.onmessage = null;
            this.onerror = null;
            this.listeners = {};

            win.mockEventSourceInstance = this;

            setTimeout(() => {
              this.readyState = 1; // OPEN
              if (this.onopen) this.onopen({ type: 'open' });
              this.dispatchEvent({ type: 'open' });
            }, 100);
          }

          close() {
            this.readyState = 2; // CLOSED
          }

          addEventListener(type, listener) {
            if (!this.listeners[type]) this.listeners[type] = [];
            this.listeners[type].push(listener);
          }

          removeEventListener(type, listener) {
            if (this.listeners[type]) {
              this.listeners[type] = this.listeners[type].filter(l => l !== listener);
            }
          }

          dispatchEvent(event) {
            if (this.listeners[event.type]) {
              this.listeners[event.type].forEach(l => l(event));
            }
          }

          // Helper to simulate incoming events
          simulateMessage(data) {
            const event = {
              type: 'message',
              data: JSON.stringify(data),
              lastEventId: '1',
              origin: 'http://mock-mercure-hub'
            };
            if (this.onmessage) this.onmessage(event);
            this.dispatchEvent(event);
          }
        }

        MockEventSource.CONNECTING = 0;
        MockEventSource.OPEN = 1;
        MockEventSource.CLOSED = 2;

        win.EventSource = MockEventSource;
      }
    });
  });

  it('should connect to Mercure and show online status', () => {
    // Auto-connect should trigger
    cy.wait('@tokenRequest');

    cy.window().then((win) => {
      return new Cypress.Promise((resolve) => {
        const checkConnection = () => {
          if (win.mockEventSourceInstance && win.mockEventSourceInstance.readyState === 1) {
            resolve();
          } else {
            setTimeout(checkConnection, 50);
          }
        };
        checkConnection();
      });
    });

    cy.contains('Connected', { timeout: 10000 }).should('be.visible');
  });

  it('should display typing indicator when receiving event', () => {
    // Auto-connect should trigger
    cy.wait('@tokenRequest');

    cy.get('input[placeholder="Enter recipient user ID"]').type('other-user');

    cy.window().then((win) => {
      return new Cypress.Promise((resolve) => {
        const checkInstance = () => {
          if (win.mockEventSourceInstance) {
            resolve(win.mockEventSourceInstance);
          } else {
            setTimeout(checkInstance, 10);
          }
        };
        checkInstance();
      });
    }).then((mockInstance) => {
      mockInstance.simulateMessage({
        type: 'typing_indicator',
        from_user_id: 'other-user',
        to_user_id: 'test-user-id',
        is_typing: true,
        timestamp: new Date().toISOString(),
      });
    });

    cy.contains('typing...', { timeout: 5000 }).should('be.visible');
  });

  it('should update presence status of other users', () => {
    // Auto-connect should trigger
    cy.wait('@tokenRequest');

    cy.window().then((win) => {
      return new Cypress.Promise((resolve) => {
        const checkInstance = () => {
          if (win.mockEventSourceInstance) {
            resolve(win.mockEventSourceInstance);
          } else {
            setTimeout(checkInstance, 10);
          }
        };
        checkInstance();
      });
    }).then((mockInstance) => {
      mockInstance.simulateMessage({
        type: 'presence_update',
        user_id: 'other-user',
        status: 'online',
        timestamp: new Date().toISOString(),
        metadata: { name: 'Other User' }
      });
    });

    cy.contains('Online Users').should('exist');
    cy.contains('User other-user').should('be.visible');
  });

  it('should send a chat message', () => {
    // Auto-connect should trigger
    cy.wait('@tokenRequest');

    cy.get('input[placeholder="Enter recipient user ID"]').type('other-user');
    cy.get('input[placeholder*="Enter test message"]').type('Hello World');
    cy.contains('button', 'Send Chat Message').click();

    cy.wait('@sendMessage').then((interception) => {
      expect(interception.request.body).to.deep.include({
        recipient_id: 'other-user',
        message: { type: 'text', content: 'Hello World' }
      });
    });
  });
});
