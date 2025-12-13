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
});
