describe('Video Chat Flow', () => {
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

  const partner = {
    id: 2,
    email: 'partner@example.com',
    name: 'Future Partner',
    profile: {
      display_name: 'Future Partner',
      age: 25,
      bio: 'I like hiking',
      location_description: 'New York, NY',
      avatar_url: '/images/test-avatar.svg'
    }
  };

  beforeEach(() => {
    // Mock login
    cy.intercept('POST', '**/api/auth/login', {
      statusCode: 200,
      body: {
        token: 'fake-token',
        user: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('login');

    // Mock user profile
    cy.intercept('GET', '**/api/user', {
      statusCode: 200,
      body: {
        data: { id: 1, name: 'Test User', profile_complete: true }
      }
    }).as('getUser');

    // Mock conversations list
    cy.intercept('GET', '**/api/matches/established', {
      statusCode: 200,
      body: {
        data: [
          {
            id: 101,
            user1_id: 1,
            user2_id: 2,
            updated_at: new Date().toISOString(),
            last_message: null,
            other_user: partner
          }
        ]
      }
    }).as('getConversations');

    // Mock messages
    cy.intercept('GET', '**/api/messages/2', {
      statusCode: 200,
      body: {
        messages: []
      }
    }).as('getMessages');

    // Mock mark as read
    cy.intercept('POST', '**/api/messages/mark-all-read/2', {
      statusCode: 200,
      body: { success: true }
    }).as('markRead');

    // Mock Video Chat Endpoints
    cy.intercept('POST', '**/api/video/initiate', {
      statusCode: 201,
      body: {
        call_id: 'call-123',
        status: 'initiated'
      }
    }).as('initiateCall');

    cy.intercept('POST', '**/api/video/signal', {
      statusCode: 200,
      body: { success: true }
    }).as('sendSignal');

    cy.intercept('PUT', '**/api/video/call-123/status', {
      statusCode: 200,
      body: { success: true }
    }).as('updateCallStatus');
  });

  it('allows initiating a video call', () => {
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        
        // Inject Feature Flag
        win.__CYPRESS_FEATURE_FLAGS__ = {
          video_chat: true
        };

        // Mock getUserMedia
        cy.stub(win.navigator.mediaDevices, 'getUserMedia').resolves({
          getTracks: () => [{ stop: () => {} }],
          getVideoTracks: () => [{ enabled: true }],
          getAudioTracks: () => [{ enabled: true }]
        });

        // Mock RTCPeerConnection
        win.RTCPeerConnection = class MockRTCPeerConnection {
          constructor() {
            this.localDescription = null;
            this.remoteDescription = null;
            this.iceConnectionState = 'new';
            this.signalingState = 'stable';
            this.onicecandidate = null;
            this.ontrack = null;
            this.onnegotiationneeded = null;
          }
          
          createOffer() {
            return Promise.resolve({ type: 'offer', sdp: 'mock-sdp' });
          }
          
          createAnswer() {
            return Promise.resolve({ type: 'answer', sdp: 'mock-sdp' });
          }
          
          setLocalDescription(desc) {
            this.localDescription = desc;
            return Promise.resolve();
          }
          
          setRemoteDescription(desc) {
            this.remoteDescription = desc;
            return Promise.resolve();
          }
          
          addTrack() {}
          close() {}
        };
      }
    });

    cy.wait('@getConversations');

    // 1. Select conversation
    cy.contains('Future Partner').should('be.visible').click();
    cy.wait('@getMessages');

    // 2. Click Video Call button
    cy.get('button[title="Video Call"]').should('be.visible').click();

    // 3. Verify Modal Opens
    cy.contains('Calling Future Partner...').should('be.visible');
    
    // 4. Verify API call
    cy.wait('@initiateCall');

    // 5. Verify Local Video is "playing" (mocked stream)
    cy.get('video').should('have.length.at.least', 1);

    // 6. End Call
    cy.get('button[title="End Call"]').click();

    // 7. Verify Modal Closes
    cy.contains('Calling Future Partner...').should('not.exist');
    
    // 8. Verify Status Update
    cy.wait('@updateCallStatus');
  });

  it('handles incoming video call', () => {
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        
        // Inject Feature Flag
        win.__CYPRESS_FEATURE_FLAGS__ = {
          video_chat: true
        };

        // Mock getUserMedia
        cy.stub(win.navigator.mediaDevices, 'getUserMedia').resolves({
          getTracks: () => [{ stop: () => {} }],
          getVideoTracks: () => [{ enabled: true }],
          getAudioTracks: () => [{ enabled: true }]
        });

        // Mock EventSource to simulate incoming call
        win.EventSource = class MockEventSource {
            constructor(url) {
                this.url = url;
                this.onmessage = null;
                this.addEventListener = (type, callback) => {
                    if (type === 'message') this.onmessage = callback;
                };
                win.mockEventSourceInstance = this;
            }
            close() {}
        };
      }
    });

    cy.wait('@getConversations');

    // Simulate incoming call event
    cy.window().then((win) => {
        // Wait for EventSource to be initialized by the app
        // This might be flaky if app initializes it async, but usually it's in useEffect
        
        const incomingCallData = {
            type: 'video_call_initiated',
            data: {
                call_id: 'call-incoming-123',
                caller_id: partner.id,
                caller_name: partner.name,
                caller_avatar: partner.profile.avatar_url
            }
        };

        // We need to simulate the Mercure structure
        // The app likely listens to a specific topic
        if (win.mockEventSourceInstance && win.mockEventSourceInstance.onmessage) {
            const event = new MessageEvent('message', {
                data: JSON.stringify(incomingCallData)
            });
            win.mockEventSourceInstance.onmessage(event);
        }
    });

    // Verify Incoming Call Modal
    // Note: This depends on how the app handles the event. 
    // If the app uses a global listener, this should work.
    // If it requires being in a specific conversation, we might need to navigate first.
    // Assuming global listener for incoming calls:
    
    cy.contains('Incoming Call').should('be.visible');
    cy.contains('Future Partner is calling...').should('be.visible');

    // Accept Call
    cy.contains('button', 'Accept').click();

    // Verify Video Interface opens
    cy.get('video').should('have.length.at.least', 1);
  });

  it('handles camera permission denial gracefully', () => {
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        
        win.__CYPRESS_FEATURE_FLAGS__ = {
          video_chat: true
        };

        // Mock getUserMedia to throw error
        cy.stub(win.navigator.mediaDevices, 'getUserMedia').rejects(new Error('Permission denied'));
      }
    });

    cy.wait('@getConversations');
    cy.contains('Future Partner').click();
    cy.wait('@getMessages');

    cy.get('button[title="Video Call"]').click();

    // Verify Error Toast/Message
    // Adjust selector based on your Toast implementation
    cy.contains('Camera/Microphone permission denied').should('be.visible');
    
    // Verify Modal did NOT open or closed immediately
    cy.contains('Calling Future Partner...').should('not.exist');
  });

  it('allows declining an incoming call', () => {
    cy.visit('/messages', {
      onBeforeLoad: (win) => {
        win.localStorage.setItem('fwber_token', 'fake-token');
        win.localStorage.setItem('fwber_user', JSON.stringify(user));
        
        win.__CYPRESS_FEATURE_FLAGS__ = {
          video_chat: true
        };

        // Mock EventSource
        win.EventSource = class MockEventSource {
            constructor(url) {
                this.url = url;
                this.onmessage = null;
                this.addEventListener = (type, callback) => {
                    if (type === 'message') this.onmessage = callback;
                };
                win.mockEventSourceInstance = this;
            }
            close() {}
        };
      }
    });

    cy.wait('@getConversations');

    // Simulate incoming call
    cy.window().then((win) => {
        const incomingCallData = {
            type: 'video_call_initiated',
            data: {
                call_id: 'call-incoming-456',
                caller_id: partner.id,
                caller_name: partner.name,
                caller_avatar: partner.profile.avatar_url
            }
        };

        if (win.mockEventSourceInstance && win.mockEventSourceInstance.onmessage) {
            const event = new MessageEvent('message', {
                data: JSON.stringify(incomingCallData)
            });
            win.mockEventSourceInstance.onmessage(event);
        }
    });

    cy.contains('Incoming Call').should('be.visible');
    
    // Click Decline
    cy.contains('button', 'Decline').click();

    // Verify Modal Closes
    cy.contains('Incoming Call').should('not.exist');
    
    // Verify Video Interface is NOT open
    cy.get('video').should('not.exist');
  });
});
