describe('FWBer.me Bulletin Board System', () => {
  beforeEach(() => {
    // Mock geolocation to Times Square, NYC
    cy.window().then((win) => {
      cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((success) => {
        success({
          coords: {
            latitude: 40.7589,
            longitude: -73.9851,
            accuracy: 10
          }
        })
      })
    })

    // Visit the bulletin boards page
    cy.visit('/bulletin-boards')
  })

  it('should display bulletin board interface', () => {
    // Check if the main interface loads
    cy.contains('📍 Local Bulletin Boards').should('be.visible')
    cy.contains('Connect with people in your area').should('be.visible')
    
    // Check for location display
    cy.contains('Your location: 40.7589, -73.9851').should('be.visible')
  })

  it('should show real-time connection status', () => {
    // Check for Mercure connection indicator
    cy.get('[data-testid="connection-status"]').should('be.visible')
    
    // Should show either connected or connecting
    cy.get('[data-testid="connection-status"]').should('contain.text', 'Real-time')
      .or('contain.text', 'Connecting')
  })

  it('should allow creating a new bulletin board', () => {
    // Click the "New Board" button
    cy.contains('+ New Board').click()
    
    // Should show success or loading state
    cy.contains('Creating').or('Board created').should('be.visible')
  })

  it('should allow posting messages to bulletin boards', () => {
    // Wait for boards to load
    cy.get('[data-testid="bulletin-board"]').first().click()
    
    // Type a test message
    cy.get('textarea[placeholder*="Share something"]').type('Test message from Cypress!')
    
    // Post the message
    cy.contains('Post').click()
    
    // Should show the message in the list
    cy.contains('Test message from Cypress!').should('be.visible')
  })

  it('should handle anonymous posting', () => {
    // Wait for boards to load
    cy.get('[data-testid="bulletin-board"]').first().click()
    
    // Check the anonymous option
    cy.get('input[type="checkbox"]').check()
    
    // Type a message
    cy.get('textarea[placeholder*="Share something"]').type('Anonymous test message')
    
    // Post the message
    cy.contains('Post').click()
    
    // Should show as anonymous
    cy.contains('Anonymous').should('be.visible')
  })

  it('should validate message content', () => {
    // Wait for boards to load
    cy.get('[data-testid="bulletin-board"]').first().click()
    
    // Try to post empty message
    cy.contains('Post').should('be.disabled')
    
    // Type a very long message (over 1000 chars)
    const longMessage = 'a'.repeat(1001)
    cy.get('textarea[placeholder*="Share something"]').type(longMessage)
    
    // Should show validation error
    cy.contains('Message too long').should('be.visible')
  })

  it('should handle SSE connection errors gracefully', () => {
    // Intercept SSE requests and return error
    cy.intercept('GET', '**/mercure**', { statusCode: 500 })
    
    // Reload the page
    cy.reload()
    
    // Should show error state but still be functional
    cy.contains('Failed to connect').or('Connection error').should('be.visible')
    
    // Basic functionality should still work
    cy.get('textarea[placeholder*="Share something"]').should('be.visible')
  })

  it('should respect rate limiting', () => {
    // Wait for boards to load
    cy.get('[data-testid="bulletin-board"]').first().click()
    
    // Post multiple messages quickly to trigger rate limiting
    for (let i = 0; i < 12; i++) {
      cy.get('textarea[placeholder*="Share something"]').clear().type(`Rate limit test ${i}`)
      cy.contains('Post').click()
      cy.wait(100) // Small delay between posts
    }
    
    // Should show rate limit error
    cy.contains('Rate limit exceeded').or('Too many requests').should('be.visible')
  })

  it('should handle offline scenarios', () => {
    // Simulate offline
    cy.window().then((win) => {
      cy.stub(win.navigator, 'onLine').value(false)
    })
    
    // Reload the page
    cy.reload()
    
    // Should show offline indicator
    cy.contains('Offline').or('No connection').should('be.visible')
  })
})


