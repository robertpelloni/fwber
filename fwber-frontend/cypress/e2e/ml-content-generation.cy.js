describe('ML Content Generation E2E Test', () => {
  const testUser = {
    name: 'AI Test User',
    email: `ai-test-${Date.now()}@example.com`,
    password: 'password123',
  };

  beforeEach(() => {
    cy.visit('/register');
    cy.url().should('include', '/register');
  });

  it('should allow a user to generate AI profile content end-to-end', () => {
    // 1. User Registration
    cy.get('input[name="name"]').type(testUser.name);
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('input[name="password_confirmation"]').type(testUser.password);
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');
    cy.contains('Welcome, AI Test User');

    // 2. Navigate to Content Generation
    cy.get('a[href="/content-generation"]').click();
    cy.url().should('include', '/content-generation');
    cy.contains('AI Content Generation');

    // 3. Test AI Profile Builder
    cy.get('[data-testid="personality-extroverted"]').click();
    cy.get('[data-testid="interest-travel"]').click();
    cy.get('[data-testid="interest-music"]').click();
    cy.get('[data-testid="interest-photography"]').click();
    
    cy.get('textarea[placeholder*="What are you looking for"]').type(
      'Looking for someone to share adventures and create memories with!'
    );
    
    cy.get('[data-testid="style-casual"]').click();
    cy.get('input[placeholder*="Target audience"]').type('adventure lovers');

    // 4. Generate AI Profile
    cy.get('button').contains('Generate AI Profile').click();
    
    // Wait for generation to complete
    cy.get('[data-testid="generation-loading"]', { timeout: 30000 }).should('not.exist');
    
    // Verify suggestions are displayed
    cy.get('[data-testid="suggestion-item"]').should('have.length.greaterThan', 0);
    
    // Check suggestion quality
    cy.get('[data-testid="suggestion-item"]').first().within(() => {
      cy.get('[data-testid="suggestion-content"]').should('not.be.empty');
      cy.get('[data-testid="confidence-score"]').should('contain', '%');
      cy.get('[data-testid="safety-score"]').should('contain', '%');
      cy.get('[data-testid="provider-badge"]').should('contain.oneOf', ['OpenAI', 'Gemini']);
    });

    // 5. Test suggestion selection
    cy.get('[data-testid="suggestion-item"]').first().within(() => {
      cy.get('button').contains('Select').click();
    });

    cy.get('[data-testid="selected-profile"]').should('be.visible');
    cy.get('[data-testid="selected-content"]').should('not.be.empty');

    // 6. Test feedback submission
    cy.get('[data-testid="suggestion-item"]').first().within(() => {
      cy.get('[data-testid="rating-5"]').click();
    });

    // Verify feedback was submitted (should show success message)
    cy.get('[data-testid="feedback-success"]', { timeout: 10000 }).should('be.visible');
  });

  it('should test Smart Content Editor with real-time optimization', () => {
    // Login first
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Navigate to content generation
    cy.get('a[href="/content-generation"]').click();
    cy.url().should('include', '/content-generation');

    // Test Smart Content Editor
    cy.get('[data-testid="smart-editor"]').should('be.visible');
    
    // Type some content
    const testContent = 'hey, i like hiking and stuff. want to hang out sometime?';
    cy.get('[data-testid="content-textarea"]').type(testContent);
    
    // Verify character count
    cy.get('[data-testid="character-count"]').should('contain', testContent.length);

    // Test real-time quality analysis
    cy.get('[data-testid="quality-analysis"]').should('be.visible');
    cy.get('[data-testid="readability-score"]').should('contain', '%');
    cy.get('[data-testid="engagement-score"]').should('contain', '%');
    cy.get('[data-testid="safety-score"]').should('contain', '%');

    // Test content optimization
    cy.get('button').contains('Optimize Content').click();
    
    // Wait for optimization to complete
    cy.get('[data-testid="optimization-loading"]', { timeout: 30000 }).should('not.exist');
    
    // Verify optimized content is different and better
    cy.get('[data-testid="optimized-content"]').should('be.visible');
    cy.get('[data-testid="optimized-text"]').should('not.contain', testContent);
    cy.get('[data-testid="optimized-text"]').should('not.be.empty');
    
    // Check improvement suggestions
    cy.get('[data-testid="improvement-suggestions"]').should('be.visible');
    cy.get('[data-testid="suggestion-item"]').should('have.length.greaterThan', 0);

    // Test applying optimization
    cy.get('button').contains('Apply Optimization').click();
    cy.get('[data-testid="content-textarea"]').should('not.contain', testContent);
  });

  it('should test bulletin board post suggestions', () => {
    // Login first
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Navigate to bulletin boards
    cy.get('a[href="/bulletin-boards"]').click();
    cy.url().should('include', '/bulletin-boards');

    // Mock geolocation for consistent testing
    cy.window().then((win) => {
      cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((cb) => {
        return cb({
          coords: {
            latitude: 34.052235,
            longitude: -118.243683,
            accuracy: 20,
          },
        });
      });
    });

    // Create a bulletin board
    const boardName = `AI Test Board ${Date.now()}`;
    cy.get('button').contains('Create New Board').click();
    cy.get('input[placeholder="Board Name"]').type(boardName);
    cy.get('textarea[placeholder="Description"]').type('A test board for AI content generation');
    cy.get('button').contains('Submit').click();
    cy.contains(boardName);

    // Select the board
    cy.contains(boardName).click();

    // Test post suggestions
    cy.get('button').contains('Get AI Suggestions').click();
    
    // Wait for suggestions to load
    cy.get('[data-testid="post-suggestions"]', { timeout: 30000 }).should('be.visible');
    cy.get('[data-testid="suggestion-item"]').should('have.length.greaterThan', 0);

    // Verify suggestion quality
    cy.get('[data-testid="suggestion-item"]').first().within(() => {
      cy.get('[data-testid="suggestion-content"]').should('not.be.empty');
      cy.get('[data-testid="relevance-score"]').should('contain', '%');
    });

    // Test using a suggestion
    cy.get('[data-testid="suggestion-item"]').first().within(() => {
      cy.get('button').contains('Use This').click();
    });

    // Verify the suggestion was applied to the message input
    cy.get('textarea[placeholder*="Type your message"]').should('not.be.empty');
  });

  it('should test conversation starter generation', () => {
    // Login first
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Navigate to content generation
    cy.get('a[href="/content-generation"]').click();
    cy.url().should('include', '/content-generation');

    // Test conversation starter generation
    cy.get('[data-testid="conversation-starters-tab"]').click();
    
    // Set context
    cy.get('[data-testid="conversation-type"]').select('casual');
    cy.get('[data-testid="target-interests"]').type('travel, music, food');
    cy.get('[data-testid="conversation-hints"]').type('shared love for adventure');

    // Generate conversation starters
    cy.get('button').contains('Generate Starters').click();
    
    // Wait for generation
    cy.get('[data-testid="conversation-loading"]', { timeout: 30000 }).should('not.exist');
    
    // Verify starters
    cy.get('[data-testid="conversation-starter"]').should('have.length.greaterThan', 0);
    
    cy.get('[data-testid="conversation-starter"]').first().within(() => {
      cy.get('[data-testid="starter-content"]').should('not.be.empty');
      cy.get('[data-testid="starter-content"]').should('contain', '?'); // Should be a question
      cy.get('[data-testid="engagement-score"]').should('contain', '%');
    });

    // Test using a starter
    cy.get('[data-testid="conversation-starter"]').first().within(() => {
      cy.get('button').contains('Use This').click();
    });

    // Verify the starter was copied
    cy.get('[data-testid="copied-starter"]').should('be.visible');
  });

  it('should handle AI provider failures gracefully', () => {
    // Login first
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Navigate to content generation
    cy.get('a[href="/content-generation"]').click();
    cy.url().should('include', '/content-generation');

    // Mock API failure
    cy.intercept('POST', '/api/content-generation/profile', {
      statusCode: 500,
      body: { error: 'AI service temporarily unavailable' }
    }).as('profileGenerationFailure');

    // Try to generate profile
    cy.get('[data-testid="personality-extroverted"]').click();
    cy.get('button').contains('Generate AI Profile').click();

    // Wait for failure
    cy.wait('@profileGenerationFailure');
    
    // Verify error handling
    cy.get('[data-testid="generation-error"]').should('be.visible');
    cy.get('[data-testid="error-message"]').should('contain', 'temporarily unavailable');
    
    // Verify retry option is available
    cy.get('button').contains('Try Again').should('be.visible');
  });

  it('should test performance and caching', () => {
    // Login first
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Navigate to content generation
    cy.get('a[href="/content-generation"]').click();
    cy.url().should('include', '/content-generation');

    // Set preferences
    cy.get('[data-testid="personality-creative"]').click();
    cy.get('[data-testid="interest-art"]').click();
    cy.get('[data-testid="interest-music"]').click();

    // First generation
    const startTime1 = Date.now();
    cy.get('button').contains('Generate AI Profile').click();
    cy.get('[data-testid="generation-loading"]', { timeout: 30000 }).should('not.exist');
    const endTime1 = Date.now();
    const firstGenerationTime = endTime1 - startTime1;

    // Second generation (should be cached)
    const startTime2 = Date.now();
    cy.get('button').contains('Generate AI Profile').click();
    cy.get('[data-testid="generation-loading"]', { timeout: 5000 }).should('not.exist');
    const endTime2 = Date.now();
    const secondGenerationTime = endTime2 - startTime2;

    // Cached generation should be faster
    cy.log(`First generation: ${firstGenerationTime}ms`);
    cy.log(`Second generation: ${secondGenerationTime}ms`);
    
    // Second generation should be significantly faster (cached)
    expect(secondGenerationTime).to.be.lessThan(firstGenerationTime * 0.5);
  });

  it('should test analytics and statistics', () => {
    // Login first
    cy.visit('/login');
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();

    cy.url().should('include', '/dashboard');

    // Navigate to content generation
    cy.get('a[href="/content-generation"]').click();
    cy.url().should('include', '/content-generation');

    // Generate some content first
    cy.get('[data-testid="personality-analytical"]').click();
    cy.get('button').contains('Generate AI Profile').click();
    cy.get('[data-testid="generation-loading"]', { timeout: 30000 }).should('not.exist');

    // Check analytics
    cy.get('[data-testid="analytics-tab"]').click();
    
    // Verify analytics are displayed
    cy.get('[data-testid="generation-stats"]').should('be.visible');
    cy.get('[data-testid="total-generations"]').should('contain', '1');
    cy.get('[data-testid="success-rate"]').should('contain', '%');
    cy.get('[data-testid="average-time"]').should('contain', 'ms');
    
    // Check optimization stats
    cy.get('[data-testid="optimization-stats"]').should('be.visible');
    cy.get('[data-testid="optimization-count"]').should('be.visible');
    cy.get('[data-testid="improvement-score"]').should('contain', '%');
  });
});
