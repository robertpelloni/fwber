describe('Real-time Chat & Presence', () => {
  beforeEach(() => {
    // Forward console logs to Cypress terminal
    cy.on('window:console', (msg) => {
      // console.log('BROWSER LOG:', msg);
      // Use cy.task to print to terminal if possible, but we can't chain it here easily.
      // However, we can use a trick:
      // cy.now('task', 'log', 'BROWSER LOG: ' + JSON.stringify(msg)); // cy.now is internal
      // Let's just rely on the fact that we can see the output if we look closely or if we use a different approach.
      // Actually, let's try to use the task via a promise if possible, or just ignore it for now and trust the file output.
      // But the file output didn't show it.
      
      // Let's try to use a simple console.log, but maybe the issue is that the browser console is not piped.
    });

    // Mock the API endpoint for connection details
    cy.intercept('POST', '**/websocket/connect', {
      statusCode: 200,
      body: {
        connection_id: 'test-connection-id',
        user_id: 'test-user-id',
        channels: ['global'],
        heartbeat_interval: 30,
      },
    }).as('connectRequest');

    cy.visit('/websocket', {
      onBeforeLoad(win) {
        // Set auth token to simulate logged-in state
        win.localStorage.setItem('fwber_token', 'mock-jwt-token');
        win.localStorage.setItem('fwber_user', JSON.stringify({
          id: 'test-user-id',
          name: 'Test User',
          email: 'test@example.com'
        }));

        // Define the MockWebSocket class
        class MockWebSocket {
          constructor(url) {
            console.log('MockWebSocket instantiated with URL:', url);
            this.url = url;
            this.readyState = 0; // CONNECTING
            this.onopen = null;
            this.onmessage = null;
            this.onclose = null;
            this.onerror = null;
            this.listeners = {};

            // Store instance on window for test access
            win.mockWebSocketInstance = this;
            win.mockSentMessages = [];

            setTimeout(() => {
              console.log('MockWebSocket opening...');
              this.readyState = 1; // OPEN
              if (this.onopen) {
                 console.log('Calling onopen handler');
                 this.onopen();
              } else {
                 console.log('No onopen handler defined');
              }
              this.dispatchEvent({ type: 'open' });
            }, 100);
          }

          send(data) {
            const parsed = JSON.parse(data);
            win.mockSentMessages.push(parsed);
          }

          close() {
            this.readyState = 3; // CLOSED
            const event = { type: 'close', code: 1000, reason: 'Normal Closure', wasClean: true };
            if (this.onclose) this.onclose(event);
            this.dispatchEvent(event);
          }

          addEventListener(type, listener) {
            if (!this.listeners[type]) {
              this.listeners[type] = [];
            }
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

          // Helper for tests to simulate incoming messages
          simulateMessage(data) {
            const event = {
              type: 'message',
              data: JSON.stringify(data),
              origin: 'ws://mock',
              lastEventId: '',
              source: null,
              ports: []
            };
            
            if (this.onmessage) {
              this.onmessage(event);
            }
            this.dispatchEvent(event);
          }
        }

        // Constants
        MockWebSocket.CONNECTING = 0;
        MockWebSocket.OPEN = 1;
        MockWebSocket.CLOSING = 2;
        MockWebSocket.CLOSED = 3;

        // Replace native WebSocket
        win.WebSocket = MockWebSocket;
      }
    });
  });

  it('should load login page', () => {
    cy.visit('/login');
    cy.contains('Sign in').should('exist');
  });

  it('should connect to WebSocket and show online status', () => {
    // Wait for AuthProvider and WebSocket client initialization
    cy.contains('button', 'Connect').should('not.be.disabled').click();
    cy.wait('@connectRequest');
    
    // Wait for connection to be established (MockWebSocket sets readyState=1 after 100ms)
    cy.window().then((win) => {
      return new Cypress.Promise((resolve) => {
        const checkConnection = () => {
          if (win.mockWebSocketInstance && win.mockWebSocketInstance.readyState === 1) {
            resolve();
          } else {
            setTimeout(checkConnection, 50);
          }
        };
        checkConnection();
      });
    });

    // Verify connection status in UI
    // Note: The UI might show "Connected" or a green dot. Adjusting expectation to be more generic if needed.
    // Assuming the UI has a "Connected" text or similar indicator.
    cy.contains('Connected', { timeout: 10000 }).should('be.visible');
  });

  it('should display typing indicator when receiving event', () => {
    cy.contains('button', 'Connect').should('not.be.disabled').click();
    cy.wait('@connectRequest');

    // Select a recipient to enable the chat view
    cy.get('input[placeholder="Enter recipient user ID"]').type('other-user');

    cy.window().then((win) => {
      // Simulate incoming typing indicator
      win.mockWebSocketInstance.simulateMessage({
        type: 'typing_indicator',
        data: {
          from_user_id: 'other-user',
          to_user_id: 'test-user-id',
          is_typing: true,
        },
        timestamp: new Date().toISOString(),
      });
    });

    // Check for typing indicator in UI
    cy.contains('typing...', { timeout: 5000 }).should('be.visible');

    cy.window().then((win) => {
      // Simulate stopping typing
      win.mockWebSocketInstance.simulateMessage({
        type: 'typing_indicator',
        data: {
          from_user_id: 'other-user',
          to_user_id: 'test-user-id',
          is_typing: false,
        },
        timestamp: new Date().toISOString(),
      });
    });

    cy.contains('typing...').should('not.exist');
  });

  it('should update presence status of other users', () => {
    cy.contains('button', 'Connect').should('not.be.disabled').click();
    cy.wait('@connectRequest');
    cy.wait(500); // Wait for listeners to be attached

    cy.window().then((win) => {
      // Simulate presence update
      win.mockWebSocketInstance.simulateMessage({
        type: 'presence_update',
        data: {
          user_id: 'other-user',
          status: 'online',
          metadata: { name: 'Other User' }
        },
        timestamp: new Date().toISOString(),
      });
    });

    // Verify user appears in online list or status updates
    cy.contains('User other-user').should('be.visible');
    cy.contains('online').should('be.visible');
  });

  it('should send a chat message', () => {
    cy.contains('button', 'Connect').should('not.be.disabled').click();
    cy.wait('@connectRequest');

    // Select a recipient
    cy.get('input[placeholder="Enter recipient user ID"]').type('other-user');

    // Type and send a message
    // Ensure the input exists first
    cy.get('input[placeholder*="Type a message"]', { timeout: 10000 }).should('exist');
    cy.get('input[placeholder*="Type a message"]').type('Hello World{enter}');

    // Verify message was sent via WebSocket
    cy.window().then((win) => {
      // Wait a bit for the send to happen
      cy.wrap(null).then(() => {
        const sentMsg = win.mockSentMessages.find(m => m.type === 'chat_message');
        expect(sentMsg).to.exist;
        expect(sentMsg.data.message.content).to.equal('Hello World');
      });
    });

    // Verify message appears in UI (optimistic update or echo)
    cy.contains('Hello World').should('be.visible');
  });
});
